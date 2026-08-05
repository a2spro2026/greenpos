<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Models\UserDocument;
use App\Models\UserInvitation;
use App\Models\UserLog;
use App\Models\UserLoginLog;
use App\Services\UserService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(private UserService $users)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('users.view');
        $company = Workspace::company();

        $base = User::query()->forCompany($company->id);

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'invited' => UserInvitation::query()->where('company_id', $company->id)->where('status', 'pending')->count(),
            'new' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
        ];

        $logins = collect(range(6, 0))->map(function (int $i) use ($company) {
            $day = now()->subDays($i)->startOfDay();

            return [
                'label' => $day->format('D d'),
                'count' => UserLoginLog::query()
                    ->where('company_id', $company->id)
                    ->whereBetween('logged_in_at', [$day, (clone $day)->endOfDay()])
                    ->count(),
            ];
        });

        $recentLogins = UserLoginLog::query()
            ->where('company_id', $company->id)
            ->with('user')
            ->latest('logged_in_at')
            ->limit(8)
            ->get();

        $recentActivity = UserLog::query()
            ->where('company_id', $company->id)
            ->with(['user', 'actor'])
            ->latest()
            ->limit(10)
            ->get();

        $recentUsers = User::query()
            ->forCompany($company->id)
            ->latest()
            ->limit(6)
            ->get();

        return view('users.dashboard', compact('stats', 'logins', 'recentLogins', 'recentActivity', 'recentUsers'));
    }

    public function index(Request $request): View
    {
        $this->authorize('users.view');
        $company = Workspace::company();

        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, ['name', 'email', 'status', 'last_login_at', 'created_at', 'job_title'], true)) {
            $sort = 'name';
        }

        $users = User::query()
            ->forCompany($company->id)
            ->with(['stores' => fn ($q) => $q->where('company_id', $company->id), 'companies' => fn ($q) => $q->where('companies.id', $company->id)])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('role'), function ($q) use ($request, $company) {
                $q->whereHas('companies', fn ($c) => $c->where('companies.id', $company->id)->where('company_user.role', $request->string('role')));
            })
            ->when($request->filled('store_id'), function ($q) use ($request) {
                $q->whereHas('stores', fn ($s) => $s->where('stores.id', $request->integer('store_id')));
            })
            ->when($request->filled('department'), fn ($q) => $q->where('department', $request->string('department')))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'departments' => User::DEPARTMENTS,
            'filters' => $request->only(['q', 'status', 'role', 'store_id', 'department', 'sort', 'dir']),
            'company' => $company,
        ]);
    }

    public function create(): View
    {
        $this->authorize('users.create');

        return view('users.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('users.create');
        $data = $this->validated($request, true);
        $user = $this->users->create($data, $request->input('store_ids', []), $request->file('photo'));

        return redirect()->route('users.show', $user)->with('success', 'Utilisateur créé.');
    }

    public function show(Request $request, User $user): View
    {
        $this->authorize('users.view');
        $company = Workspace::company();
        $this->ensureCompany($user);

        $user->load([
            'stores' => fn ($q) => $q->where('company_id', $company->id),
            'companies' => fn ($q) => $q->where('companies.id', $company->id),
            'documents' => fn ($q) => $q->where('company_id', $company->id),
            'loginLogs' => fn ($q) => $q->where('company_id', $company->id)->limit(20),
            'logs' => fn ($q) => $q->where('company_id', $company->id)->with('actor')->limit(30),
        ]);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'activity', 'permissions', 'stores', 'history', 'documents'], true)) {
            $tab = 'overview';
        }

        $role = $user->roleIn($company);
        $permissions = $this->permissionsForRole($role);

        return view('users.show', compact('user', 'tab', 'company', 'role', 'permissions'));
    }

    public function edit(User $user): View
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);
        $user->load(['stores', 'companies']);

        return view('users.edit', array_merge($this->formData(), ['user' => $user]));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);
        $data = $this->validated($request, false);
        $this->users->update($user, $data, $request->input('store_ids', []), $request->file('photo'));

        return redirect()->route('users.show', $user)->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('users.delete');
        $this->ensureCompany($user);
        $this->users->delete($user);

        return redirect()->route('users.index')->with('success', 'Utilisateur archivé.');
    }

    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);
        $this->users->deactivate($user);

        return back()->with('success', 'Utilisateur désactivé.');
    }

    public function reactivate(User $user): RedirectResponse
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);
        $this->users->reactivate($user);

        return back()->with('success', 'Utilisateur réactivé.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.reset');
        $this->ensureCompany($user);

        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $this->users->resetPassword($user, $data['password']);

        return back()->with('success', 'Mot de passe réinitialisé.');
    }

    public function invite(Request $request): RedirectResponse
    {
        $this->authorize('users.invite');

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
        ]);

        $invitation = $this->users->invite($data);

        return back()->with('success', 'Invitation préparée pour '.$invitation->email.' (envoi email à brancher).');
    }

    public function storeDocument(Request $request, User $user): RedirectResponse
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);

        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(array_keys(UserDocument::CATEGORIES))],
        ]);

        $this->users->storeDocument($user, $request->file('file'), $data['title'] ?? null, $data['category'] ?? 'other');

        return redirect()->route('users.show', ['user' => $user, 'tab' => 'documents'])->with('success', 'Document ajouté.');
    }

    public function destroyDocument(User $user, UserDocument $document): RedirectResponse
    {
        $this->authorize('users.update');
        $this->ensureCompany($user);
        if ($document->user_id !== $user->id) {
            abort(404);
        }
        $this->users->deleteDocument($document);

        return back()->with('success', 'Document supprimé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('users.export');
        $company = Workspace::company();

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['nom', 'email', 'telephone', 'fonction', 'departement', 'role', 'statut', 'derniere_connexion', 'boutiques'], ';');

            User::query()
                ->forCompany($company->id)
                ->with(['stores' => fn ($q) => $q->where('company_id', $company->id), 'companies' => fn ($q) => $q->where('companies.id', $company->id)])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('name')
                ->chunk(100, function ($chunk) use ($out, $company) {
                    foreach ($chunk as $user) {
                        fputcsv($out, [
                            $user->displayName(),
                            $user->email,
                            $user->phone,
                            $user->job_title,
                            $user->departmentLabel(),
                            $user->roleLabel($company),
                            $user->statusLabel(),
                            optional($user->last_login_at)->format('Y-m-d H:i'),
                            $user->stores->pluck('name')->implode(', '),
                        ], ';');
                    }
                });
            fclose($out);
        }, 'utilisateurs-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(User $user): View
    {
        $this->authorize('users.print');
        $company = Workspace::company();
        $this->ensureCompany($user);
        $user->load(['stores' => fn ($q) => $q->where('company_id', $company->id)]);

        return view('users.print', compact('user', 'company'));
    }

    protected function validated(Request $request, bool $creating): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'username' => ['nullable', 'string', 'max:80', 'alpha_dash'],
            'phone' => ['nullable', 'string', 'max:40'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'department' => ['nullable', Rule::in(array_keys(User::DEPARTMENTS))],
            'hired_at' => ['nullable', 'date'],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'status' => ['required', Rule::in(array_keys(User::STATUSES))],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer', 'exists:stores,id'],
            'password' => $creating
                ? ['required', 'confirmed', Password::defaults()]
                : ['nullable', 'confirmed', Password::defaults()],
        ]);
    }

    protected function formData(): array
    {
        $company = Workspace::company();

        return [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'roles' => User::ROLES,
            'statuses' => User::STATUSES,
            'departments' => User::DEPARTMENTS,
            'company' => $company,
        ];
    }

    protected function ensureCompany(User $user): void
    {
        $company = Workspace::company();
        if (! $company || ! $user->companies()->where('companies.id', $company->id)->exists()) {
            abort(404);
        }
    }

    protected function permissionsForRole(?string $role): array
    {
        $matrix = [
            'products' => ['view', 'create', 'update', 'delete', 'export'],
            'stock' => ['view', 'move', 'adjust', 'inventory', 'export'],
            'purchases' => ['view', 'create', 'update', 'cancel', 'receive'],
            'customers' => ['view', 'create', 'update', 'delete', 'export'],
            'pos' => ['view', 'sell', 'open', 'close', 'history'],
            'invoices' => ['view', 'create', 'update', 'cancel', 'export'],
            'quotes' => ['view', 'create', 'update', 'convert', 'send'],
            'sales' => ['view', 'create', 'update', 'cancel', 'return'],
            'reports' => ['view', 'export', 'print', 'financial'],
            'users' => ['view', 'create', 'update', 'delete', 'invite'],
        ];

        $result = [];
        foreach ($matrix as $module => $actions) {
            foreach ($actions as $action) {
                $ability = $module.'.'.$action;
                $result[] = [
                    'module' => $module,
                    'action' => $action,
                    'ability' => $ability,
                    'allowed' => $this->roleCan($role, $ability),
                ];
            }
        }

        return $result;
    }

    protected function roleCan(?string $role, string $ability): bool
    {
        if (! $role) {
            return false;
        }

        $roles = [
            'products.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'accountant', 'sales'],
            'products.create' => ['owner', 'admin', 'manager'],
            'products.update' => ['owner', 'admin', 'manager'],
            'products.delete' => ['owner', 'admin', 'manager'],
            'products.export' => ['owner', 'admin', 'manager', 'accountant'],
            'stock.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales'],
            'stock.move' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.adjust' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.inventory' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.export' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant'],
            'purchases.view' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant'],
            'purchases.create' => ['owner', 'admin', 'manager'],
            'purchases.update' => ['owner', 'admin', 'manager'],
            'purchases.cancel' => ['owner', 'admin', 'manager'],
            'purchases.receive' => ['owner', 'admin', 'manager', 'storekeeper'],
            'customers.view' => ['owner', 'admin', 'manager', 'cashier', 'sales', 'accountant'],
            'customers.create' => ['owner', 'admin', 'manager', 'sales'],
            'customers.update' => ['owner', 'admin', 'manager', 'sales'],
            'customers.delete' => ['owner', 'admin', 'manager'],
            'customers.export' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'pos.view' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.sell' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.open' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.close' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.history' => ['owner', 'admin', 'manager', 'cashier', 'accountant'],
            'invoices.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.create' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.update' => ['owner', 'admin', 'manager', 'accountant'],
            'invoices.cancel' => ['owner', 'admin', 'manager', 'accountant'],
            'invoices.export' => ['owner', 'admin', 'manager', 'accountant'],
            'quotes.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'quotes.create' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.update' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.convert' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.send' => ['owner', 'admin', 'manager', 'sales'],
            'sales.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'sales.create' => ['owner', 'admin', 'manager', 'sales'],
            'sales.update' => ['owner', 'admin', 'manager', 'sales'],
            'sales.cancel' => ['owner', 'admin', 'manager'],
            'sales.return' => ['owner', 'admin', 'manager'],
            'reports.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'reports.export' => ['owner', 'admin', 'manager', 'accountant'],
            'reports.print' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'reports.financial' => ['owner', 'admin', 'accountant'],
            'users.view' => ['owner', 'admin', 'manager'],
            'users.create' => ['owner', 'admin'],
            'users.update' => ['owner', 'admin'],
            'users.delete' => ['owner', 'admin'],
            'users.invite' => ['owner', 'admin'],
        ];

        return in_array($role, $roles[$ability] ?? [], true);
    }
}

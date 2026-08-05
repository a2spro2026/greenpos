<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use App\Support\PermissionCatalog;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RoleController extends Controller
{
    public function __construct(private RoleService $roles)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('roles.view');
        $company = Workspace::company();
        $this->roles->ensureSystemRoles();

        $systemRoles = Role::query()->system()->orderBy('id')->get();
        $customRoles = Role::query()->where('company_id', $company->id)->where('is_system', false)->orderBy('name')->get();

        $roles = $systemRoles->map(function (Role $role) use ($company) {
            $override = Role::query()->where('company_id', $company->id)->where('slug', $role->slug)->first();
            $effective = $override ?? $role;
            $effective->loadCount('permissions');
            $effective->users_count = $this->roles->usersWithRole($effective, $company)->count();
            $effective->is_override = (bool) $override;

            return $effective;
        })->merge($customRoles->each(function (Role $role) use ($company) {
            $role->loadCount('permissions');
            $role->users_count = $this->roles->usersWithRole($role, $company)->count();
            $role->is_override = false;
        }));

        $stats = [
            'roles' => $roles->count(),
            'system' => $systemRoles->count(),
            'custom' => $customRoles->count(),
            'permissions' => \App\Models\Permission::query()->count(),
            'users' => User::query()->forCompany($company->id)->count(),
        ];

        return view('roles.dashboard', compact('stats', 'roles'));
    }

    public function index(Request $request): View
    {
        $this->authorize('roles.view');
        $company = Workspace::company();
        $this->roles->ensureSystemRoles();

        $q = $request->string('q')->toString();

        $system = Role::query()->system()
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            }))
            ->orderBy('id')
            ->get()
            ->map(function (Role $role) use ($company) {
                $override = Role::query()->where('company_id', $company->id)->where('slug', $role->slug)->first();
                $effective = $override ?? $role;
                $effective->loadCount('permissions');
                $effective->users_count = $this->roles->usersWithRole($effective, $company)->count();

                return $effective;
            });

        $custom = Role::query()
            ->where('company_id', $company->id)
            ->where('is_system', false)
            ->when($q, fn ($query) => $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")->orWhere('slug', 'like', "%{$q}%");
            }))
            ->withCount('permissions')
            ->orderBy('name')
            ->get()
            ->each(fn (Role $role) => $role->users_count = $this->roles->usersWithRole($role, $company)->count());

        $roles = $system->merge($custom);

        return view('roles.index', [
            'roles' => $roles,
            'filters' => $request->only(['q']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('roles.create');
        $this->roles->ensureSystemRoles();

        return view('roles.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('roles.create');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:32'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $role = $this->roles->create($data, $request->input('permissions', []));

        return redirect()->route('roles.show', $role)->with('success', 'Rôle créé.');
    }

    public function show(Request $request, Role $role): View
    {
        $this->authorize('roles.view');
        $company = Workspace::company();
        $this->ensureAccessible($role);

        $effective = $role->company_id
            ? $role
            : ($this->roles->resolveForCompany($role->slug, $company->id) ?? $role);

        $effective->load(['permissions', 'logs.user']);
        $users = $this->roles->usersWithRole($effective, $company)->with('stores')->get();
        $allUsers = User::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get();
        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'permissions', 'users', 'history'], true)) {
            $tab = 'overview';
        }

        $matrix = $this->matrixData($effective->permissionKeys());

        return view('roles.show', [
            'role' => $effective,
            'sourceRole' => $role,
            'users' => $users,
            'allUsers' => $allUsers,
            'tab' => $tab,
            'matrix' => $matrix,
            'scopePermissions' => \App\Models\Permission::query()->where('group', 'scope')->orderBy('sort_order')->get(),
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorize('roles.update');
        $this->ensureAccessible($role);
        if ($role->is_super) {
            abort(403, 'Rôle non modifiable.');
        }

        $company = Workspace::company();
        $effective = $role->company_id
            ? $role
            : ($this->roles->resolveForCompany($role->slug, $company->id) ?? $role);
        $effective->load('permissions');

        return view('roles.edit', array_merge($this->formData($effective->permissionKeys()), [
            'role' => $effective,
            'sourceRole' => $role,
        ]));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.update');
        $this->ensureAccessible($role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:32'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $updated = $this->roles->update($role, $data, $request->input('permissions', []));

        return redirect()->route('roles.show', $updated)->with('success', 'Rôle mis à jour.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('roles.delete');
        $this->ensureAccessible($role);
        $this->roles->delete($role);

        return redirect()->route('roles.index')->with('success', 'Rôle supprimé.');
    }

    public function duplicate(Role $role): RedirectResponse
    {
        $this->authorize('roles.create');
        $this->ensureAccessible($role);
        $copy = $this->roles->duplicate($role);

        return redirect()->route('roles.edit', $copy)->with('success', 'Rôle dupliqué.');
    }

    public function matrix(): View
    {
        $this->authorize('roles.view');
        $company = Workspace::company();
        $this->roles->ensureSystemRoles();

        $roles = Role::query()->system()->orderBy('id')->get()->map(function (Role $role) use ($company) {
            return $this->roles->resolveForCompany($role->slug, $company->id) ?? $role;
        });
        $custom = Role::query()->where('company_id', $company->id)->where('is_system', false)->orderBy('name')->get();
        $roles = $roles->merge($custom)->each->load('permissions');

        $modules = PermissionCatalog::moduleActions();
        $actions = PermissionCatalog::ACTIONS;

        return view('roles.matrix', compact('roles', 'modules', 'actions'));
    }

    public function assignUsers(Request $request, Role $role): RedirectResponse
    {
        $this->authorize('roles.update');
        $this->ensureAccessible($role);

        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $this->roles->assignUsers($role, $data['user_ids']);

        return redirect()->route('roles.show', ['role' => $role, 'tab' => 'users'])->with('success', 'Utilisateurs assignés.');
    }

    public function export(): StreamedResponse
    {
        $this->authorize('roles.export');
        $company = Workspace::company();
        $this->roles->ensureSystemRoles();

        return Response::streamDownload(function () use ($company) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['slug', 'name', 'type', 'permissions_count', 'users_count'], ';');

            $system = Role::query()->system()->get();
            foreach ($system as $role) {
                $effective = $this->roles->resolveForCompany($role->slug, $company->id) ?? $role;
                fputcsv($out, [
                    $effective->slug,
                    $effective->name,
                    'system',
                    $effective->permissions()->count(),
                    $this->roles->usersWithRole($effective, $company)->count(),
                ], ';');
            }

            Role::query()->where('company_id', $company->id)->where('is_system', false)->each(function (Role $role) use ($out, $company) {
                fputcsv($out, [
                    $role->slug,
                    $role->name,
                    'custom',
                    $role->permissions()->count(),
                    $this->roles->usersWithRole($role, $company)->count(),
                ], ';');
            });

            fclose($out);
        }, 'roles-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function formData(array $selected = []): array
    {
        return [
            'modules' => PermissionCatalog::moduleActions(),
            'actions' => PermissionCatalog::ACTIONS,
            'moduleLabels' => PermissionCatalog::MODULES,
            'scopePermissions' => \App\Models\Permission::query()->where('group', 'scope')->orderBy('sort_order')->get(),
            'extraByModule' => \App\Models\Permission::query()
                ->where('group', 'modules')
                ->whereNotIn('action', array_keys(PermissionCatalog::ACTIONS))
                ->orderBy('sort_order')
                ->get()
                ->groupBy('module'),
            'selected' => $selected,
            'colors' => array_keys(Role::COLOR_CLASSES),
        ];
    }

    protected function matrixData(array $selected): array
    {
        return [
            'modules' => PermissionCatalog::moduleActions(),
            'actions' => PermissionCatalog::ACTIONS,
            'moduleLabels' => PermissionCatalog::MODULES,
            'selected' => $selected,
        ];
    }

    protected function ensureAccessible(Role $role): void
    {
        $company = Workspace::company();
        if ($role->company_id !== null && $role->company_id !== $company?->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Services\StoreService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StoreController extends Controller
{
    public function __construct(private StoreService $stores)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('stores.view');
        $stats = $this->stores->dashboardStats();

        return view('stores.dashboard', compact('stats'));
    }

    public function index(Request $request): View
    {
        $this->authorize('stores.view');
        $company = Workspace::company();

        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, ['name', 'city', 'code', 'is_active', 'created_at'], true)) {
            $sort = 'name';
        }

        $accessibleIds = Workspace::accessibleStores()->pluck('id');

        $query = Store::query()
            ->forCompany($company->id)
            ->whereIn('id', $accessibleIds)
            ->with(['manager', 'users'])
            ->withCount(['users', 'products'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('is_active', $request->string('status')->toString() === 'active');
            })
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->orderBy($sort, $dir);

        $stores = $query->paginate(15)->withQueryString();
        $this->stores->enrichMetrics(collect($stores->items()));

        $cities = Store::query()->forCompany($company->id)->whereIn('id', $accessibleIds)
            ->whereNotNull('city')->distinct()->orderBy('city')->pluck('city');

        return view('stores.index', [
            'stores' => $stores,
            'cities' => $cities,
            'filters' => $request->only(['q', 'status', 'city', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('stores.create');
        $users = $this->companyUsers();

        return view('stores.create', compact('users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stores.create');
        $data = $this->validated($request);
        $store = $this->stores->create($data, $request->file('logo'), $request->input('user_ids', []));

        return redirect()->route('stores.show', $store)->with('success', 'Boutique créée.');
    }

    public function show(Store $store): View
    {
        $this->authorize('stores.view');
        $this->stores->assertCompany($store);
        abort_unless(Workspace::canAccessStore($store), 403);

        $store->load(['manager', 'users']);
        $store->loadCount(['users', 'products']);
        $enriched = $this->stores->enrichMetrics(collect([$store]))->first();

        return view('stores.show', ['store' => $enriched]);
    }

    public function edit(Store $store): View
    {
        $this->authorize('stores.update');
        $this->stores->assertCompany($store);
        abort_unless(Workspace::canAccessStore($store), 403);

        $store->load(['manager', 'users']);
        $users = $this->companyUsers();

        return view('stores.edit', compact('store', 'users'));
    }

    public function update(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('stores.update');
        $this->stores->assertCompany($store);
        abort_unless(Workspace::canAccessStore($store), 403);

        $data = $this->validated($request);
        $this->stores->update($store, $data, $request->file('logo'), $request->input('user_ids', []));

        return redirect()->route('stores.show', $store)->with('success', 'Boutique mise à jour.');
    }

    public function destroy(Store $store): RedirectResponse
    {
        $this->authorize('stores.delete');
        $this->stores->assertCompany($store);
        $this->stores->delete($store);

        return redirect()->route('stores.index')->with('success', 'Boutique supprimée.');
    }

    public function deactivate(Store $store): RedirectResponse
    {
        $this->authorize('stores.update');
        $this->stores->assertCompany($store);
        $this->stores->deactivate($store);

        return back()->with('success', 'Boutique désactivée.');
    }

    public function activate(Store $store): RedirectResponse
    {
        $this->authorize('stores.update');
        $this->stores->assertCompany($store);
        $this->stores->activate($store);

        return back()->with('success', 'Boutique activée.');
    }

    public function switch(Request $request, Store $store): RedirectResponse
    {
        $this->authorize('stores.switch');
        $this->stores->assertCompany($store);
        abort_unless(Workspace::canAccessStore($store), 403);
        Workspace::switchStore($store);

        return $this->redirectAfterSwitch('Boutique active : '.$store->name);
    }

    public function switchAll(Request $request): RedirectResponse
    {
        $this->authorize('stores.switch');
        abort_unless(Workspace::canAccessAllStores(), 403);
        Workspace::setStoreFilterAll();

        return $this->redirectAfterSwitch('Vue multi-boutiques activée.');
    }

    protected function redirectAfterSwitch(string $message): RedirectResponse
    {
        $previous = url()->previous();
        $path = parse_url($previous, PHP_URL_PATH) ?: '/';

        return redirect($path)->with('success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('stores.export');
        $company = Workspace::company();
        $accessibleIds = Workspace::accessibleStores()->pluck('id');

        $stores = Store::query()
            ->forCompany($company->id)
            ->whereIn('id', $accessibleIds)
            ->with(['manager'])
            ->withCount(['users', 'products'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('is_active', $request->string('status') === 'active'))
            ->orderBy('name')
            ->get();

        $this->stores->enrichMetrics($stores);

        $filename = 'boutiques-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($stores) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nom', 'Code', 'Ville', 'Responsable', 'Téléphone', 'Email', 'Statut', 'Utilisateurs', 'Produits', 'CA'], ';');
            foreach ($stores as $store) {
                fputcsv($out, [
                    $store->name,
                    $store->code,
                    $store->city,
                    $store->manager?->name,
                    $store->phone,
                    $store->email,
                    $store->statusLabel(),
                    $store->users_count,
                    $store->metric_products,
                    number_format($store->metric_revenue, 2, ',', ' '),
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorize('stores.print');
        $company = Workspace::company();
        $accessibleIds = Workspace::accessibleStores()->pluck('id');

        $stores = Store::query()
            ->forCompany($company->id)
            ->whereIn('id', $accessibleIds)
            ->with(['manager'])
            ->withCount(['users', 'products'])
            ->orderBy('name')
            ->get();

        $this->stores->enrichMetrics($stores);

        return view('stores.print', compact('stores', 'company'));
    }

    public function printOne(Store $store): View
    {
        $this->authorize('stores.print');
        $this->stores->assertCompany($store);
        abort_unless(Workspace::canAccessStore($store), 403);
        $store->load(['manager', 'users']);
        $store->loadCount(['users', 'products']);
        $enriched = $this->stores->enrichMetrics(collect([$store]))->first();
        $company = Workspace::company();

        return view('stores.print-one', ['store' => $enriched, 'company' => $company]);
    }

    protected function validated(Request $request): array
    {
        $company = Workspace::company();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'manager_user_id' => [
                'nullable',
                'integer',
                function ($attr, $value, $fail) use ($company) {
                    if (! $value) {
                        return;
                    }
                    $ok = User::query()->whereKey($value)
                        ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))
                        ->exists();
                    if (! $ok) {
                        $fail('Responsable invalide.');
                    }
                },
            ],
            'opening_hours_summary' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer'],
            'local_receipt_footer' => ['nullable', 'string', 'max:500'],
            'local_default_printer' => ['nullable', 'string', 'max:255'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_default'] = $request->boolean('is_default');
        $data['local_settings'] = [
            'receipt_footer' => $request->string('local_receipt_footer')->toString(),
            'default_printer' => $request->string('local_default_printer')->toString(),
        ];

        return $data;
    }

    protected function companyUsers()
    {
        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', Workspace::company()->id))
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}

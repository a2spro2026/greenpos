<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\CompanyService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function __construct(private CompanyService $companies)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('companies.view');
        $stats = $this->companies->dashboardStats();

        return view('companies.dashboard', compact('stats'));
    }

    public function index(Request $request): View
    {
        $this->authorize('companies.view');

        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, ['name', 'activity', 'country', 'currency', 'status', 'created_at'], true)) {
            $sort = 'name';
        }

        $ids = Workspace::accessibleCompanies()->pluck('id');

        $query = Company::query()
            ->whereIn('id', $ids)
            ->withCount(['stores', 'users'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('country'), fn ($q) => $q->where('country', $request->string('country')))
            ->orderBy($sort, $dir);

        $companies = $query->paginate(15)->withQueryString();
        $this->companies->enrichMetrics(collect($companies->items()));

        $countries = Company::query()->whereIn('id', $ids)->whereNotNull('country')->distinct()->orderBy('country')->pluck('country');

        return view('companies.index', [
            'companies' => $companies,
            'countries' => $countries,
            'statuses' => Company::STATUSES,
            'filters' => $request->only(['q', 'status', 'country', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('companies.create');

        return view('companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('companies.create');
        $data = $this->validated($request);
        $company = $this->companies->create($data, $request->file('logo'));

        return redirect()->route('companies.show', $company)->with('success', 'Entreprise créée. Vous êtes désormais dans son contexte.');
    }

    public function show(Company $company): View
    {
        $this->authorize('companies.view');
        $this->companies->assertAccessible($company);

        $company->loadCount(['stores', 'users']);
        $company->load(['stores' => fn ($q) => $q->orderBy('name')->limit(8), 'users' => fn ($q) => $q->limit(8)]);
        $enriched = $this->companies->enrichMetrics(collect([$company]))->first();

        return view('companies.show', ['company' => $enriched]);
    }

    public function edit(Company $company): View
    {
        $this->authorize('companies.update');
        $this->companies->assertAccessible($company);

        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $this->authorize('companies.update');
        $this->companies->assertAccessible($company);
        $data = $this->validated($request);
        $this->companies->update($company, $data, $request->file('logo'));

        return redirect()->route('companies.show', $company)->with('success', 'Entreprise mise à jour.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->authorize('companies.delete');
        $this->companies->delete($company);

        return redirect()->route('companies.index')->with('success', 'Entreprise archivée (suppression douce).');
    }

    public function deactivate(Company $company): RedirectResponse
    {
        $this->authorize('companies.update');
        $this->companies->deactivate($company);

        return back()->with('success', 'Entreprise désactivée.');
    }

    public function activate(Company $company): RedirectResponse
    {
        $this->authorize('companies.update');
        $this->companies->activate($company);

        return back()->with('success', 'Entreprise activée.');
    }

    public function archive(Company $company): RedirectResponse
    {
        $this->authorize('companies.archive');
        $this->companies->archive($company);

        return back()->with('success', 'Entreprise archivée.');
    }

    public function switch(Company $company): RedirectResponse
    {
        $this->authorize('companies.switch');
        $this->companies->assertAccessible($company);
        Workspace::switchCompany($company);

        $path = parse_url(url()->previous(), PHP_URL_PATH) ?: '/';

        return redirect($path)->with('success', 'Entreprise active : '.$company->name);
    }

    public function setPrimary(Company $company): RedirectResponse
    {
        $this->companies->assertAccessible($company);
        $this->companies->setPrimary($company);

        return back()->with('success', 'Entreprise définie comme principale.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('companies.export');
        $ids = Workspace::accessibleCompanies()->pluck('id');

        $companies = Company::query()
            ->whereIn('id', $ids)
            ->withCount(['stores', 'users'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')
            ->get();

        $this->companies->enrichMetrics($companies);

        return response()->streamDownload(function () use ($companies) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Nom', 'Raison sociale', 'Secteur', 'Pays', 'Devise', 'Boutiques', 'Utilisateurs', 'Statut', 'Créée le', 'CA'], ';');
            foreach ($companies as $company) {
                fputcsv($out, [
                    $company->name,
                    $company->legal_name,
                    $company->activity,
                    $company->country,
                    $company->currency,
                    $company->stores_count,
                    $company->users_count,
                    $company->statusLabel(),
                    optional($company->created_at)->format('d/m/Y'),
                    number_format($company->metric_revenue ?? 0, 2, ',', ' '),
                ], ';');
            }
            fclose($out);
        }, 'entreprises-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function print(): View
    {
        $this->authorize('companies.print');
        $ids = Workspace::accessibleCompanies()->pluck('id');
        $companies = Company::query()->whereIn('id', $ids)->withCount(['stores', 'users'])->orderBy('name')->get();
        $this->companies->enrichMetrics($companies);

        return view('companies.print', compact('companies'));
    }

    public function printOne(Company $company): View
    {
        $this->authorize('companies.print');
        $this->companies->assertAccessible($company);
        $company->loadCount(['stores', 'users']);
        $enriched = $this->companies->enrichMetrics(collect([$company]))->first();

        return view('companies.print-one', ['company' => $enriched]);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'locale' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);
    }
}

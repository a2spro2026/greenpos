<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\SaasPlan;
use App\Models\SaasTenant;
use App\Services\ModuleManagerService;
use App\Services\PlatformAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class CompanyController extends Controller
{
    public function __construct(
        private PlatformAdminService $platform,
        private ModuleManagerService $modules,
    ) {
    }

    public function index(Request $request): View
    {
        $companies = Company::query()
            ->with(['stores', 'users'])
            ->withCount(['stores', 'users'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('city', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $tenants = SaasTenant::query()
            ->whereIn('company_id', $companies->pluck('id')->filter())
            ->with('currentSubscription.plan')
            ->get()
            ->keyBy('company_id');

        return view('admin.companies.index', [
            'companies' => $companies,
            'tenants' => $tenants,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('admin.companies.create', [
            'plans' => SaasPlan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'password' => ['nullable', 'string', 'min:8', 'max:64'],
        ]);

        try {
            $result = $this->platform->provisionCompany($data);
        } catch (Throwable $e) {
            return back()->withInput()->withErrors(['email' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.companies.show', $result['company'])
            ->with('success', 'Entreprise créée avec boutique, administrateur et modules.')
            ->with('generated_password', $result['password'])
            ->with('admin_email', $result['user']->email);
    }

    public function show(Company $company): View
    {
        $company->load(['stores', 'users']);
        $tenant = SaasTenant::query()
            ->where('company_id', $company->id)
            ->with(['currentSubscription.plan', 'payments', 'subscriptions.plan'])
            ->first();

        $this->modules->ensureSynced($company);
        $catalog = $this->modules->catalogForCompany($company);

        return view('admin.companies.show', compact('company', 'tenant', 'catalog'));
    }

    public function updateModules(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
        ]);

        $this->modules->applyModules($company, $data['modules'] ?? [], 'admin', true);

        return back()->with('success', 'Modules de l’entreprise mis à jour.');
    }

    public function edit(Company $company): View
    {
        $tenant = SaasTenant::query()->where('company_id', $company->id)->with('currentSubscription')->first();

        return view('admin.companies.edit', [
            'company' => $company,
            'tenant' => $tenant,
            'plans' => SaasPlan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'activity' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'country' => ['nullable', 'string', 'max:120'],
            'city' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', 'in:active,inactive,archived'],
            'saas_plan_id' => ['nullable', 'exists:saas_plans,id'],
        ]);

        $this->platform->updateCompany($company, $data);

        return redirect()->route('admin.companies.show', $company)->with('success', 'Entreprise mise à jour.');
    }

    public function suspend(Request $request, Company $company): RedirectResponse
    {
        $this->platform->suspendCompany($company, $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Entreprise suspendue.');
    }

    public function reactivate(Company $company): RedirectResponse
    {
        $this->platform->reactivateCompany($company);

        return back()->with('success', 'Entreprise réactivée.');
    }

    public function destroy(Company $company): RedirectResponse
    {
        $this->platform->deleteCompany($company);

        return redirect()->route('admin.companies.index')->with('success', 'Entreprise supprimée.');
    }

    public function impersonate(Request $request, Company $company): RedirectResponse
    {
        try {
            $this->platform->startImpersonation($company, $request);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('home')->with('success', 'Connecté en tant que '.$company->name);
    }
}

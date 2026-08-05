<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasTenant;
use App\Services\SaasService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function __construct(private SaasService $saas)
    {
    }

    public function index(Request $request): View
    {
        $tenants = SaasTenant::query()
            ->with(['currentSubscription.plan', 'company', 'domains'])
            ->search($request->string('q')->toString() ?: null)
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->string('status') === 'archived') {
                    $q->whereNotNull('archived_at');
                } else {
                    $q->where('status', $request->string('status'))->whereNull('archived_at');
                }
            })
            ->when(! $request->filled('status'), fn ($q) => $q->whereNull('archived_at'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('superadmin.tenants.index', [
            'tenants' => $tenants,
            'statuses' => SaasTenant::STATUSES,
            'filters' => $request->only(['q', 'status']),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.tenants.create', [
            'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
            'providers' => SaasPayment::PROVIDERS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:2'],
            'city' => ['nullable', 'string', 'max:120'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'provider' => ['required', 'in:stripe,paypal,cmi,manual'],
            'status' => ['required', 'in:trial,active'],
            'provision_company' => ['sometimes', 'boolean'],
        ]);
        $data['provision_company'] = $request->boolean('provision_company');

        $tenant = $this->saas->createTenant($data);

        return redirect()->route('superadmin.tenants.show', $tenant)->with('success', 'Client créé.');
    }

    public function show(SaasTenant $tenant): View
    {
        $tenant->load(['company', 'owner', 'subscriptions.plan', 'licenses', 'payments', 'domains', 'invoices']);

        return view('superadmin.tenants.show', compact('tenant'));
    }

    public function edit(SaasTenant $tenant): View
    {
        return view('superadmin.tenants.edit', [
            'tenant' => $tenant,
            'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, SaasTenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'country' => ['nullable', 'string', 'max:2'],
            'city' => ['nullable', 'string', 'max:120'],
            'primary_domain' => ['nullable', 'string', 'max:255'],
            'storage_used_mb' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->saas->updateTenant($tenant, $data);

        return redirect()->route('superadmin.tenants.show', $tenant)->with('success', 'Client mis à jour.');
    }

    public function suspend(Request $request, SaasTenant $tenant): RedirectResponse
    {
        $this->saas->suspend($tenant, $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Client suspendu.');
    }

    public function reactivate(SaasTenant $tenant): RedirectResponse
    {
        $this->saas->reactivate($tenant);

        return back()->with('success', 'Client réactivé.');
    }

    public function archive(SaasTenant $tenant): RedirectResponse
    {
        $this->saas->archive($tenant);

        return back()->with('success', 'Client archivé.');
    }

    public function destroy(SaasTenant $tenant): RedirectResponse
    {
        $this->saas->logAudit('tenant', 'critical', 'Client supprimé', $tenant->name, $tenant);
        $tenant->delete();

        return redirect()->route('superadmin.tenants.index')->with('success', 'Client supprimé.');
    }
}

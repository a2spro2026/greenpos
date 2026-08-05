<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPlan;
use App\Services\ModuleManagerService;
use App\Services\SaasBillingService;
use App\Services\SaasSubscriptionService;
use App\Support\ModuleCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function __construct(private SaasBillingService $billing)
    {
    }

    public function index(): View
    {
        app(SaasSubscriptionService::class)->syncPlanCatalog();
        $plans = SaasPlan::query()->orderBy('sort_order')->get();

        return view('superadmin.plans.index', [
            'plans' => $plans,
            'modules' => ModuleCatalog::labels(),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.plans.create', [
            'modules' => ModuleCatalog::labels(),
            'supportLevels' => SaasPlan::SUPPORT_LEVELS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['features'] = array_values(array_filter(array_map('trim', explode("\n", $request->input('features', '')))));
        $data['modules'] = $request->input('modules', []);
        $data['api_enabled'] = $request->boolean('api_enabled');
        $data['support_included'] = $request->boolean('support_included');
        $data['backups_enabled'] = $request->boolean('backups_enabled');
        $data['custom_domain_enabled'] = $request->boolean('custom_domain_enabled');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_public'] = $request->boolean('is_public', true);

        $plan = $this->billing->createPlan($data);

        return redirect()->route('superadmin.plans.edit', $plan)->with('success', 'Plan créé.');
    }

    public function edit(SaasPlan $plan): View
    {
        return view('superadmin.plans.edit', [
            'plan' => $plan,
            'modules' => ModuleCatalog::labels(),
            'supportLevels' => SaasPlan::SUPPORT_LEVELS,
        ]);
    }

    public function update(Request $request, SaasPlan $plan): RedirectResponse
    {
        $data = $this->validated($request);

        $plan->update([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_yearly' => $data['price_yearly'],
            'max_users' => $data['max_users'],
            'max_stores' => $data['max_stores'],
            'storage_gb' => $data['storage_gb'],
            'trial_days' => $data['trial_days'] ?? 14,
            'support_level' => $data['support_level'] ?? 'email',
            'features' => array_values(array_filter(array_map('trim', explode("\n", $request->input('features', ''))))),
            'is_active' => $request->boolean('is_active'),
            'is_public' => $request->boolean('is_public'),
            'api_enabled' => $request->boolean('api_enabled'),
            'support_included' => $request->boolean('support_included'),
            'backups_enabled' => $request->boolean('backups_enabled'),
            'custom_domain_enabled' => $request->boolean('custom_domain_enabled'),
        ]);

        app(ModuleManagerService::class)->updatePlanModules($plan->fresh(), $request->input('modules', []));

        return redirect()->route('superadmin.plans.index')->with('success', 'Plan mis à jour.');
    }

    public function toggle(SaasPlan $plan): RedirectResponse
    {
        $plan->update(['is_active' => ! $plan->is_active]);

        return back()->with('success', $plan->is_active ? 'Plan activé.' : 'Plan désactivé.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:64', 'alpha_dash', 'unique:saas_plans,code'],
            'name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_stores' => ['required', 'integer', 'min:1'],
            'storage_gb' => ['required', 'integer', 'min:1'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:90'],
            'support_level' => ['nullable', 'string', 'max:32'],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string'],
            'features' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'api_enabled' => ['sometimes', 'boolean'],
            'support_included' => ['sometimes', 'boolean'],
            'backups_enabled' => ['sometimes', 'boolean'],
            'custom_domain_enabled' => ['sometimes', 'boolean'],
        ]);
    }
}

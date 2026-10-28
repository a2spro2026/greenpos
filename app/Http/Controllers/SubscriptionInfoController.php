<?php

namespace App\Http\Controllers;

use App\Models\CompanyModule;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Support\ModuleCatalog;
use App\Support\Workspace;
use Illuminate\View\View;

/** Page « Abonnement » côté entreprise : formule SaaS en cours + modules activés (lecture seule). */
class SubscriptionInfoController extends Controller
{
    public function show(): View
    {
        $this->authorize('settings.view');
        $company = Workspace::company();
        $tenant = SaasTenant::query()->where('company_id', $company->id)->first();
        $subscriptions = $tenant
            ? SaasSubscription::query()->where('saas_tenant_id', $tenant->id)->with('plan')->latest('starts_at')->get()
            : collect();
        $labels = ModuleCatalog::labels();
        $modules = CompanyModule::query()->where('company_id', $company->id)->where('is_enabled', true)->orderBy('module_key')->get()
            ->map(fn ($m) => ['key' => $m->module_key, 'label' => $labels[$m->module_key] ?? $m->module_key, 'since' => $m->enabled_at]);

        return view('account.subscription', compact('company', 'tenant', 'subscriptions', 'modules'));
    }
}

<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasSubscriptionAlert;
use App\Models\SaasTenant;
use App\Services\SaasBillingService;
use App\Services\SaasSubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private SaasSubscriptionService $subscriptions,
        private SaasBillingService $billing,
    ) {
    }

    public function dashboard(): View
    {
        $this->subscriptions->syncPlanCatalog();
        $this->subscriptions->scanExpiring(14);
        $stats = array_merge($this->subscriptions->dashboardStats(), [
            'billing' => $this->billing->billingDashboard(),
        ]);

        return view('superadmin.subscriptions.dashboard', compact('stats'));
    }

    public function index(Request $request): View
    {
        $subs = SaasSubscription::query()
            ->with(['tenant', 'plan'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('plan_id'), fn ($q) => $q->where('saas_plan_id', $request->integer('plan_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->whereHas('tenant', fn ($t) => $t->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('superadmin.subscriptions.index', [
            'subscriptions' => $subs,
            'statuses' => SaasSubscription::STATUSES,
            'plans' => SaasPlan::query()->orderBy('sort_order')->get(),
            'filters' => $request->only(['status', 'plan_id', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('superadmin.subscriptions.create', [
            'tenants' => SaasTenant::query()->orderBy('name')->get(),
            'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
            'providers' => SaasPayment::PROVIDERS,
            'statuses' => collect(SaasSubscription::STATUSES)->only(['trialing', 'active', 'past_due']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'saas_tenant_id' => ['required', 'exists:saas_tenants,id'],
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'provider' => ['required', 'in:stripe,paypal,cmi,manual'],
            'status' => ['required', 'in:trialing,active,past_due'],
            'auto_renew' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['auto_renew'] = $request->boolean('auto_renew', true);

        $sub = $this->subscriptions->create($data);

        return redirect()->route('superadmin.subscriptions.show', $sub)->with('success', 'Abonnement créé.');
    }

    public function show(SaasSubscription $subscription): View
    {
        $subscription->load(['tenant', 'plan', 'payments', 'licenses', 'alerts' => fn ($q) => $q->latest()->limit(20)]);
        $limits = $subscription->tenant
            ? $this->subscriptions->checkLimits($subscription->tenant)
            : ['ok' => true, 'breaches' => []];
        $entitlements = $subscription->tenant
            ? $this->subscriptions->entitlementsForTenant($subscription->tenant)
            : [];

        return view('superadmin.subscriptions.show', compact('subscription', 'limits', 'entitlements'));
    }

    public function edit(SaasSubscription $subscription): View
    {
        return view('superadmin.subscriptions.edit', [
            'subscription' => $subscription->load(['tenant', 'plan']),
            'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
            'providers' => SaasPayment::PROVIDERS,
        ]);
    }

    public function update(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['required', 'in:monthly,yearly'],
            'provider' => ['required', 'in:stripe,paypal,cmi,manual'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'ends_at' => ['nullable', 'date'],
            'renews_at' => ['nullable', 'date'],
            'auto_renew' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['auto_renew'] = $request->boolean('auto_renew');

        $this->subscriptions->update($subscription, $data);

        return redirect()->route('superadmin.subscriptions.show', $subscription)->with('success', 'Abonnement mis à jour.');
    }

    public function suspend(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $this->subscriptions->suspend($subscription, $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Abonnement suspendu.');
    }

    public function reactivate(SaasSubscription $subscription): RedirectResponse
    {
        $this->subscriptions->reactivate($subscription);

        return back()->with('success', 'Abonnement réactivé.');
    }

    public function renew(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $provider = $request->string('provider')->toString() ?: null;
        $this->subscriptions->renew($subscription, $provider ?: null);

        return back()->with('success', 'Abonnement renouvelé et facturé.');
    }

    public function cancel(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $this->subscriptions->cancel($subscription, $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Abonnement résilié.');
    }

    public function markPastDue(SaasSubscription $subscription): RedirectResponse
    {
        $this->subscriptions->markPastDue($subscription);

        return back()->with('success', 'Statut « en attente de paiement » appliqué.');
    }

    public function alerts(): View
    {
        $this->subscriptions->scanExpiring(14);

        $alerts = SaasSubscriptionAlert::query()
            ->with(['tenant', 'subscription.plan'])
            ->latest()
            ->paginate(30);

        return view('superadmin.subscriptions.alerts', compact('alerts'));
    }

    public function markAlertRead(SaasSubscriptionAlert $alert): RedirectResponse
    {
        $this->subscriptions->markAlertRead($alert);

        return back()->with('success', 'Alerte marquée comme lue.');
    }

    public function changePlanForm(SaasSubscription $subscription): View
    {
        return view('superadmin.subscriptions.change-plan', [
            'subscription' => $subscription->load(['tenant', 'plan']),
            'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function upgrade(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $this->billing->upgrade($subscription, (int) $data['saas_plan_id'], $data['billing_cycle'] ?? null);

        return redirect()->route('superadmin.subscriptions.show', $subscription)->with('success', 'Montée en gamme effectuée.');
    }

    public function downgrade(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        $data = $request->validate([
            'saas_plan_id' => ['required', 'exists:saas_plans,id'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
        ]);

        $this->billing->downgrade($subscription, (int) $data['saas_plan_id'], $data['billing_cycle'] ?? null);

        return redirect()->route('superadmin.subscriptions.show', $subscription)->with('success', 'Descente de gamme effectuée.');
    }

    public function convertTrial(Request $request, SaasSubscription $subscription): RedirectResponse
    {
        if ($subscription->status !== 'trialing') {
            return back()->with('error', 'Cet abonnement n’est pas en essai.');
        }

        $provider = $request->string('provider')->toString() ?: null;
        $this->billing->convertTrial($subscription, $provider ?: null);

        return back()->with('success', 'Essai converti en abonnement payant.');
    }

    public function issueInvoice(SaasSubscription $subscription): RedirectResponse
    {
        $invoice = $this->billing->issueInvoice($subscription);

        return redirect()->route('superadmin.invoices.show', $invoice)->with('success', 'Facture émise.');
    }
}

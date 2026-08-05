<?php

/**
 * Smoke test SaaS Billing module.
 * Usage: php scripts/smoke_billing.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Billing\PaymentGatewayManager;
use App\Models\SaasInvoice;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\User;
use App\Services\SaasBillingService;
use App\Services\SaasSubscriptionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

$ok = 0;
$fail = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $ok, $fail;
    if ($cond) {
        echo "  OK  {$msg}\n";
        $ok++;
    } else {
        echo " FAIL {$msg}\n";
        $fail++;
    }
}

echo "=== SaaS Billing smoke ===\n";

assertTrue(Schema::hasTable('saas_payment_gateways'), 'table saas_payment_gateways');
assertTrue(Schema::hasColumn('saas_subscriptions', 'trial_ends_at'), 'trial_ends_at');
assertTrue(Schema::hasColumn('saas_subscriptions', 'converted_at'), 'converted_at');
assertTrue(Schema::hasColumn('saas_invoices', 'paid_at'), 'invoice paid_at');

$admin = User::query()->where('is_platform_admin', true)->first()
    ?? User::query()->where('email', 'admin@greenpos.test')->first();
assertTrue((bool) $admin, 'admin user');
auth()->login($admin);
view()->share('superAdminUser', $admin);

$gateways = app(PaymentGatewayManager::class);
$gateways->ensureDefaults();
assertTrue(count($gateways->statusBoard()) === 4, '4 payment gateways');

$billing = app(SaasBillingService::class);
$subs = app(SaasSubscriptionService::class);
$subs->syncPlanCatalog();

$stats = $billing->billingDashboard();
foreach (['mrr', 'arr', 'revenue_month', 'renewals', 'active', 'expired', 'trials', 'conversion_rate'] as $k) {
    assertTrue(array_key_exists($k, $stats), "dashboard key {$k}");
}

$routes = [
    'superadmin.billing.dashboard',
    'superadmin.billing.gateways',
    'superadmin.invoices.index',
    'superadmin.invoices.create',
    'superadmin.plans.create',
    'superadmin.subscriptions.change-plan',
];
foreach ($routes as $r) {
    assertTrue(Route::has($r), "route {$r}");
}

$sub = SaasSubscription::query()->with('plan')->where('status', 'trialing')->first()
    ?? SaasSubscription::query()->with('plan')->first();
assertTrue((bool) $sub, 'subscription exists');

if ($sub) {
    $invoice = $billing->issueInvoice($sub);
    assertTrue($invoice instanceof SaasInvoice, 'issue invoice');
    assertTrue($invoice->total > 0, 'invoice total > 0');

    $html = view('superadmin.invoices.pdf', ['invoice' => $invoice->load(['tenant', 'subscription.plan', 'payment'])])->render();
    assertTrue(str_contains($html, $invoice->number), 'invoice pdf renders');

    $plans = SaasPlan::query()->orderBy('sort_order')->get();
    $higher = $plans->first(fn ($p) => $p->sort_order > ($sub->plan?->sort_order ?? 0));
    if ($higher && $sub->status !== 'cancelled') {
        $billing->upgrade($sub->fresh(), $higher->id);
        assertTrue($sub->fresh()->saas_plan_id === $higher->id, 'upgrade plan');
    }

    if ($sub->fresh()->status === 'trialing') {
        $billing->convertTrial($sub->fresh(), 'manual');
        assertTrue($sub->fresh()->status === 'active', 'convert trial');
        assertTrue($sub->fresh()->converted_at !== null, 'converted_at set');
    }
}

$view = view('superadmin.billing.dashboard', ['stats' => $stats])->render();
assertTrue(str_contains($view, 'MRR'), 'billing dashboard view');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

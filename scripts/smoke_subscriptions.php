<?php

/**
 * Smoke test SaaS subscriptions module (service + views, no credential output).
 */

use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasSubscriptionAlert;
use App\Models\SaasTenant;
use App\Services\SaasSubscriptionService;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = $app->make(SaasSubscriptionService::class);
$svc->syncPlanCatalog();

$codes = SaasPlan::query()->orderBy('sort_order')->pluck('code')->all();
$expected = ['starter', 'standard', 'professional', 'enterprise'];
if ($codes !== $expected) {
    fwrite(STDERR, 'FAIL plans: '.implode(',', $codes)."\n");
    exit(1);
}
echo "OK   plans catalog\n";

$stats = $svc->dashboardStats();
foreach (['total', 'new', 'renewals', 'cancellations', 'mrr', 'arr', 'renewal_rate', 'by_month', 'alerts'] as $k) {
    if (! array_key_exists($k, $stats)) {
        fwrite(STDERR, "FAIL missing stat $k\n");
        exit(1);
    }
}
echo "OK   dashboard stats\n";

$admin = \App\Models\User::query()->where('is_platform_admin', true)->first();
if (! $admin) {
    fwrite(STDERR, "FAIL no platform admin\n");
    exit(1);
}
view()->share('superAdminUser', $admin);

view('superadmin.subscriptions.dashboard', compact('stats'))->render();
echo "OK   view dashboard\n";

$subscriptions = SaasSubscription::query()->with(['tenant', 'plan'])->latest()->paginate(25);
view('superadmin.subscriptions.index', [
    'subscriptions' => $subscriptions,
    'statuses' => SaasSubscription::STATUSES,
    'plans' => SaasPlan::query()->orderBy('sort_order')->get(),
    'filters' => [],
])->render();
echo "OK   view index\n";

view('superadmin.subscriptions.create', [
    'tenants' => SaasTenant::query()->orderBy('name')->get(),
    'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
    'providers' => \App\Models\SaasPayment::PROVIDERS,
    'statuses' => collect(SaasSubscription::STATUSES)->only(['trialing', 'active', 'past_due']),
])->render();
echo "OK   view create\n";

$alerts = SaasSubscriptionAlert::query()->with(['tenant', 'subscription.plan'])->latest()->paginate(30);
view('superadmin.subscriptions.alerts', compact('alerts'))->render();
echo "OK   view alerts\n";

$sub = SaasSubscription::query()->with(['tenant', 'plan', 'payments', 'licenses', 'alerts'])->first();
if ($sub) {
    $limits = $sub->tenant ? $svc->checkLimits($sub->tenant) : ['ok' => true, 'breaches' => []];
    $entitlements = $sub->tenant ? $svc->entitlementsForTenant($sub->tenant) : [];
    view('superadmin.subscriptions.show', [
        'subscription' => $sub,
        'limits' => $limits,
        'entitlements' => $entitlements,
    ])->render();
    echo "OK   view show\n";

    view('superadmin.subscriptions.edit', [
        'subscription' => $sub,
        'plans' => SaasPlan::query()->active()->orderBy('sort_order')->get(),
        'providers' => \App\Models\SaasPayment::PROVIDERS,
    ])->render();
    echo "OK   view edit\n";
} else {
    echo "SKIP show/edit (no subscription)\n";
}

$tenant = SaasTenant::query()->first();
$plan = SaasPlan::query()->where('code', 'starter')->first();
if ($tenant && $plan) {
    $created = $svc->create([
        'saas_tenant_id' => $tenant->id,
        'saas_plan_id' => $plan->id,
        'billing_cycle' => 'monthly',
        'provider' => 'manual',
        'status' => 'trialing',
        'auto_renew' => true,
        'notes' => 'smoke-test',
    ]);
    $svc->suspend($created, 'smoke');
    $svc->reactivate($created);
    $svc->markPastDue($created);
    $svc->renew($created, 'manual');
    $svc->cancel($created, 'smoke cleanup');
    $svc->checkLimits($tenant);
    $svc->entitlementsForTenant($tenant);
    $svc->scanExpiring(14);
    echo "OK   lifecycle + entitlements\n";
} else {
    echo "SKIP lifecycle (no tenant)\n";
}

$routes = [
    'superadmin.subscriptions.dashboard',
    'superadmin.subscriptions.index',
    'superadmin.subscriptions.create',
    'superadmin.subscriptions.alerts',
    'superadmin.subscriptions.show',
    'superadmin.subscriptions.edit',
    'superadmin.subscriptions.store',
    'superadmin.subscriptions.update',
    'superadmin.subscriptions.suspend',
    'superadmin.subscriptions.reactivate',
    'superadmin.subscriptions.renew',
    'superadmin.subscriptions.cancel',
    'superadmin.subscriptions.past-due',
    'superadmin.subscriptions.alerts.read',
];
foreach ($routes as $name) {
    if (! app('router')->has($name)) {
        fwrite(STDERR, "FAIL missing route $name\n");
        exit(1);
    }
}
echo 'OK   '.count($routes)." named routes\n";

echo "PASS subscriptions module\n";
exit(0);

<?php

/**
 * Smoke test Super Admin Enterprise (CLI, no HTTP auth).
 * Usage: php scripts/smoke_superadmin.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\SaasLicense;
use App\Models\SaasPlan;
use App\Models\SaasPlatformSnapshot;
use App\Models\SaasTenant;
use App\Models\User;
use App\Services\SaasService;
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

echo "=== Super Admin Enterprise smoke ===\n";

assertTrue(Schema::hasTable('saas_tenants'), 'table saas_tenants');
assertTrue(Schema::hasColumn('saas_tenants', 'archived_at'), 'column archived_at');
assertTrue(Schema::hasColumn('saas_tenants', 'storage_used_mb'), 'column storage_used_mb');
assertTrue(Schema::hasTable('saas_audit_events'), 'table saas_audit_events');
assertTrue(Schema::hasColumn('saas_platform_snapshots', 'meta'), 'snapshot meta column');

$saas = app(SaasService::class);
$saas->ensurePlans();

$plans = SaasPlan::query()->orderBy('sort_order')->pluck('name', 'code');
assertTrue($plans->has('starter'), 'plan Starter');
assertTrue(($plans['standard'] ?? '') === 'Business', 'plan Business (code standard)');
assertTrue($plans->has('professional'), 'plan Professional');
assertTrue($plans->has('enterprise'), 'plan Enterprise');

$admin = User::query()->where('is_platform_admin', true)->first()
    ?? User::query()->where('email', 'admin@greenpos.test')->first();

if ($admin && ! $admin->is_platform_admin) {
    $admin->update(['is_platform_admin' => true]);
}

assertTrue((bool) $admin, 'platform admin user exists');

auth()->login($admin);
view()->share('superAdminUser', $admin);
$saas->seedDemo($admin->id);
$saas->seedJournalIfEmpty();

$stats = $saas->dashboardStats();
assertTrue(isset($stats['clients'], $stats['mrr'], $stats['arr'], $stats['growth_monthly'], $stats['total_stores']), 'dashboard KPIs');
assertTrue(isset($stats['revenue_by_month'], $stats['by_plan'], $stats['clients_by_month']), 'dashboard chart series');

$snap = $saas->capturePlatformSnapshot();
assertTrue($snap instanceof SaasPlatformSnapshot, 'platform snapshot');
assertTrue(isset($snap->meta['response_ms'], $snap->meta['uptime']), 'snapshot response/uptime meta');

$tenant = SaasTenant::query()->first();
assertTrue((bool) $tenant, 'demo tenant exists');
assertTrue(is_string($tenant->domainLabel()), 'tenant domainLabel');
assertTrue(is_string($tenant->storageLabel()), 'tenant storageLabel');

$routes = [
    'superadmin.dashboard',
    'superadmin.tenants.index',
    'superadmin.tenants.create',
    'superadmin.plans.index',
    'superadmin.licenses.index',
    'superadmin.monitoring.index',
    'superadmin.journal.index',
];
foreach ($routes as $name) {
    assertTrue(Route::has($name), "route {$name}");
}

$view = view('superadmin.dashboard', ['stats' => $stats])->render();
assertTrue(str_contains($view, 'Dashboard Executive'), 'dashboard view renders');

$mon = view('superadmin.monitoring.index', [
    'latest' => $snap,
    'history' => collect([['label' => 'now', 'cpu_percent' => 10, 'memory_percent' => 20, 'disk_percent' => 30]]),
])->render();
assertTrue(str_contains($mon, 'CPU'), 'monitoring view renders');

$licCount = SaasLicense::query()->count();
assertTrue($licCount >= 0, "licenses count={$licCount}");

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

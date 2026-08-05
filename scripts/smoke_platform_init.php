<?php

/**
 * Smoke — Platform init + Super Admin login readiness.
 * Usage: php scripts/smoke_platform_init.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Models\User;
use App\Services\PlatformBootstrapService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

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

echo "=== Platform init smoke ===\n";

$boot = app(PlatformBootstrapService::class)->ensureReady();

$admin = User::query()->where('email', PlatformBootstrapService::SUPER_ADMIN_EMAIL)->first();
assertTrue((bool) $admin, 'superadmin exists');
assertTrue((bool) $admin?->is_platform_admin, 'is_platform_admin');
assertTrue(Hash::check(PlatformBootstrapService::SUPER_ADMIN_PASSWORD, $admin->password), 'password Super Admin ok');

$platform = Company::query()->where('name', PlatformBootstrapService::PLATFORM_COMPANY_NAME)->first();
assertTrue((bool) $platform, 'GreenPOS company');
assertTrue($admin->companies()->where('companies.id', $platform->id)->exists(), 'superadmin linked to GreenPOS');
assertTrue($platform->stores()->where('is_default', true)->exists(), 'main store');

$demo = Company::query()->where('name', PlatformBootstrapService::DEMO_COMPANY_NAME)->first();
assertTrue((bool) $demo, 'demo company');

$plan = SaasPlan::query()->where('code', 'enterprise')->first();
assertTrue((bool) $plan, 'enterprise plan');

$tenant = SaasTenant::query()->where('company_id', $platform->id)->first();
assertTrue((bool) $tenant, 'platform tenant');
$sub = SaasSubscription::query()->where('saas_tenant_id', $tenant->id)->where('status', 'active')->first();
assertTrue((bool) $sub && (int) $sub->saas_plan_id === (int) $plan->id, 'active enterprise subscription');

$modules = CompanyModule::query()->where('company_id', $platform->id)->where('is_enabled', true)->count();
assertTrue($modules >= 10, "modules enabled ({$modules})");

assertTrue(Route::has('admin.login'), 'admin.login');
assertTrue(Route::has('admin.dashboard'), 'admin.dashboard');

$src = file_get_contents(base_path('app/Http/Middleware/EnsureWorkspace.php'));
assertTrue(! str_contains($src, 'Lancez les seeders'), 'old seeder abort removed');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

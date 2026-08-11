<?php

/**
 * Smoke — Platform Super Admin (/admin).
 * Usage: php scripts/smoke_admin.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\SaasPlan;
use App\Models\SaasTenant;
use App\Models\Store;
use App\Models\User;
use App\Services\PlatformAdminService;
use App\Services\SaasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

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

echo "=== Platform Admin smoke ===\n";

assertTrue(Route::has('admin.login'), 'route admin.login');
assertTrue(Route::has('admin.dashboard'), 'route admin.dashboard');
assertTrue(Route::has('admin.companies.create'), 'route companies.create');
assertTrue(Route::has('admin.companies.impersonate'), 'route impersonate');
assertTrue(Route::has('admin.stores.index'), 'route stores');
assertTrue(Route::has('admin.plans.index'), 'route plans');
assertTrue(Route::has('admin.subscriptions.index'), 'route subscriptions');
assertTrue(Route::has('admin.payments.index'), 'route payments');
assertTrue(Route::has('admin.users.index'), 'route users');
assertTrue(Route::has('admin.modules.index'), 'route modules');
assertTrue(Route::has('admin.journal.index'), 'route journal');
assertTrue(Route::has('admin.settings.edit'), 'route settings');
assertTrue(Route::has('admin.leave-impersonation'), 'route leave impersonation');

$admin = User::query()->where('email', 'admin@greenpos.test')->first();
assertTrue((bool) $admin, 'demo super admin exists');
assertTrue((bool) $admin?->is_platform_admin, 'demo is_platform_admin');

app(SaasService::class)->ensurePlans();
$plan = SaasPlan::query()->where('is_active', true)->orderBy('sort_order')->first();
assertTrue((bool) $plan, 'active plan exists');

$svc = app(PlatformAdminService::class);
$stats = $svc->dashboardKpis();
assertTrue(isset($stats['companies_total']), 'kpi companies_total');
assertTrue(isset($stats['stores_total']), 'kpi stores_total');
assertTrue(isset($stats['users_total']), 'kpi users_total');
assertTrue(isset($stats['active_subscriptions']), 'kpi active_subscriptions');
assertTrue(isset($stats['platform_revenue']), 'kpi platform_revenue');
assertTrue(isset($stats['trial_companies']), 'kpi trial_companies');
assertTrue(isset($stats['suspended_companies']), 'kpi suspended_companies');

$email = 'smoke-admin-'.Str::lower(Str::random(6)).'@greenpos.test';
$result = $svc->provisionCompany([
    'name' => 'Smoke Corp '.Str::upper(Str::random(4)),
    'activity' => 'Retail',
    'owner_name' => 'Owner Smoke',
    'email' => $email,
    'phone' => '0600000000',
    'address' => '1 Rue Test',
    'country' => 'Maroc',
    'city' => 'Casablanca',
    'saas_plan_id' => $plan->id,
    'password' => 'Password123!',
]);

$company = $result['company'];
$store = $result['store'];
$user = $result['user'];
$tenant = $result['tenant'];

assertTrue($company instanceof Company, 'company created');
assertTrue($store instanceof Store && $store->is_default, 'main store created');
assertTrue($user instanceof User && $user->email === $email, 'admin user created');
assertTrue(Hash::check('Password123!', $user->fresh()->password), 'password hashed');
assertTrue($tenant instanceof SaasTenant && (int) $tenant->company_id === (int) $company->id, 'tenant linked');
assertTrue($user->companies()->where('companies.id', $company->id)->exists(), 'user attached to company');

$modules = CompanyModule::query()->where('company_id', $company->id)->where('is_enabled', true)->count();
assertTrue($modules > 0, 'shell modules synced ('.$modules.')');
assertTrue($company->needsModuleSetup(), 'new company waits for module setup');

$svc->suspendCompany($company, 'smoke');
assertTrue($company->fresh()->status === 'inactive', 'company suspended');
assertTrue(SaasTenant::query()->where('company_id', $company->id)->value('status') === 'suspended', 'tenant suspended');

$svc->reactivateCompany($company);
assertTrue($company->fresh()->status === 'active', 'company reactivated');

Auth::login($admin);
$request = Request::create('/');
$request->setLaravelSession(app('session')->driver());
app('session')->start();
$as = $svc->startImpersonation($company, $request);
assertTrue((int) $as->id === (int) $user->id, 'impersonation switches to owner');
assertTrue((int) $request->session()->get('admin_impersonator_id') === (int) $admin->id, 'impersonator stored');

$restored = $svc->stopImpersonation($request);
assertTrue((int) ($restored?->id) === (int) $admin->id, 'stop impersonation restores admin');
assertTrue(! $request->session()->has('admin_impersonator_id'), 'impersonation cleared');

$settings = $svc->savePlatformSettings([
    'platform_name' => 'GreenPOS Smoke',
    'support_email' => 'ops@greenpos.test',
    'default_trial_days' => 10,
    'default_currency' => 'MAD',
    'maintenance_mode' => false,
    'allow_self_signup' => true,
]);
assertTrue(($settings['platform_name'] ?? '') === 'GreenPOS Smoke', 'platform settings saved');

$viewFiles = [
    'resources/views/admin/auth/login.blade.php',
    'resources/views/admin/dashboard.blade.php',
    'resources/views/admin/companies/create.blade.php',
    'resources/views/layouts/admin.blade.php',
    'resources/css/admin.css',
];
foreach ($viewFiles as $f) {
    assertTrue(is_file(base_path($f)), 'asset '.$f);
}

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

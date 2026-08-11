<?php

/**
 * Smoke — Module Manager: plan → modules → sidebar → permissions.
 * Usage: php scripts/smoke_modules.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\SaasPlan;
use App\Models\User;
use App\Services\ModuleManagerService;
use App\Support\ModuleCatalog;
use App\Support\Workspace;
use Illuminate\Support\Facades\Auth;
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

echo "=== Module Manager smoke ===\n";

assertTrue(Schema::hasTable('company_modules'), 'table company_modules');
assertTrue(count(ModuleCatalog::keys()) >= 20, 'catalog size');
assertTrue(Route::has('modules.index'), 'route modules.index');
assertTrue(Route::has('modules.setup'), 'route modules.setup');
assertTrue(Route::has('superadmin.modules.index'), 'route superadmin.modules.index');

$mm = app(ModuleManagerService::class);
$mm->bootstrapPlans();

$starter = SaasPlan::query()->where('code', 'starter')->first();
assertTrue((bool) $starter, 'starter plan');
$starterMods = ModuleCatalog::defaultModulesForPlan('starter');
$mm->updatePlanModules($starter, $starterMods);
$starter = $starter->fresh();
assertTrue(in_array('products', $starter->modules ?? [], true), 'starter has products');
assertTrue(! in_array('crm', $starter->modules ?? [], true), 'starter no crm');

$user = User::query()->where('email', 'admin@greenpos.test')->first() ?? User::query()->first();
Auth::login($user);
$company = $user->companies()->first() ?? Company::query()->first();
Workspace::set($company, $company->stores()->first());

// Force sync as starter
$mm->syncCompanyFromPlan($company, $starter);
assertTrue($mm->isEnabled('products', $company), 'products enabled');
assertTrue($mm->isEnabled('pos', $company), 'pos enabled');
assertTrue(! $mm->isEnabled('crm', $company), 'crm disabled on starter');
assertTrue($mm->isEnabled('dashboard', $company), 'dashboard always on');

assertTrue(Workspace::can('products.view'), 'RBAC+module products');
assertTrue(! Workspace::can('purchases.view') || in_array('purchases', $starter->modules ?? [], true), 'purchases gated by plan');

// purchases not in starter defaults
assertTrue(! $mm->isEnabled('purchases', $company), 'purchases off on starter');
assertTrue(! Workspace::can('purchases.view'), 'cannot purchases.view without module');

$nav = $mm->sidebarNav();
$labels = collect($nav)->flatMap(fn ($g) => collect($g['items'])->pluck('label'))->all();
assertTrue(in_array('Produits', $labels, true), 'sidebar has Produits');
assertTrue(! in_array('CRM Enterprise', $labels, true), 'sidebar hides CRM');
assertTrue(in_array('Tableau de bord', $labels, true), 'sidebar has core');
assertTrue(! in_array('Catalogue des Modules', $labels, true), 'sidebar hides module store');
assertTrue(Route::has('modules.show'), 'route modules.show');
assertTrue(Route::has('modules.toggle'), 'route modules.toggle');

// Enterprise unlocks all
$ent = SaasPlan::query()->where('code', 'enterprise')->first();
$mm->updatePlanModules($ent, ModuleCatalog::defaultModulesForPlan('enterprise'));
$mm->syncCompanyFromPlan($company, $ent->fresh());
assertTrue($mm->isEnabled('crm', $company), 'crm on enterprise');

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));
$html = view('partials.module-sidebar')->render();
assertTrue(str_contains($html, 'gp-nav-group'), 'sidebar partial renders');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

<?php

/**
 * GreenPOS RC1 smoke checks — run: php scripts/rc1_smoke.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Support\PermissionCatalog;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$failures = [];
$ok = 0;

function check(bool $cond, string $label, array &$failures, int &$ok): void
{
    if ($cond) {
        echo "[OK] {$label}\n";
        $ok++;
    } else {
        echo "[FAIL] {$label}\n";
        $failures[] = $label;
    }
}

$http = $app->make(Illuminate\Contracts\Http\Kernel::class);

$routes = [
    'home', 'products.index', 'stock.dashboard', 'purchases.dashboard', 'suppliers.dashboard',
    'customers.dashboard', 'pos.dashboard', 'sales.dashboard', 'invoices.dashboard', 'quotes.dashboard',
    'reports.dashboard', 'users.dashboard', 'roles.dashboard', 'settings.index', 'stores.dashboard',
    'companies.dashboard', 'notifications.dashboard', 'documents.dashboard', 'audit.dashboard',
];

foreach ($routes as $name) {
    check(Route::has($name), "route:{$name}", $failures, $ok);
    $url = route($name, [], false);
    $response = $http->handle(Request::create($url, 'GET'));
    $code = $response->getStatusCode();
    check(in_array($code, [200, 302], true), "http:{$name} => {$code}", $failures, $ok);
    $http->terminate(Request::create($url, 'GET'), $response);
}

// Permission matrix per role
$roles = PermissionCatalog::defaultRolePermissions();
foreach (['owner', 'manager', 'cashier', 'sales', 'accountant', 'storekeeper'] as $role) {
    check(isset($roles[$role]), "role_defined:{$role}", $failures, $ok);
}

$matrix = [
    'cashier' => ['pos.sell' => true, 'settings.update' => false, 'audit.purge' => false, 'products.view' => true],
    'sales' => ['customers.view' => true, 'quotes.create' => true, 'stock.inventory' => false],
    'accountant' => ['reports.financial' => true, 'invoices.view' => true, 'pos.sell' => false],
    'storekeeper' => ['stock.view' => true, 'stock.inventory' => true, 'sales.create' => false],
    'manager' => ['products.create' => true, 'audit.view' => true, 'companies.archive' => false],
];

$user = User::where('email', 'admin@greenpos.test')->first();
check((bool) $user, 'demo_user_exists', $failures, $ok);

if ($user) {
    Auth::login($user);
    $company = $user->companies()->first();
    if ($company) {
        Workspace::set($company, $company->stores()->first());
    }

    foreach ($matrix as $role => $perms) {
        // Simulate role by checking catalog defaults rather than mutating membership
        $keys = $roles[$role] ?? [];
        foreach ($perms as $perm => $expected) {
            $has = in_array($perm, $keys, true);
            check($has === $expected, "perm:{$role}:{$perm}=".($expected ? 'allow' : 'deny'), $failures, $ok);
        }
    }
}

// Design system classes present in CSS
$css = file_get_contents(__DIR__.'/../resources/css/app.css');
foreach (['gp-input', 'gp-select', 'gp-table', 'gp-empty', 'gp-flash', 'gp-skeleton', 'gp-loader', 'gp-btn-danger'] as $cls) {
    check(str_contains($css, '.'.$cls), "css:{$cls}", $failures, $ok);
}

// Dead nav hrefs should be gone from layout
$layout = file_get_contents(__DIR__.'/../resources/views/layouts/app.blade.php');
check(! preg_match('/href="#"/', $layout), 'layout_no_dead_href', $failures, $ok);
check(str_contains($layout, 'RC1'), 'layout_version_rc1', $failures, $ok);

echo "\n---\nPassed: {$ok} | Failed: ".count($failures)."\n";
if ($failures) {
    echo "Failures:\n - ".implode("\n - ", $failures)."\n";
    exit(1);
}

echo "RC1 smoke OK\n";
exit(0);

<?php

/**
 * Smoke — reset démo + thème clair/sombre.
 * Usage: php scripts/smoke_reset_theme.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\SaasPlan;
use App\Models\User;
use App\Services\PlatformBootstrapService;
use App\Services\PlatformResetService;
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

echo "=== Reset + theme smoke ===\n";

$result = app(PlatformResetService::class)->reset();
assertTrue($result['companies_left'] === 0, '0 companies after reset');
assertTrue($result['users_left'] === 1, '1 user after reset');

$admin = User::query()->where('email', PlatformBootstrapService::SUPER_ADMIN_EMAIL)->first();
assertTrue((bool) $admin?->is_platform_admin, 'superadmin kept');
assertTrue(Hash::check(PlatformBootstrapService::SUPER_ADMIN_PASSWORD, $admin->password), 'password Super Admin ok');
assertTrue(User::query()->where('email', 'admin@greenpos.test')->doesntExist(), 'demo user removed');
assertTrue(Company::query()->count() === 0, 'companies empty');
assertTrue(SaasPlan::query()->count() >= 4, 'plans kept');

assertTrue(Route::has('admin.dashboard'), 'admin dashboard route');

$js = file_get_contents(base_path('resources/js/app.js'));
assertTrue(str_contains($js, "THEME_KEY = 'gp-theme'"), 'theme localStorage key');
assertTrue(str_contains($js, "current === 'light' ? 'dark' : 'light'"), 'binary theme toggle');

$adminLayout = file_get_contents(base_path('resources/views/layouts/admin.blade.php'));
assertTrue(str_contains($adminLayout, 'data-theme-toggle'), 'admin theme toggle');
assertTrue(! str_contains($adminLayout, 'class="dark"'), 'admin html not forced dark');

$appLayout = file_get_contents(base_path('resources/views/layouts/app.blade.php'));
assertTrue(str_contains($appLayout, 'data-theme-toggle'), 'erp theme toggle');
assertTrue(str_contains($appLayout, 'gp-theme-user'), 'user preference respected');

$pos = file_get_contents(base_path('resources/views/pos/terminal.blade.php'));
assertTrue(str_contains($pos, 'data-theme-toggle'), 'pos theme toggle');

$css = file_get_contents(base_path('resources/css/admin.css'));
assertTrue(str_contains($css, 'html.dark .pa-body'), 'admin dark styles');
assertTrue(str_contains($css, '.pa-body {'), 'admin light styles');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

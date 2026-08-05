<?php

/**
 * Smoke — Console Super Admin vs ERP separation.
 * Usage: php scripts/smoke_console_separation.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\User;
use App\Services\PlatformAdminService;
use App\Services\PlatformBootstrapService;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

function makeRequest(string $uri, $session): Request
{
    $request = Request::create($uri, 'GET');
    $request->setLaravelSession($session);

    return $request;
}

echo "=== Console / ERP separation smoke ===\n";

app(PlatformBootstrapService::class)->ensureReady();

assertTrue(Route::has('admin.dashboard'), 'route admin.dashboard');
assertTrue(str_contains(route('admin.dashboard', [], false), '/admin/dashboard'), 'path /admin/dashboard');

$admin = User::query()->where('email', PlatformBootstrapService::SUPER_ADMIN_EMAIL)->first();
assertTrue((bool) $admin?->is_platform_admin, 'superadmin flag');
assertTrue(Hash::check(PlatformBootstrapService::SUPER_ADMIN_PASSWORD, $admin->password), 'password ok');

$session = app('session')->driver();
$session->start();
$session->forget([
    'admin_impersonator_id', 'admin_impersonating_company_id', 'admin_impersonating_company_name',
    'workspace_company_id', 'workspace_store_id', 'workspace_store_filter',
]);

Auth::login($admin);

$mw = app(\App\Http\Middleware\EnsureWorkspace::class);
$erpRequest = makeRequest('/', $session);
$response = $mw->handle($erpRequest, fn () => response('erp-ok'));

assertTrue($response->isRedirect(), 'ERP / redirects away for platform admin');
assertTrue(str_contains($response->headers->get('Location') ?? '', '/admin'), 'redirect target is /admin');

$demo = Company::query()->where('name', PlatformBootstrapService::DEMO_COMPANY_NAME)->first();
assertTrue((bool) $demo, 'demo company');

$impersonateRequest = makeRequest('/admin/companies/'.$demo->id.'/impersonate', $session);
$svc = app(PlatformAdminService::class);
Auth::login($admin);
$as = $svc->startImpersonation($demo, $impersonateRequest);

assertTrue(! $as->is_platform_admin, 'impersonates non-platform owner');
assertTrue((int) $impersonateRequest->session()->get('admin_impersonating_company_id') === (int) $demo->id, 'impersonation company set');
assertTrue((int) Workspace::company()?->id === (int) $demo->id, 'ERP workspace = demo company');

Auth::login($as);
$erpOkReq = makeRequest('/', $impersonateRequest->session());
$response2 = $mw->handle($erpOkReq, fn () => response('erp-ok'));
assertTrue($response2->getContent() === 'erp-ok', 'ERP allowed during impersonation');

$restored = $svc->stopImpersonation($impersonateRequest);
assertTrue((int) $restored?->id === (int) $admin->id, 'back to superadmin');
assertTrue(! $impersonateRequest->session()->has('admin_impersonator_id'), 'impersonation cleared');
Auth::login($admin);
assertTrue($admin->companies()->count() === 0, 'superadmin has no ERP companies');
assertTrue(Workspace::company() === null, 'no ERP workspace for superadmin');

$src = file_get_contents(base_path('app/Http/Middleware/EnsureWorkspace.php'));
assertTrue(str_contains($src, 'is_platform_admin') && str_contains($src, 'admin_impersonator_id'), 'workspace guards platform admin');

$layout = file_get_contents(base_path('resources/views/layouts/app.blade.php'));
assertTrue(str_contains($layout, 'Retour à la Console GreenPOS'), 'return to console button');

$adminLayout = file_get_contents(base_path('resources/views/layouts/admin.blade.php'));
assertTrue(str_contains($adminLayout, 'Console Super Admin'), 'admin chrome label');
assertTrue(! preg_match('/\bPOS\b/', $adminLayout), 'no POS in admin menu');
assertTrue(str_contains($adminLayout, 'Entreprises'), 'companies menu');
assertTrue(str_contains($adminLayout, 'Utilisateurs plateforme'), 'platform users menu');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

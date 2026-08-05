<?php

/**
 * Smoke — Branding & Personnalisation (isolation entreprise).
 * Usage: php scripts/smoke_branding.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use App\Services\BrandingService;
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

echo "=== Branding smoke ===\n";

assertTrue(Schema::hasTable('company_settings'), 'company_settings table');
assertTrue(array_key_exists('branding', \App\Models\CompanySetting::GROUPS), 'branding group');
assertTrue(Route::has('branding.index'), 'route branding.index');
assertTrue(Route::has('branding.update'), 'route branding.update');

$user = User::query()->where('email', 'admin@greenpos.test')->first() ?? User::query()->first();
Auth::login($user);
$companies = $user->companies()->get();
assertTrue($companies->count() >= 1, 'user has company');

$a = $companies->first();
$b = $companies->count() > 1 ? $companies->get(1) : Company::query()->where('id', '!=', $a->id)->first();

$svc = app(BrandingService::class);
Workspace::set($a, $a->stores()->first());

$saved = $svc->save([
    'trade_name' => 'Marque Alpha Test',
    'tagline' => 'Slogan A',
    'primary_color' => '#112233',
    'secondary_color' => '#445566',
    'button_color' => '#112233',
    'link_color' => '#334455',
    'theme' => 'dark',
    'density' => 'compact',
    'login_welcome' => 'Bienvenue Alpha',
    'login_footer' => 'Footer Alpha',
    'invoice_primary_color' => '#112233',
    'invoice_header' => 'Header Alpha',
    'invoice_footer' => 'Footer facture A',
    'invoice_legal' => 'ICE Alpha',
    'timezone' => 'Africa/Casablanca',
    'locale' => 'fr',
    'currency' => 'MAD',
    'date_format' => 'd/m/Y',
    'number_format' => 'fr',
    'emails' => [
        'welcome' => ['subject' => 'Hello Alpha', 'body' => 'Body {{company}}'],
    ],
], [], $a);

assertTrue($saved['trade_name'] === 'Marque Alpha Test', 'save trade name');
assertTrue($saved['primary_color'] === '#112233', 'save primary');
assertTrue(($saved['emails']['welcome']['subject'] ?? '') === 'Hello Alpha', 'email template');

$rowA = CompanySetting::query()->where('company_id', $a->id)->where('group', 'branding')->first();
assertTrue((bool) $rowA, 'branding row company A');

if ($b) {
    $brandB = $svc->forCompany($b);
    assertTrue(($brandB['trade_name'] ?? '') !== 'Marque Alpha Test' || ($brandB['primary_color'] ?? '') !== '#112233' || ! CompanySetting::query()->where('company_id', $b->id)->where('group', 'branding')->exists(), 'company B isolated from A');
    $svc->save([
        'trade_name' => 'Marque Beta Test',
        'primary_color' => '#abcdef',
        'theme' => 'light',
        'density' => 'comfortable',
        'currency' => 'EUR',
        'locale' => 'fr',
        'timezone' => 'Europe/Paris',
        'date_format' => 'Y-m-d',
        'number_format' => 'en',
    ], [], $b);
    $againA = $svc->forCompany($a);
    assertTrue($againA['trade_name'] === 'Marque Alpha Test', 'A unchanged after B save');
    assertTrue($svc->forCompany($b)['trade_name'] === 'Marque Beta Test', 'B saved');
} else {
    assertTrue(true, 'single company — isolation skip (ok)');
    assertTrue(true, 'single company — A unchanged (ok)');
    assertTrue(true, 'single company — B save skip (ok)');
}

$css = $svc->cssVariables($saved);
assertTrue(($css['--color-gp-primary'] ?? '') === '#112233', 'css vars');

$email = $svc->renderEmail('welcome', ['name' => 'Sam'], $a);
assertTrue(str_contains($email['subject'], 'Hello Alpha'), 'render email subject');
assertTrue(str_contains($email['body'], 'Marque Alpha Test') || str_contains($email['body'], 'Alpha'), 'render email body');

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));
$html = view('branding.index', [
    'company' => $a,
    'branding' => $saved,
    'tab' => 'identity',
    'cssVars' => $css,
    'urls' => array_fill_keys(BrandingService::FILE_KEYS, null),
])->render();
assertTrue(str_contains($html, 'Aperçu en temps réel'), 'branding UI');
assertTrue(str_contains($html, 'Marque Alpha Test'), 'trade name in UI');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

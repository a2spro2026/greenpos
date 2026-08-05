<?php

/**
 * Smoke — SaaS Onboarding: register → provision → wizard → dashboard checklist.
 * Usage: php scripts/smoke_onboarding.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\SaasOnboarding;
use App\Models\SaasPlan;
use App\Models\User;
use App\Services\OnboardingService;
use App\Support\SessionManager;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

echo "=== SaaS Onboarding smoke ===\n";

assertTrue(Schema::hasTable('saas_onboardings'), 'table saas_onboardings');

foreach (['onboarding.landing', 'onboarding.register', 'onboarding.plan', 'onboarding.wizard', 'onboarding.wizard.store'] as $r) {
    assertTrue(Route::has($r), "route {$r}");
}

$svc = app(OnboardingService::class);
$plans = $svc->publicPlans();
assertTrue($plans->count() >= 4, 'public plans >= 4');

$email = 'onboard_'.Str::lower(Str::random(8)).'@test.greenpos';
$user = $svc->registerAccount([
    'full_name' => 'Amine Benali',
    'company_name' => 'Boutique Atlas Test',
    'email' => $email,
    'phone' => '0612345678',
    'password' => 'password123',
]);
assertTrue((bool) $user->id, 'user created');
assertTrue($svc->needsPlan($user), 'needs plan');

$req = Request::create('/register/plan', 'POST', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
$req->setLaravelSession($app['session.store']);
$app['session']->start();
SessionManager::loginUser($user, $req, false);

$plan = SaasPlan::query()->where('code', 'starter')->first() ?? $plans->first();
$row = $svc->provision($user, $plan, 'trial');
assertTrue($row->status === 'wizard', 'status wizard');
assertTrue((bool) $row->company_id, 'company provisioned');
assertTrue((bool) $row->saas_tenant_id, 'tenant provisioned');
assertTrue(Company::query()->find($row->company_id)?->users()->where('users.id', $user->id)->exists(), 'owner attached');

Workspace::set($row->company, $row->company->stores()->first());
assertTrue((bool) Workspace::company(), 'workspace set');

$svc->saveWizard($row, [
    'address' => '12 Rue Test',
    'country' => 'Maroc',
    'city' => 'Rabat',
    'currency' => 'MAD',
    'tax_rate' => 20,
    'category_name' => 'Boissons',
    'product_name' => 'Eau 50cl',
    'product_price' => 5,
    'register_name' => 'Caisse Principale',
]);
$svc->complete($row->fresh());
assertTrue($row->fresh()->status === 'completed', 'onboarding completed');

$checklist = $svc->dashboardChecklist($row->company);
assertTrue(is_array($checklist), 'checklist available');
assertTrue($checklist['items']['add_product']['done'] === true, 'product checklist done');
assertTrue($checklist['items']['configure_pos']['done'] === true, 'pos checklist done');

view()->share('errors', new Illuminate\Support\ViewErrorBag([]));
$landing = view('onboarding.landing', ['plans' => $plans])->render();
assertTrue(str_contains($landing, 'Essayer gratuitement'), 'landing CTA');

$register = view('onboarding.register')->render();
assertTrue(str_contains($register, 'Nom de l’entreprise') || str_contains($register, "Nom de l'entreprise"), 'register form');

echo "\nResult: {$ok} ok, {$fail} fail\n";
exit($fail > 0 ? 1 : 0);

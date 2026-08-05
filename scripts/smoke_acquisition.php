<?php

/**
 * Smoke — full acquisition journey
 * php scripts/smoke_acquisition.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Events\CompanyRegistrationApproved;
use App\Events\CompanyRegistrationRejected;
use App\Events\CompanyRegistrationSubmitted;
use App\Events\CompanyRegistrationSuspended;
use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\PlatformNotification;
use App\Models\User;
use App\Services\CompanyRegistrationService;
use App\Services\PlatformAdminService;
use App\Services\PlatformBootstrapService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

$failed = 0;
$passed = 0;

function ok(bool $c, string $m): void
{
    global $failed, $passed;
    if ($c) {
        $passed++;
        echo "  OK  {$m}\n";
    } else {
        $failed++;
        echo " FAIL {$m}\n";
    }
}

echo "=== GreenPOS acquisition journey ===\n";

app(PlatformBootstrapService::class)->ensureMinimal();
app(\App\Services\SaasService::class)->ensurePlans();

ok(Route::has('register-company.track'), 'route suivi form');
ok(Route::has('register-company.track.show'), 'route suivi show');
ok(Route::has('site.home'), 'site home intact');
ok(Route::has('home'), 'ERP home intact');

$svc = app(CompanyRegistrationService::class);
$planId = $svc->publicPlans()->first()->id;
$admin = User::query()->where('email', PlatformBootstrapService::SUPER_ADMIN_EMAIL)->first();
ok((bool) $admin, 'super admin');

$dispatched = [];
Event::listen(CompanyRegistrationSubmitted::class, function () use (&$dispatched) {
    $dispatched[] = 'submitted';
});
Event::listen(CompanyRegistrationApproved::class, function () use (&$dispatched) {
    $dispatched[] = 'approved';
});
Event::listen(CompanyRegistrationRejected::class, function () use (&$dispatched) {
    $dispatched[] = 'rejected';
});
Event::listen(CompanyRegistrationSuspended::class, function () use (&$dispatched) {
    $dispatched[] = 'suspended';
});

$email = 'acq-'.Str::lower(Str::random(6)).'@example.test';
$req = $svc->submit([
    'owner_name' => 'Client Acq',
    'owner_phone' => '0600112233',
    'owner_email' => $email,
    'password' => 'pass1234',
    'company_name' => 'Acq Co '.Str::random(3),
    'activity' => 'Retail',
    'country' => 'Maroc',
    'city' => 'Casa',
    'address' => '1 rue A',
    'currency' => 'MAD',
    'store_name' => 'Magasin A',
    'saas_plan_id' => $planId,
]);

ok($req->status === 'EN_ATTENTE', 'status EN_ATTENTE');
ok((bool) preg_match('/^REQ-\d{8}-[A-Z0-9]{4}$/', $req->reference), 'reference REQ-YYYYMMDD-XXXX');
ok(in_array('submitted', $dispatched, true), 'event submitted');
ok(PlatformNotification::query()->where('data->request_id', $req->id)->exists(), 'admin notified');

$found = $svc->findByReference($req->reference);
ok((bool) $found && $found->id === $req->id, 'findByReference');
$msg = $svc->statusMessage($req);
ok(str_contains($msg['title'], 'étude') || str_contains($msg['body'], 'email'), 'pending message');

$approved = $svc->approve($req->fresh(), $admin);
ok($approved->status === 'ACTIVE', 'status ACTIVE');
ok(in_array('approved', $dispatched, true), 'event approved');
ok((bool) $approved->company_id, 'company provisioned');
ok(User::query()->where('email', $email)->exists(), 'owner created');
ok(Hash::check('pass1234', User::query()->where('email', $email)->first()->password), 'password ok');
ok(Company::query()->find($approved->company_id)?->stores()->exists(), 'store created');
$msgA = $svc->statusMessage($approved);
ok($msgA['title'] === 'Votre entreprise a été activée.', 'approved copy');
ok($msgA['body'] === 'Vous pouvez maintenant vous connecter.', 'approved body');

$svc->suspend($approved->fresh(), $admin, 'Contrôle qualité');
ok($approved->fresh()->status === 'SUSPENDUE', 'status SUSPENDUE');
ok(in_array('suspended', $dispatched, true), 'event suspended');
$msgS = $svc->statusMessage($approved->fresh());
ok($msgS['title'] === 'Votre demande est suspendue.', 'suspended copy');

$email2 = 'acq-rej-'.Str::lower(Str::random(6)).'@example.test';
$req2 = $svc->submit([
    'owner_name' => 'Reject Client',
    'owner_phone' => '0611223344',
    'owner_email' => $email2,
    'password' => 'pass1234',
    'company_name' => 'Reject Co',
    'activity' => 'Services',
    'country' => 'Maroc',
    'city' => 'Rabat',
    'address' => '2 rue B',
    'currency' => 'MAD',
    'store_name' => 'Boutique',
    'saas_plan_id' => $planId,
]);
$svc->reject($req2, $admin, 'Dossier incomplet');
ok($req2->fresh()->status === 'REFUSEE', 'status REFUSEE');
ok(in_array('rejected', $dispatched, true), 'event rejected');
$msgR = $svc->statusMessage($req2->fresh());
ok($msgR['title'] === 'Votre demande n’a pas été acceptée.', 'rejected copy');

$stats = app(PlatformAdminService::class)->dashboardKpis();
ok(array_key_exists('registration_today', $stats), 'KPI today');
ok(array_key_exists('registration_week', $stats), 'KPI week');
ok(array_key_exists('registration_acceptance_rate', $stats), 'KPI acceptance');
ok(array_key_exists('registration_avg_validation_label', $stats), 'KPI avg time');

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
foreach (['/suivi-demande', '/suivi-demande/'.$req->reference, '/'] as $uri) {
    $code = $kernel->handle(Illuminate\Http\Request::create($uri, 'GET'))->getStatusCode();
    ok($code === 200, "HTTP {$uri} => {$code}");
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);

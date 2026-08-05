<?php

/**
 * Smoke tests — company registration workflow
 * Run: php scripts/smoke_registration.php
 */

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\PlatformNotification;
use App\Models\SaasPlan;
use App\Models\User;
use App\Services\CompanyRegistrationService;
use App\Services\PlatformAdminService;
use App\Services\PlatformBootstrapService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

$failed = 0;
$passed = 0;

function assertTrue(bool $cond, string $msg): void
{
    global $failed, $passed;
    if ($cond) {
        $passed++;
        echo "  OK  {$msg}\n";
    } else {
        $failed++;
        echo " FAIL {$msg}\n";
    }
}

echo "=== GreenPOS registration smoke ===\n";

app(PlatformBootstrapService::class)->ensureMinimal();
app(\App\Services\SaasService::class)->ensurePlans();

assertTrue(Schema::hasTable('company_registration_requests'), 'table company_registration_requests');
assertTrue(Schema::hasTable('platform_notifications'), 'table platform_notifications');

$svc = app(CompanyRegistrationService::class);
$plans = $svc->publicPlans();
assertTrue($plans->count() >= 3, 'public plans starter/business/enterprise');
assertTrue($plans->contains(fn ($p) => $p->code === 'starter'), 'has starter');
assertTrue($plans->contains(fn ($p) => $p->code === 'standard'), 'has business (standard)');
assertTrue($plans->contains(fn ($p) => $p->code === 'enterprise'), 'has enterprise');

$email = 'smoke-reg-'.Str::lower(Str::random(6)).'@example.test';
$planId = $plans->firstWhere('code', 'starter')->id;

$req = $svc->submit([
    'owner_name' => 'Smoke Owner',
    'owner_phone' => '0600000000',
    'owner_email' => $email,
    'password' => 'secret12',
    'company_name' => 'Smoke Co '.Str::random(4),
    'activity' => 'Retail',
    'country' => 'Maroc',
    'city' => 'Casablanca',
    'address' => '1 rue Test',
    'currency' => 'MAD',
    'store_name' => 'Magasin Centre',
    'saas_plan_id' => $planId,
]);

assertTrue($req->status === CompanyRegistrationRequest::STATUS_PENDING, 'status EN_ATTENTE');
assertTrue(! Company::query()->where('email', $email)->exists(), 'no company created yet');
assertTrue(! User::query()->where('email', $email)->exists(), 'no user created yet');
assertTrue(PlatformNotification::query()->where('type', 'registration')->where('data->request_id', $req->id)->exists(), 'platform notification created');

$admin = User::query()->where('email', PlatformBootstrapService::SUPER_ADMIN_EMAIL)->first();
assertTrue((bool) $admin, 'super admin exists');

$approved = $svc->approve($req->fresh(), $admin);
assertTrue($approved->status === CompanyRegistrationRequest::STATUS_ACTIVE, 'status ACTIVE after approve');
assertTrue((bool) $approved->company_id, 'company_id set');
$user = User::query()->where('email', $email)->first();
assertTrue((bool) $user, 'user created on approve');
assertTrue(Hash::check('secret12', $user->password), 'password preserved');
$company = Company::query()->find($approved->company_id);
assertTrue($company && $company->status === 'active', 'company active');
assertTrue($company->stores()->where('name', 'Magasin Centre')->exists(), 'store name preserved');

$svc->suspend($approved->fresh(), $admin, 'Test suspension');
$approved->refresh();
$company->refresh();
assertTrue($approved->status === CompanyRegistrationRequest::STATUS_SUSPENDED, 'status SUSPENDUE');
assertTrue($company->status === 'inactive', 'company inactive after suspend');

$svc->approve($approved->fresh(), $admin); // reactivate
$approved->refresh();
$company->refresh();
assertTrue($approved->status === CompanyRegistrationRequest::STATUS_ACTIVE, 'reactivated ACTIVE');
assertTrue($company->status === 'active', 'company active after reactivate');

// Reject flow
$email2 = 'smoke-rej-'.Str::lower(Str::random(6)).'@example.test';
$req2 = $svc->submit([
    'owner_name' => 'Reject Me',
    'owner_phone' => '0611111111',
    'owner_email' => $email2,
    'password' => 'secret12',
    'company_name' => 'Reject Co',
    'activity' => 'Services',
    'country' => 'Maroc',
    'city' => 'Rabat',
    'address' => '2 rue Test',
    'currency' => 'MAD',
    'store_name' => 'Boutique',
    'saas_plan_id' => $planId,
]);
$svc->reject($req2, $admin, 'Documents incomplets pour validation.');
$req2->refresh();
assertTrue($req2->status === CompanyRegistrationRequest::STATUS_REJECTED, 'status REFUSEE');
assertTrue(! User::query()->where('email', $email2)->exists(), 'no user after reject');

// Super Admin direct create
$directEmail = 'smoke-direct-'.Str::lower(Str::random(6)).'@example.test';
$result = app(PlatformAdminService::class)->provisionCompany([
    'name' => 'Direct Co',
    'activity' => 'Retail',
    'owner_name' => 'Direct Owner',
    'email' => $directEmail,
    'phone' => '0622222222',
    'country' => 'Maroc',
    'city' => 'Fès',
    'saas_plan_id' => $planId,
    'password' => 'direct99',
    'store_name' => 'HQ',
]);
assertTrue($result['company']->status === 'active', 'direct create ACTIVE');
assertTrue(User::query()->where('email', $directEmail)->exists(), 'direct user created');

$kpis = app(PlatformAdminService::class)->dashboardKpis();
assertTrue(array_key_exists('registration_pending', $kpis), 'KPI registration_pending');
assertTrue(array_key_exists('companies_active', $kpis), 'KPI companies_active');

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);

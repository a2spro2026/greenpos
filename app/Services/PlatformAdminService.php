<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyRegistrationRequest;
use App\Models\CompanySetting;
use App\Models\SaasLicense;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Models\Store;
use App\Models\User;
use App\Support\SettingsDefaults;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformAdminService
{
    public function __construct(
        private SaasService $saas,
        private ModuleManagerService $modules,
    ) {
    }

    public function dashboardKpis(): array
    {
        $this->saas->ensurePlans();
        $base = $this->saas->dashboardStats();

        $suspended = SaasTenant::query()->where('status', 'suspended')->whereNull('archived_at')->count();
        $companiesTotal = Schema::hasTable('companies')
            ? Company::query()->count()
            : $base['clients'];
        $companiesActive = Schema::hasTable('companies')
            ? Company::query()->where('status', 'active')->count()
            : 0;
        $companiesSuspended = Schema::hasTable('companies')
            ? Company::query()->where('status', 'inactive')->count()
            : $suspended;
        $revenue = (float) SaasPayment::query()->where('status', 'paid')->sum('amount');
        $trialCompanies = SaasTenant::query()->where('status', 'trial')->whereNull('archived_at')->count();
        $activeUsers = User::query()
            ->where('is_platform_admin', false)
            ->where('status', 'active')
            ->count();

        $regCounts = ['pending' => 0, 'active' => 0, 'suspended' => 0, 'rejected' => 0];
        $acquisition = [
            'today' => 0,
            'week' => 0,
            'acceptance_rate' => null,
            'avg_validation_hours' => null,
            'avg_validation_label' => '—',
        ];
        if (Schema::hasTable('company_registration_requests')) {
            $regService = app(CompanyRegistrationService::class);
            $regCounts = $regService->statusCounts();
            $acquisition = $regService->acquisitionStats();
        }

        return array_merge($base, [
            'companies_total' => $companiesTotal,
            'companies_active' => $companiesActive,
            'companies_suspended' => $companiesSuspended,
            'stores_total' => $base['total_stores'],
            'users_total' => $activeUsers,
            'active_users' => $activeUsers,
            'active_subscriptions' => $base['active_subscriptions'],
            'platform_revenue' => round($revenue, 2),
            'trial_companies' => $trialCompanies,
            'suspended_companies' => $suspended,
            'registration_pending' => $regCounts['pending'] ?? 0,
            'registration_active' => $regCounts['active'] ?? 0,
            'registration_suspended' => $regCounts['suspended'] ?? 0,
            'registration_rejected' => $regCounts['rejected'] ?? 0,
            'registration_today' => $acquisition['today'],
            'registration_week' => $acquisition['week'],
            'registration_acceptance_rate' => $acquisition['acceptance_rate'],
            'registration_avg_validation_label' => $acquisition['avg_validation_label'],
        ]);
    }

    /**
     * Full company provisioning for Super Admin.
     *
     * @return array{company: Company, store: Store, user: User, tenant: SaasTenant, password: string}
     */
    public function provisionCompany(array $data): array
    {
        $this->saas->ensurePlans();
        $plan = SaasPlan::query()->findOrFail($data['saas_plan_id']);

        if (User::query()->where('email', $data['email'])->exists()) {
            throw ValidationException::withMessages([
                'email' => 'Un utilisateur existe déjà avec cet e-mail.',
            ]);
        }

        $plainPassword = $data['password'] ?? null;
        $passwordHash = $data['password_hash'] ?? null;
        if (! $passwordHash) {
            $plainPassword = $plainPassword ?: Str::password(12);
            $passwordHash = Hash::make($plainPassword);
        }

        return DB::transaction(function () use ($data, $plan, $plainPassword, $passwordHash) {
            $ownerName = trim($data['owner_name']);
            $parts = preg_split('/\s+/', $ownerName, 2) ?: [$ownerName];

            $user = User::query()->create([
                'name' => $ownerName,
                'first_name' => $parts[0] ?? $ownerName,
                'last_name' => $parts[1] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $passwordHash,
                'status' => 'active',
                'is_platform_admin' => false,
            ]);

            $company = Company::query()->create([
                'name' => $data['name'],
                'legal_name' => $data['name'],
                'activity' => $data['activity'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'currency' => strtoupper($data['currency'] ?? 'MAD'),
                'timezone' => 'Africa/Casablanca',
                'locale' => 'fr',
                'status' => 'active',
            ]);

            $user->companies()->attach($company->id, [
                'role' => 'owner',
                'status' => 'active',
                'is_primary' => true,
            ]);

            $store = Store::query()->create([
                'company_id' => $company->id,
                'name' => trim($data['store_name'] ?? 'Boutique principale') ?: 'Boutique principale',
                'code' => 'MAIN',
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'],
                'manager_user_id' => $user->id,
                'is_active' => true,
                'is_default' => true,
            ]);

            $user->stores()->syncWithoutDetaching([$store->id]);

            foreach (['tax', 'currencies', 'languages', 'numbering', 'pos', 'backup'] as $group) {
                CompanySetting::query()->updateOrCreate(
                    ['company_id' => $company->id, 'group' => $group],
                    ['payload' => SettingsDefaults::for($group)]
                );
            }

            $trialDays = max(1, (int) ($plan->trial_days ?: 14));
            $tenant = SaasTenant::query()->create([
                'company_id' => $company->id,
                'name' => $company->name,
                'legal_name' => $company->name,
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'country' => strlen((string) ($data['country'] ?? '')) === 2
                    ? strtoupper($data['country'])
                    : 'MA',
                'city' => $data['city'] ?? null,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'owner_user_id' => $user->id,
            ]);

            $sub = SaasSubscription::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_plan_id' => $plan->id,
                'status' => 'trialing',
                'billing_cycle' => 'monthly',
                'amount' => 0,
                'currency' => $plan->currency ?? 'MAD',
                'starts_at' => now(),
                'ends_at' => now()->addDays($trialDays),
                'renews_at' => now()->addDays($trialDays),
                'trial_ends_at' => now()->addDays($trialDays),
                'provider' => 'manual',
                'auto_renew' => true,
            ]);

            SaasLicense::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_subscription_id' => $sub->id,
                'status' => 'active',
                'expires_at' => $sub->ends_at,
            ]);

            $this->modules->syncCompanyFromPlan($company, $plan);

            $this->saas->logAudit(
                'tenant',
                'info',
                'Entreprise provisionnée (Super Admin)',
                $company->name,
                $tenant,
                [
                    'company_id' => $company->id,
                    'store_id' => $store->id,
                    'user_id' => $user->id,
                    'plan' => $plan->code,
                ]
            );

            return [
                'company' => $company->fresh(),
                'store' => $store,
                'user' => $user,
                'tenant' => $tenant->fresh(['currentSubscription.plan']),
                'password' => $plainPassword ?? '',
            ];
        });
    }

    public function updateCompany(Company $company, array $data): Company
    {
        $company->update(collect($data)->only([
            'name', 'legal_name', 'activity', 'email', 'phone', 'address',
            'city', 'country', 'region', 'postal_code', 'status',
        ])->all());

        $tenant = SaasTenant::query()->where('company_id', $company->id)->first();
        if ($tenant) {
            $tenant->update([
                'name' => $company->name,
                'legal_name' => $company->legal_name ?? $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'city' => $company->city,
            ]);
        }

        if (! empty($data['saas_plan_id']) && $tenant) {
            $plan = SaasPlan::query()->find($data['saas_plan_id']);
            $sub = $tenant->currentSubscription;
            if ($plan && $sub) {
                $sub->update(['saas_plan_id' => $plan->id]);
                $this->modules->syncCompanyFromPlan($company, $plan);
            }
        }

        $this->saas->logAudit('tenant', 'info', 'Entreprise modifiée', $company->name, $tenant);

        return $company->fresh();
    }

    public function suspendCompany(Company $company, ?string $reason = null): void
    {
        $company->update(['status' => 'inactive']);
        $tenant = SaasTenant::query()->where('company_id', $company->id)->first();
        if ($tenant) {
            $this->saas->suspend($tenant, $reason);
        }

        if (Schema::hasTable('company_registration_requests')) {
            CompanyRegistrationRequest::query()
                ->where('company_id', $company->id)
                ->where('status', CompanyRegistrationRequest::STATUS_ACTIVE)
                ->update([
                    'status' => CompanyRegistrationRequest::STATUS_SUSPENDED,
                    'suspend_reason' => $reason,
                    'suspended_at' => now(),
                ]);
        }
    }

    public function reactivateCompany(Company $company): void
    {
        $company->update(['status' => 'active']);
        $tenant = SaasTenant::query()->where('company_id', $company->id)->first();
        if ($tenant) {
            $this->saas->reactivate($tenant);
        }

        if (Schema::hasTable('company_registration_requests')) {
            CompanyRegistrationRequest::query()
                ->where('company_id', $company->id)
                ->where('status', CompanyRegistrationRequest::STATUS_SUSPENDED)
                ->update([
                    'status' => CompanyRegistrationRequest::STATUS_ACTIVE,
                    'suspend_reason' => null,
                    'suspended_at' => null,
                ]);
        }
    }

    public function deleteCompany(Company $company): void
    {
        $tenant = SaasTenant::query()->where('company_id', $company->id)->first();
        if ($tenant) {
            $this->saas->logAudit('tenant', 'critical', 'Entreprise supprimée', $company->name, $tenant);
            $tenant->delete();
        }
        $company->delete();
    }

    public function startImpersonation(Company $company, Request $request): User
    {
        $admin = Auth::user();
        if (! $admin?->is_platform_admin) {
            abort(403);
        }

        $owner = $company->users()
            ->wherePivot('role', 'owner')
            ->where('users.status', 'active')
            ->where('users.is_platform_admin', false)
            ->first()
            ?? $company->users()
                ->where('users.status', 'active')
                ->where('users.is_platform_admin', false)
                ->first()
            ?? $company->users()
                ->wherePivot('role', 'owner')
                ->where('users.status', 'active')
                ->first()
            ?? $company->users()->where('users.status', 'active')->first();

        if (! $owner) {
            throw ValidationException::withMessages([
                'impersonate' => 'Aucun utilisateur actif pour cette entreprise.',
            ]);
        }

        $store = $company->stores()->where('is_default', true)->first()
            ?? $company->stores()->first();

        if (! $store) {
            throw ValidationException::withMessages([
                'impersonate' => 'Aucune boutique pour cette entreprise.',
            ]);
        }

        $request->session()->put('admin_impersonator_id', $admin->id);
        $request->session()->put('admin_impersonating_company_id', $company->id);
        $request->session()->put('admin_impersonating_company_name', $company->name);

        Auth::login($owner, false);
        Workspace::set($company, $store);

        $this->saas->logAudit('security', 'warning', 'Impersonation démarrée', $company->name, SaasTenant::query()->where('company_id', $company->id)->first(), [
            'admin_id' => $admin->id,
            'as_user_id' => $owner->id,
            'company_id' => $company->id,
        ]);

        return $owner;
    }

    public function stopImpersonation(Request $request): ?User
    {
        $adminId = $request->session()->pull('admin_impersonator_id');
        $request->session()->forget([
            'admin_impersonating_company_id',
            'admin_impersonating_company_name',
            'workspace_company_id',
            'workspace_store_id',
            'workspace_store_filter',
        ]);

        if (! $adminId) {
            return null;
        }

        $admin = User::query()->find($adminId);
        if ($admin) {
            Auth::login($admin, false);
        }

        Workspace::clear();

        return $admin;
    }

    public function isImpersonating(Request $request): bool
    {
        return (bool) $request->session()->get('admin_impersonator_id');
    }

    /** @return array<string, mixed> */
    public function platformSettings(): array
    {
        $defaults = [
            'platform_name' => 'GreenPOS',
            'support_email' => 'support@greenpos.test',
            'support_phone' => '',
            'default_trial_days' => 14,
            'maintenance_mode' => false,
            'allow_self_signup' => true,
            'default_currency' => 'MAD',
            'default_country' => 'Maroc',
            'invoice_prefix' => 'GP',
            'note' => '',
        ];

        return array_merge($defaults, Cache::get('greenpos.platform_settings', []));
    }

    public function savePlatformSettings(array $data): array
    {
        $settings = array_merge($this->platformSettings(), collect($data)->only([
            'platform_name', 'support_email', 'support_phone', 'default_trial_days',
            'maintenance_mode', 'allow_self_signup', 'default_currency', 'default_country',
            'invoice_prefix', 'note',
        ])->all());

        $settings['maintenance_mode'] = (bool) ($settings['maintenance_mode'] ?? false);
        $settings['allow_self_signup'] = (bool) ($settings['allow_self_signup'] ?? true);
        $settings['default_trial_days'] = max(1, (int) ($settings['default_trial_days'] ?? 14));

        Cache::forever('greenpos.platform_settings', $settings);
        $this->saas->logAudit('platform', 'info', 'Paramètres plateforme mis à jour', null, null, $settings);

        return $settings;
    }
}

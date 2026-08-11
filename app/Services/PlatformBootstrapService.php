<?php

namespace App\Services;

use App\Models\SaasPlan;
use App\Models\User;
use App\Support\ModuleCatalog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent platform initialization: Super Admin + SaaS plans only.
 */
class PlatformBootstrapService
{
    public const SUPER_ADMIN_EMAIL = 'yahyabilal@greenpos.com';

    public const SUPER_ADMIN_USERNAME = 'bilal';

    public const SUPER_ADMIN_PASSWORD = '0661755048';

    public const PLATFORM_COMPANY_NAME = 'GreenPOS';

    public const DEMO_COMPANY_NAME = 'Entreprise Démo';

    public function __construct(private SaasService $saas)
    {
    }

    /**
     * Run when the database looks uninitialized (safe on every request).
     */
    public function ensureIfEmpty(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! User::query()->where('email', self::SUPER_ADMIN_EMAIL)->exists()) {
            $this->ensureMinimal();
        }
    }

    /**
     * Super Admin + plans only (no companies, no demo).
     *
     * @return array{super_admin: User}
     */
    public function ensureMinimal(): array
    {
        if (! Schema::hasTable('users')) {
            throw new \RuntimeException('Migrations manquantes. Exécutez php artisan migrate.');
        }

        $this->saas->ensurePlans();
        $this->ensureAllPlansPresent();

        $enterprise = SaasPlan::query()->where('code', 'enterprise')->first();
        if ($enterprise) {
            $enterprise->update([
                'modules' => ModuleCatalog::keys(),
                'is_active' => true,
                'is_public' => true,
            ]);
        }

        return ['super_admin' => $this->ensureSuperAdmin()];
    }

    /** @return array{super_admin: User} */
    public function ensureReady(): array
    {
        return $this->ensureMinimal();
    }

    public function ensureSuperAdmin(): User
    {
        // Migrate legacy demo email if present
        $legacy = User::query()->where('email', 'superadmin@greenpos.com')->first();
        if ($legacy && ! User::query()->where('email', self::SUPER_ADMIN_EMAIL)->exists()) {
            $legacy->forceFill(['email' => self::SUPER_ADMIN_EMAIL])->save();
        }

        $user = User::query()->updateOrCreate(
            ['email' => self::SUPER_ADMIN_EMAIL],
            [
                'name' => 'Zerragui Abdelilah',
                'first_name' => 'Zerragui',
                'last_name' => 'Abdelilah',
                'username' => self::SUPER_ADMIN_USERNAME,
                'password' => self::SUPER_ADMIN_PASSWORD,
                'status' => 'active',
                'is_platform_admin' => true,
            ]
        );

        $user->forceFill([
            'name' => 'Zerragui Abdelilah',
            'first_name' => 'Zerragui',
            'last_name' => 'Abdelilah',
            'username' => self::SUPER_ADMIN_USERNAME,
            'status' => 'active',
            'is_platform_admin' => true,
            'password' => self::SUPER_ADMIN_PASSWORD,
        ])->save();

        if (Schema::hasTable('company_user')) {
            $user->companies()->detach();
        }

        // Remove leftover legacy superadmin account if both somehow exist
        User::query()
            ->where('email', 'superadmin@greenpos.com')
            ->where('id', '!=', $user->id)
            ->delete();

        return $user->fresh();
    }

    private function ensureAllPlansPresent(): void
    {
        if (! Schema::hasTable('saas_plans')) {
            return;
        }

        $defs = [
            'starter' => ['name' => 'Starter', 'price_monthly' => 199, 'price_yearly' => 1990, 'sort_order' => 1],
            'standard' => ['name' => 'Business', 'price_monthly' => 499, 'price_yearly' => 4990, 'sort_order' => 2],
            'professional' => ['name' => 'Professional', 'price_monthly' => 999, 'price_yearly' => 9990, 'sort_order' => 3],
            'enterprise' => ['name' => 'Enterprise', 'price_monthly' => 2499, 'price_yearly' => 24990, 'sort_order' => 4],
        ];

        foreach ($defs as $code => $meta) {
            SaasPlan::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name' => $meta['name'],
                    'price_monthly' => $meta['price_monthly'],
                    'price_yearly' => $meta['price_yearly'],
                    'currency' => 'MAD',
                    'modules' => ModuleCatalog::defaultModulesForPlan($code),
                    'is_active' => true,
                    'is_public' => true,
                    'sort_order' => $meta['sort_order'],
                    'trial_days' => 14,
                    'max_users' => $code === 'enterprise' ? 999 : 50,
                    'max_stores' => $code === 'enterprise' ? 999 : 10,
                    'storage_gb' => $code === 'enterprise' ? 1000 : 100,
                ]
            );
        }
    }

    public static function passwordMatches(User $user, string $plain): bool
    {
        return Hash::check($plain, $user->password);
    }
}

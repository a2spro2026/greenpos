<?php

namespace App\Services;

use App\Models\SaasPlan;
use App\Models\User;
use App\Support\ModuleCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Wipe all tenant / demo data while keeping Super Admin, plans and platform settings.
 */
class PlatformResetService
{
    /** @var list<string> */
    private const KEEP_TABLES = [
        'migrations',
        'users',
        'password_reset_tokens',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'saas_plans',
        'saas_payment_gateways',
        'permissions',
        'roles',
        'permission_role',
    ];

    public function __construct(private PlatformBootstrapService $bootstrap)
    {
    }

    /**
     * @return array{deleted_tables:int, companies_left:int, users_left:int}
     */
    public function reset(): array
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            Schema::disableForeignKeyConstraints();
        }

        $deleted = 0;
        try {
            foreach (Schema::getTables() as $meta) {
                $table = $meta['name'] ?? null;
                if (! is_string($table) || $table === '' || in_array($table, self::KEEP_TABLES, true)) {
                    continue;
                }
                // MySQL may list views / case-mismatched names — only wipe real tables
                if (! Schema::hasTable($table)) {
                    continue;
                }
                try {
                    DB::table($table)->delete();
                    $deleted++;
                } catch (\Throwable $e) {
                    // Skip non-deletable relations / views
                    report($e);
                }
            }

            // Keep only Super Admin (force-delete: users use SoftDeletes + unique email)
            if (Schema::hasTable('users')) {
                User::withTrashed()
                    ->where(function ($q) {
                        $q->where('email', '!=', PlatformBootstrapService::SUPER_ADMIN_EMAIL)
                            ->orWhereNull('email');
                    })
                    ->where('is_platform_admin', false)
                    ->forceDelete();
            }
        } finally {
            if ($driver === 'sqlite') {
                DB::statement('PRAGMA foreign_keys = ON');
            } else {
                Schema::enableForeignKeyConstraints();
            }
        }

        // Re-assert Super Admin + plans (no companies / no demo)
        $this->bootstrap->ensureMinimal();

        return [
            'deleted_tables' => $deleted,
            'companies_left' => Schema::hasTable('companies') ? (int) DB::table('companies')->count() : 0,
            'users_left' => User::query()->count(),
        ];
    }
}

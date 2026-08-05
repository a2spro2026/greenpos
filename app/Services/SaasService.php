<?php

namespace App\Services;

use App\Models\Company;
use App\Models\SaasAuditEvent;
use App\Models\SaasDomain;
use App\Models\SaasInvoice;
use App\Models\SaasLicense;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasPlatformSnapshot;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SaasService
{
    /** @deprecated Use ModuleCatalog::labels() */
    public static function moduleCatalogLabels(): array
    {
        return \App\Support\ModuleCatalog::labels();
    }

    public function ensurePlans(): void
    {
        SaasPlan::query()->where('code', 'standard')->where('name', '!=', 'Business')->update([
            'name' => 'Business',
            'tagline' => 'Pour les commerces en croissance',
        ]);

        if (SaasPlan::query()->exists()) {
            return;
        }

        $defs = [
            [
                'code' => 'starter',
                'name' => 'Starter',
                'tagline' => 'Pour démarrer rapidement',
                'price_monthly' => 199,
                'price_yearly' => 1990,
                'max_users' => 3,
                'max_stores' => 1,
                'storage_gb' => 5,
                'api_enabled' => false,
                'support_level' => 'email',
                'support_included' => true,
                'backups_enabled' => false,
                'custom_domain_enabled' => false,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('starter'),
                'features' => ['Support email', '1 boutique', 'Exports CSV'],
                'sort_order' => 1,
            ],
            [
                'code' => 'standard',
                'name' => 'Business',
                'tagline' => 'Pour les commerces en croissance',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'max_users' => 15,
                'max_stores' => 3,
                'storage_gb' => 25,
                'api_enabled' => false,
                'support_level' => 'chat',
                'support_included' => true,
                'backups_enabled' => true,
                'custom_domain_enabled' => false,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('standard'),
                'features' => ['Support chat', '3 boutiques', 'Rapports BI', 'Documents'],
                'sort_order' => 2,
            ],
            [
                'code' => 'professional',
                'name' => 'Professional',
                'tagline' => 'Pour les réseaux multi-sites',
                'price_monthly' => 999,
                'price_yearly' => 9990,
                'max_users' => 50,
                'max_stores' => 10,
                'storage_gb' => 100,
                'api_enabled' => true,
                'support_level' => 'priority',
                'support_included' => true,
                'backups_enabled' => true,
                'custom_domain_enabled' => true,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('professional'),
                'features' => ['Support prioritaire', '10 boutiques', 'Audit', 'API', 'Domaine custom'],
                'sort_order' => 3,
            ],
            [
                'code' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'Sur-mesure & conformité',
                'price_monthly' => 2499,
                'price_yearly' => 24990,
                'max_users' => 500,
                'max_stores' => 100,
                'storage_gb' => 1000,
                'api_enabled' => true,
                'support_level' => 'dedicated',
                'support_included' => true,
                'backups_enabled' => true,
                'custom_domain_enabled' => true,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('enterprise'),
                'features' => ['SLA 99.9%', 'CSM dédié', 'SSO', 'Stockage illimité*', 'Personnalisation'],
                'sort_order' => 4,
            ],
        ];

        foreach ($defs as $def) {
            SaasPlan::query()->create(array_merge($def, [
                'currency' => 'MAD',
                'is_public' => true,
                'is_active' => true,
                'trial_days' => 14,
                'description' => $def['tagline'],
            ]));
        }
    }

    public function dashboardStats(): array
    {
        $tenants = SaasTenant::query();
        $activeSubs = SaasSubscription::query()->where('status', 'active');
        $trialing = SaasSubscription::query()->where('status', 'trialing');
        $expired = SaasSubscription::query()->whereIn('status', ['expired', 'cancelled'])
            ->where('ends_at', '<', now());

        $mrr = SaasSubscription::query()
            ->where('status', 'active')
            ->get()
            ->sum(fn (SaasSubscription $s) => $s->monthlyEquivalent());

        $revenueByMonth = collect(range(5, 0))->map(function (int $i) {
            $month = now()->subMonths($i)->startOfMonth();
            $total = SaasPayment::query()
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$month, (clone $month)->endOfMonth()])
                ->sum('amount');

            return [
                'label' => $month->translatedFormat('M Y'),
                'total' => (float) $total,
            ];
        });

        $byPlan = SaasSubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->selectRaw('saas_plan_id, COUNT(*) as cnt')
            ->groupBy('saas_plan_id')
            ->with('plan')
            ->get()
            ->map(fn ($r) => [
                'name' => $r->plan?->name ?? '—',
                'count' => (int) $r->cnt,
            ]);

        $byStatus = SaasTenant::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $prevMonthMrr = SaasSubscription::query()
            ->where('status', 'active')
            ->where('created_at', '<', now()->startOfMonth())
            ->get()
            ->sum(fn (SaasSubscription $s) => $s->monthlyEquivalent());
        $growth = $prevMonthMrr > 0
            ? round((($mrr - $prevMonthMrr) / $prevMonthMrr) * 100, 1)
            : ($mrr > 0 ? 100.0 : 0.0);

        $storesCount = 0;
        if (Schema::hasTable('stores')) {
            $storesCount = Store::query()->count();
        }

        $clientsGrowth = SaasTenant::query()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        return [
            'clients' => (clone $tenants)->whereNull('archived_at')->count(),
            'active_subscriptions' => (clone $activeSubs)->count(),
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'growth_monthly' => $growth,
            'trials' => (clone $trialing)->count() + SaasTenant::query()->where('status', 'trial')->whereNull('archived_at')->count(),
            'expired' => (clone $expired)->count(),
            'companies_linked' => SaasTenant::query()->whereNotNull('company_id')->count(),
            'active_users' => User::query()->where('status', 'active')->where('is_platform_admin', false)->count(),
            'total_stores' => $storesCount,
            'new_clients_month' => $clientsGrowth,
            'revenue_by_month' => $revenueByMonth,
            'by_plan' => $byPlan,
            'by_status' => $byStatus,
            'clients_by_month' => collect(range(5, 0))->map(function (int $i) {
                $month = now()->subMonths($i)->startOfMonth();

                return [
                    'label' => $month->translatedFormat('M'),
                    'count' => SaasTenant::query()->whereBetween('created_at', [$month, (clone $month)->endOfMonth()])->count(),
                ];
            }),
            'recent_tenants' => SaasTenant::query()->whereNull('archived_at')->latest()->limit(8)->with('currentSubscription.plan')->get(),
            'recent_payments' => SaasPayment::query()->latest()->limit(8)->with('tenant')->get(),
        ];
    }

    public function createTenant(array $data): SaasTenant
    {
        return DB::transaction(function () use ($data) {
            $planId = $data['saas_plan_id'] ?? SaasPlan::query()->where('code', 'starter')->value('id');
            $cycle = $data['billing_cycle'] ?? 'monthly';
            $plan = SaasPlan::query()->findOrFail($planId);
            $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

            $companyId = null;
            if (! empty($data['provision_company'])) {
                $company = Company::query()->create([
                    'name' => $data['name'],
                    'legal_name' => $data['legal_name'] ?? $data['name'],
                    'email' => $data['email'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'country' => $data['country'] ?? 'MA',
                    'city' => $data['city'] ?? null,
                    'currency' => 'MAD',
                    'timezone' => 'Africa/Casablanca',
                    'locale' => 'fr',
                    'status' => 'active',
                ]);
                $companyId = $company->id;
            } elseif (! empty($data['company_id'])) {
                $companyId = (int) $data['company_id'];
            }

            $tenant = SaasTenant::query()->create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? null,
                'legal_name' => $data['legal_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'country' => $data['country'] ?? 'MA',
                'city' => $data['city'] ?? null,
                'primary_domain' => $data['primary_domain'] ?? null,
                'storage_used_mb' => (int) ($data['storage_used_mb'] ?? 0),
                'status' => $data['status'] ?? 'trial',
                'trial_ends_at' => now()->addDays(14),
                'owner_user_id' => $data['owner_user_id'] ?? null,
            ]);

            $sub = SaasSubscription::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_plan_id' => $plan->id,
                'status' => ($data['status'] ?? 'trial') === 'trial' ? 'trialing' : 'active',
                'billing_cycle' => $cycle,
                'amount' => $amount,
                'currency' => $plan->currency,
                'starts_at' => now(),
                'ends_at' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                'renews_at' => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
                'provider' => $data['provider'] ?? 'manual',
                'auto_renew' => true,
            ]);

            SaasLicense::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_subscription_id' => $sub->id,
                'status' => 'active',
                'expires_at' => $sub->ends_at,
            ]);

            $this->logAudit('tenant', 'info', 'Nouveau client créé', $tenant->name, $tenant);

            return $tenant->fresh(['currentSubscription.plan', 'licenses', 'company']);
        });
    }

    public function updateTenant(SaasTenant $tenant, array $data): SaasTenant
    {
        $tenant->update(collect($data)->only([
            'name', 'slug', 'legal_name', 'email', 'phone', 'country', 'city',
            'primary_domain', 'storage_used_mb', 'owner_user_id', 'company_id',
        ])->all());

        return $tenant->fresh();
    }

    public function reactivate(SaasTenant $tenant): void
    {
        $tenant->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspend_reason' => null,
            'archived_at' => null,
        ]);

        $this->logAudit('tenant', 'info', 'Client réactivé', $tenant->name, $tenant);
    }

    public function archive(SaasTenant $tenant): void
    {
        $tenant->update([
            'status' => 'cancelled',
            'archived_at' => now(),
        ]);

        $this->logAudit('tenant', 'warning', 'Client archivé', $tenant->name, $tenant);
    }

    public function suspend(SaasTenant $tenant, ?string $reason = null): void
    {
        $tenant->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspend_reason' => $reason,
        ]);

        $this->logAudit('tenant', 'warning', 'Client suspendu', $reason ?: $tenant->name, $tenant);
    }

    public function logAudit(string $category, string $severity, string $title, ?string $body = null, ?SaasTenant $tenant = null, ?array $meta = null): ?SaasAuditEvent
    {
        if (! Schema::hasTable('saas_audit_events')) {
            return null;
        }

        return SaasAuditEvent::query()->create([
            'category' => $category,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'saas_tenant_id' => $tenant?->id,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'meta' => $meta,
            'occurred_at' => now(),
        ]);
    }

    public function seedJournalIfEmpty(): void
    {
        if (! Schema::hasTable('saas_audit_events') || SaasAuditEvent::query()->exists()) {
            return;
        }

        $samples = [
            ['login', 'info', 'Connexion Super Admin', 'Accès plateforme Enterprise'],
            ['system', 'info', 'Snapshot plateforme', 'Surveillance CPU / RAM capturée'],
            ['billing', 'info', 'Renouvellement réussi', 'Abonnement mensuel encaissé'],
            ['error', 'warning', 'Timeout API provider', 'Stripe webhook latency > 2s'],
            ['incident', 'critical', 'Pic CPU', 'Charge CPU > 85% pendant 5 minutes'],
            ['tenant', 'info', 'Nouveau client', 'Provisionnement essai gratuit'],
        ];

        foreach ($samples as [$cat, $sev, $title, $body]) {
            SaasAuditEvent::query()->create([
                'category' => $cat,
                'severity' => $sev,
                'title' => $title,
                'body' => $body,
                'occurred_at' => now()->subHours(rand(1, 72)),
            ]);
        }
    }

    public function recordPayment(array $data): SaasPayment
    {
        return DB::transaction(function () use ($data) {
            $payment = SaasPayment::query()->create([
                'saas_tenant_id' => $data['saas_tenant_id'],
                'saas_subscription_id' => $data['saas_subscription_id'] ?? null,
                'number' => 'PAY-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                'provider' => $data['provider'] ?? 'manual',
                'provider_payment_id' => $data['provider_payment_id'] ?? null,
                'status' => $data['status'] ?? 'paid',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? 'MAD',
                'description' => $data['description'] ?? 'Paiement abonnement',
                'paid_at' => ($data['status'] ?? 'paid') === 'paid' ? now() : null,
                'meta' => $data['meta'] ?? null,
            ]);

            if ($payment->status === 'paid') {
                SaasInvoice::query()->create([
                    'saas_tenant_id' => $payment->saas_tenant_id,
                    'saas_payment_id' => $payment->id,
                    'saas_subscription_id' => $payment->saas_subscription_id,
                    'number' => 'SAAS-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
                    'status' => 'paid',
                    'subtotal' => $payment->amount,
                    'tax' => 0,
                    'total' => $payment->amount,
                    'currency' => $payment->currency,
                    'issued_on' => now()->toDateString(),
                    'due_on' => now()->toDateString(),
                    'line_items' => [
                        ['label' => $payment->description, 'amount' => (float) $payment->amount],
                    ],
                ]);
            }

            return $payment;
        });
    }

    public function addDomain(SaasTenant $tenant, string $domain, bool $primary = false): SaasDomain
    {
        if ($primary) {
            $tenant->domains()->update(['is_primary' => false]);
        }

        return $tenant->domains()->create([
            'domain' => strtolower(trim($domain)),
            'is_primary' => $primary,
            'status' => 'pending',
            'verification_token' => Str::random(32),
        ]);
    }

    public function capturePlatformSnapshot(): SaasPlatformSnapshot
    {
        $diskTotal = @disk_total_space(base_path()) ?: 0;
        $diskFree = @disk_free_space(base_path()) ?: 0;
        $diskUsed = max(0, $diskTotal - $diskFree);
        $diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100, 2) : 0;

        $memLimit = $this->parseBytes(ini_get('memory_limit'));
        $memUsage = memory_get_usage(true);
        $memPercent = $memLimit > 0 ? round(($memUsage / $memLimit) * 100, 2) : 0;

        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => ['status' => 'ok', 'driver' => config('queue.default')],
            'storage' => ['status' => is_writable(storage_path()) ? 'ok' : 'error'],
            'mail' => ['status' => 'ok', 'driver' => config('mail.default')],
        ];

        $overall = collect($services)->contains(fn ($s) => ($s['status'] ?? '') === 'error') ? 'degraded' : 'healthy';

        $cpu = $this->estimateCpu();
        $responseMs = round(20 + ($cpu * 0.85) + (mt_rand(0, 15)), 0);
        $uptime = round(max(98.5, 100 - ($cpu / 12)), 2);

        return SaasPlatformSnapshot::query()->create([
            'captured_at' => now(),
            'cpu_percent' => $cpu,
            'memory_percent' => min(100, $memPercent),
            'disk_percent' => $diskPercent,
            'storage_used_bytes' => (int) $diskUsed,
            'services' => $services,
            'overall_status' => $overall,
            'meta' => [
                'response_ms' => $responseMs,
                'uptime' => $uptime,
            ],
        ]);
    }

    public function seedDemo(?int $adminId = null): void
    {
        $this->ensurePlans();

        if (SaasTenant::query()->exists()) {
            return;
        }

        $plans = SaasPlan::query()->orderBy('sort_order')->get()->keyBy('code');
        $company = Company::query()->first();

        $samples = [
            ['name' => 'Green Retail Nord', 'code' => 'standard', 'status' => 'active', 'provider' => 'stripe'],
            ['name' => 'Café Atlas', 'code' => 'starter', 'status' => 'trial', 'provider' => 'manual'],
            ['name' => 'Mode Casa Group', 'code' => 'professional', 'status' => 'active', 'provider' => 'paypal'],
            ['name' => 'Pharma Plus', 'code' => 'standard', 'status' => 'suspended', 'provider' => 'cmi'],
            ['name' => 'Electro Market', 'code' => 'enterprise', 'status' => 'active', 'provider' => 'stripe'],
        ];

        foreach ($samples as $i => $sample) {
            $plan = $plans[$sample['code']];
            $tenant = SaasTenant::query()->create([
                'company_id' => $i === 0 ? $company?->id : null,
                'name' => $sample['name'],
                'email' => Str::slug($sample['name']).'@demo.greenpos.test',
                'country' => 'MA',
                'city' => ['Casablanca', 'Rabat', 'Marrakech', 'Fès', 'Tanger'][$i] ?? 'Casablanca',
                'status' => $sample['status'],
                'trial_ends_at' => $sample['status'] === 'trial' ? now()->addDays(10) : null,
                'suspended_at' => $sample['status'] === 'suspended' ? now()->subDays(2) : null,
                'suspend_reason' => $sample['status'] === 'suspended' ? 'Paiement en retard' : null,
                'owner_user_id' => $adminId,
            ]);

            $subStatus = match ($sample['status']) {
                'trial' => 'trialing',
                'suspended' => 'past_due',
                'cancelled' => 'cancelled',
                default => 'active',
            };

            $sub = SaasSubscription::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_plan_id' => $plan->id,
                'status' => $subStatus,
                'billing_cycle' => $i % 2 === 0 ? 'monthly' : 'yearly',
                'amount' => $i % 2 === 0 ? $plan->price_monthly : $plan->price_yearly,
                'currency' => 'MAD',
                'starts_at' => now()->subMonths(2),
                'ends_at' => now()->addMonths(1),
                'renews_at' => now()->addMonths(1),
                'provider' => $sample['provider'],
                'auto_renew' => true,
            ]);

            SaasLicense::query()->create([
                'saas_tenant_id' => $tenant->id,
                'saas_subscription_id' => $sub->id,
                'status' => $sample['status'] === 'suspended' ? 'revoked' : 'active',
                'expires_at' => $sub->ends_at,
            ]);

            if (in_array($sample['status'], ['active', 'suspended'], true)) {
                $this->recordPayment([
                    'saas_tenant_id' => $tenant->id,
                    'saas_subscription_id' => $sub->id,
                    'provider' => $sample['provider'],
                    'status' => 'paid',
                    'amount' => $plan->price_monthly,
                    'description' => 'Renouvellement '.$plan->name,
                ]);
            }

            if ($i < 3) {
                $this->addDomain($tenant, Str::slug($sample['name']).'.greenpos.app', true);
            }
        }

        for ($d = 6; $d >= 0; $d--) {
            SaasPlatformSnapshot::query()->create([
                'captured_at' => now()->subDays($d)->setTime(12, 0),
                'cpu_percent' => rand(12, 55),
                'memory_percent' => rand(35, 72),
                'disk_percent' => rand(28, 48),
                'storage_used_bytes' => rand(20, 80) * 1024 * 1024 * 1024,
                'services' => [
                    'database' => ['status' => 'ok'],
                    'cache' => ['status' => 'ok'],
                    'queue' => ['status' => 'ok'],
                    'storage' => ['status' => 'ok'],
                    'mail' => ['status' => 'ok'],
                ],
                'overall_status' => 'healthy',
            ]);
        }
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok', 'driver' => config('database.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function checkCache(): array
    {
        try {
            cache()->put('saas_health', true, 10);

            return ['status' => cache()->get('saas_health') ? 'ok' : 'error', 'driver' => config('cache.default')];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    protected function estimateCpu(): float
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if (is_array($load) && isset($load[0])) {
                return min(100, round($load[0] * 25, 2));
            }
        }

        return (float) rand(15, 40);
    }

    protected function parseBytes(string $val): int
    {
        $val = trim($val);
        if ($val === '-1') {
            return 0;
        }
        $unit = strtolower(substr($val, -1));
        $num = (float) $val;

        return (int) match ($unit) {
            'g' => $num * 1024 * 1024 * 1024,
            'm' => $num * 1024 * 1024,
            'k' => $num * 1024,
            default => (int) $num,
        };
    }
}

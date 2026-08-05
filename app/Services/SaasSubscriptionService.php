<?php

namespace App\Services;

use App\Models\SaasLicense;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasSubscriptionAlert;
use App\Models\SaasTenant;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SaasSubscriptionService
{
    public function __construct(private SaasService $saas)
    {
    }

    public function dashboardStats(): array
    {
        $base = SaasSubscription::query();
        $total = (clone $base)->count();
        $newThisMonth = (clone $base)->where('created_at', '>=', now()->startOfMonth())->count();
        $renewals = (clone $base)->where('renewal_count', '>', 0)
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();
        $cancellations = (clone $base)->where('status', 'cancelled')
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();

        $active = SaasSubscription::query()->where('status', 'active')->get();
        $mrr = $active->sum(fn (SaasSubscription $s) => $s->monthlyEquivalent());

        $eligibleRenewals = SaasSubscription::query()
            ->whereIn('status', ['active', 'cancelled', 'expired'])
            ->where('ends_at', '>=', now()->subMonths(3))
            ->count();
        $renewed = SaasSubscription::query()->where('renewal_count', '>', 0)->count();
        $renewalRate = $eligibleRenewals > 0 ? round(($renewed / $eligibleRenewals) * 100, 1) : 0;

        $byStatus = SaasSubscription::query()
            ->selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        $byMonth = collect(range(5, 0))->map(function (int $i) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->translatedFormat('M'),
                'created' => SaasSubscription::query()->whereBetween('created_at', [$month, (clone $month)->endOfMonth()])->count(),
                'cancelled' => SaasSubscription::query()->where('status', 'cancelled')
                    ->whereBetween('cancelled_at', [$month, (clone $month)->endOfMonth()])->count(),
                'revenue' => (float) SaasPayment::query()->where('status', 'paid')
                    ->whereBetween('paid_at', [$month, (clone $month)->endOfMonth()])->sum('amount'),
            ];
        });

        return [
            'total' => $total,
            'new' => $newThisMonth,
            'renewals' => $renewals,
            'cancellations' => $cancellations,
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'renewal_rate' => $renewalRate,
            'by_status' => $byStatus,
            'by_month' => $byMonth,
            'alerts' => SaasSubscriptionAlert::query()->where('is_read', false)->latest()->limit(8)->with('tenant')->get(),
            'expiring' => SaasSubscription::query()
                ->whereIn('status', ['active', 'trialing'])
                ->whereBetween('ends_at', [now(), now()->addDays(14)])
                ->with(['tenant', 'plan'])
                ->orderBy('ends_at')
                ->limit(8)
                ->get(),
            'recent' => SaasSubscription::query()->with(['tenant', 'plan'])->latest()->limit(8)->get(),
        ];
    }

    public function create(array $data): SaasSubscription
    {
        return DB::transaction(function () use ($data) {
            $plan = SaasPlan::query()->findOrFail($data['saas_plan_id']);
            $cycle = $data['billing_cycle'] ?? 'monthly';
            $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $status = $data['status'] ?? 'trialing';

            $starts = now();
            $trialDays = (int) ($plan->trial_days ?: 14);
            $ends = $status === 'trialing'
                ? now()->addDays($trialDays)
                : ($cycle === 'yearly' ? now()->addYear() : now()->addMonth());

            $sub = SaasSubscription::query()->create([
                'saas_tenant_id' => $data['saas_tenant_id'],
                'saas_plan_id' => $plan->id,
                'status' => $status,
                'billing_cycle' => $cycle,
                'amount' => $data['amount'] ?? $amount,
                'currency' => $plan->currency,
                'starts_at' => $starts,
                'trial_ends_at' => $status === 'trialing' ? $ends : null,
                'ends_at' => $ends,
                'renews_at' => $ends,
                'provider' => $data['provider'] ?? 'manual',
                'auto_renew' => $data['auto_renew'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

            SaasLicense::query()->create([
                'saas_tenant_id' => $sub->saas_tenant_id,
                'saas_subscription_id' => $sub->id,
                'status' => 'active',
                'expires_at' => $sub->ends_at,
            ]);

            $tenant = SaasTenant::query()->find($sub->saas_tenant_id);
            if ($tenant && $status === 'trialing') {
                $tenant->update(['status' => 'trial', 'trial_ends_at' => $ends]);
            } elseif ($tenant && $status === 'active') {
                $tenant->update(['status' => 'active']);
            }

            return $sub->fresh(['tenant', 'plan']);
        });
    }

    public function update(SaasSubscription $subscription, array $data): SaasSubscription
    {
        $plan = isset($data['saas_plan_id'])
            ? SaasPlan::query()->findOrFail($data['saas_plan_id'])
            : $subscription->plan;

        $cycle = $data['billing_cycle'] ?? $subscription->billing_cycle;
        $amount = $data['amount'] ?? ($cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly);

        $subscription->update([
            'saas_plan_id' => $plan->id,
            'billing_cycle' => $cycle,
            'amount' => $amount,
            'provider' => $data['provider'] ?? $subscription->provider,
            'auto_renew' => array_key_exists('auto_renew', $data) ? (bool) $data['auto_renew'] : $subscription->auto_renew,
            'notes' => $data['notes'] ?? $subscription->notes,
            'ends_at' => $data['ends_at'] ?? $subscription->ends_at,
            'renews_at' => $data['renews_at'] ?? $subscription->renews_at,
        ]);

        return $subscription->fresh(['tenant', 'plan']);
    }

    public function suspend(SaasSubscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspend_reason' => $reason,
            'auto_renew' => false,
        ]);

        $subscription->tenant?->update(['status' => 'suspended', 'suspended_at' => now(), 'suspend_reason' => $reason]);

        $this->alert($subscription, 'suspended', 'critical', 'Abonnement suspendu', $reason ?: 'Suspension manuelle');
    }

    public function reactivate(SaasSubscription $subscription): void
    {
        $ends = $subscription->ends_at && $subscription->ends_at->isFuture()
            ? $subscription->ends_at
            : ($subscription->billing_cycle === 'yearly' ? now()->addYear() : now()->addMonth());

        $subscription->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspend_reason' => null,
            'ends_at' => $ends,
            'renews_at' => $ends,
            'auto_renew' => true,
        ]);

        $subscription->tenant?->update(['status' => 'active', 'suspended_at' => null, 'suspend_reason' => null]);
    }

    public function renew(SaasSubscription $subscription, ?string $provider = null): SaasSubscription
    {
        return DB::transaction(function () use ($subscription, $provider) {
            $cycle = $subscription->billing_cycle;
            $newEnd = $cycle === 'yearly'
                ? ($subscription->ends_at?->isFuture() ? $subscription->ends_at->copy()->addYear() : now()->addYear())
                : ($subscription->ends_at?->isFuture() ? $subscription->ends_at->copy()->addMonth() : now()->addMonth());

            $subscription->update([
                'status' => 'active',
                'ends_at' => $newEnd,
                'renews_at' => $newEnd,
                'renewal_count' => $subscription->renewal_count + 1,
                'suspended_at' => null,
                'cancelled_at' => null,
                'provider' => $provider ?? $subscription->provider,
            ]);

            $this->saas->recordPayment([
                'saas_tenant_id' => $subscription->saas_tenant_id,
                'saas_subscription_id' => $subscription->id,
                'provider' => $provider ?? $subscription->provider ?? 'manual',
                'status' => 'paid',
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'description' => 'Renouvellement '.$subscription->plan?->name,
            ]);

            $subscription->licenses()->where('status', 'active')->update(['expires_at' => $newEnd]);
            $subscription->tenant?->update(['status' => 'active']);

            $this->alert($subscription, 'renewed', 'info', 'Renouvellement réussi', 'Abonnement prolongé jusqu’au '.$newEnd->format('d/m/Y'));

            return $subscription->fresh(['tenant', 'plan', 'payments']);
        });
    }

    public function cancel(SaasSubscription $subscription, ?string $reason = null): void
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancel_reason' => $reason,
            'auto_renew' => false,
        ]);

        $subscription->tenant?->update(['status' => 'cancelled']);
        $subscription->licenses()->where('status', 'active')->update(['status' => 'revoked', 'revoked_at' => now()]);

        $this->alert($subscription, 'cancelled', 'warning', 'Abonnement résilié', $reason ?: 'Résiliation manuelle');
    }

    public function markPastDue(SaasSubscription $subscription, ?string $message = null): void
    {
        $subscription->update(['status' => 'past_due', 'auto_renew' => false]);
        $this->alert($subscription, 'payment_failed', 'critical', 'Paiement échoué', $message ?: 'Le renouvellement automatique a échoué.');
    }

    public function scanExpiring(int $days = 7): int
    {
        $subs = SaasSubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereBetween('ends_at', [now(), now()->addDays($days)])
            ->with(['tenant', 'plan'])
            ->get();

        $count = 0;
        foreach ($subs as $sub) {
            $exists = SaasSubscriptionAlert::query()
                ->where('saas_subscription_id', $sub->id)
                ->where('type', 'expiring_soon')
                ->where('created_at', '>=', now()->subDays(3))
                ->exists();

            if (! $exists) {
                $this->alert(
                    $sub,
                    'expiring_soon',
                    'warning',
                    'Abonnement bientôt expiré',
                    ($sub->tenant?->name ?? 'Client').' — expire le '.$sub->ends_at->format('d/m/Y')
                );
                $count++;
            }
        }

        return $count;
    }

    public function checkLimits(SaasTenant $tenant): array
    {
        $sub = $tenant->currentSubscription;
        if ($sub) {
            $sub->load('plan');
        } else {
            $sub = $tenant->subscriptions()->latest()->with('plan')->first();
        }
        $plan = $sub?->plan;
        if (! $plan) {
            return ['ok' => true, 'breaches' => []];
        }

        $breaches = [];
        $companyId = $tenant->company_id;

        if ($companyId) {
            $users = User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))->count();
            if ($users > $plan->max_users) {
                $breaches[] = "Utilisateurs : {$users}/{$plan->max_users}";
            }

            $stores = Store::query()->where('company_id', $companyId)->count();
            if ($stores > $plan->max_stores) {
                $breaches[] = "Boutiques : {$stores}/{$plan->max_stores}";
            }
        }

        if ($breaches !== []) {
            $this->alert(
                $sub,
                'limit_exceeded',
                'critical',
                'Dépassement des limites du plan',
                implode(' · ', $breaches)
            );
        }

        return ['ok' => $breaches === [], 'breaches' => $breaches, 'plan' => $plan];
    }

    /**
     * Access control helpers for plan entitlements (usable by future middleware).
     */
    public function entitlementsForTenant(SaasTenant $tenant): array
    {
        $sub = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->latest()
            ->with('plan')
            ->first();

        if (! $sub || ! $sub->plan) {
            return [
                'allowed' => false,
                'reason' => 'no_active_subscription',
                'modules' => [],
                'api' => false,
                'custom_domain' => false,
                'backups' => false,
                'support' => false,
            ];
        }

        $plan = $sub->plan;

        return [
            'allowed' => true,
            'subscription' => $sub,
            'plan' => $plan,
            'modules' => $plan->modules ?? [],
            'api' => (bool) $plan->api_enabled,
            'custom_domain' => (bool) $plan->custom_domain_enabled,
            'backups' => (bool) $plan->backups_enabled,
            'support' => (bool) $plan->support_included,
            'max_users' => $plan->max_users,
            'max_stores' => $plan->max_stores,
            'storage_gb' => $plan->storage_gb,
            'status' => $sub->status,
        ];
    }

    public function canAccessModule(SaasTenant $tenant, string $module): bool
    {
        $ent = $this->entitlementsForTenant($tenant);
        if (! $ent['allowed']) {
            return false;
        }

        return in_array($module, $ent['modules'], true);
    }

    public function alert(SaasSubscription $subscription, string $type, string $severity, string $title, ?string $body = null): SaasSubscriptionAlert
    {
        return SaasSubscriptionAlert::query()->create([
            'saas_subscription_id' => $subscription->id,
            'saas_tenant_id' => $subscription->saas_tenant_id,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
        ]);
    }

    public function markAlertRead(SaasSubscriptionAlert $alert): void
    {
        $alert->update(['is_read' => true, 'read_at' => now()]);
    }

    public function syncPlanCatalog(): void
    {
        $defaults = [
            'starter' => [
                'name' => 'Starter', 'tagline' => 'Pour démarrer rapidement',
                'price_monthly' => 199, 'price_yearly' => 1990,
                'max_users' => 3, 'max_stores' => 1, 'storage_gb' => 5,
                'api_enabled' => false, 'support_included' => true, 'support_level' => 'email',
                'backups_enabled' => false, 'custom_domain_enabled' => false, 'trial_days' => 14,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('starter'),
                'features' => ['Support email', '1 boutique', 'Exports CSV'],
                'sort_order' => 1,
            ],
            'standard' => [
                'name' => 'Business', 'tagline' => 'Pour les commerces en croissance',
                'price_monthly' => 499, 'price_yearly' => 4990,
                'max_users' => 15, 'max_stores' => 3, 'storage_gb' => 25,
                'api_enabled' => false, 'support_included' => true, 'support_level' => 'chat',
                'backups_enabled' => true, 'custom_domain_enabled' => false, 'trial_days' => 14,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('standard'),
                'features' => ['Support chat', '3 boutiques', 'Sauvegardes', 'Rapports BI'],
                'sort_order' => 2,
            ],
            'professional' => [
                'name' => 'Professional', 'tagline' => 'Pour les réseaux multi-sites',
                'price_monthly' => 999, 'price_yearly' => 9990,
                'max_users' => 50, 'max_stores' => 10, 'storage_gb' => 100,
                'api_enabled' => true, 'support_included' => true, 'support_level' => 'priority',
                'backups_enabled' => true, 'custom_domain_enabled' => true, 'trial_days' => 14,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('professional'),
                'features' => ['API', 'Domaine custom', 'Support prioritaire', '10 boutiques'],
                'sort_order' => 3,
            ],
            'enterprise' => [
                'name' => 'Enterprise', 'tagline' => 'Sur-mesure & conformité',
                'price_monthly' => 2499, 'price_yearly' => 24990,
                'max_users' => 500, 'max_stores' => 100, 'storage_gb' => 1000,
                'api_enabled' => true, 'support_included' => true, 'support_level' => 'dedicated',
                'backups_enabled' => true, 'custom_domain_enabled' => true, 'trial_days' => 30,
                'modules' => \App\Support\ModuleCatalog::defaultModulesForPlan('enterprise'),
                'features' => ['SLA 99.9%', 'CSM dédié', 'SSO', 'Personnalisation'],
                'sort_order' => 4,
            ],
        ];

        foreach ($defaults as $code => $attrs) {
            SaasPlan::query()->updateOrCreate(
                ['code' => $code],
                array_merge($attrs, [
                    'currency' => 'MAD',
                    'is_public' => true,
                    'is_active' => true,
                    'description' => $attrs['tagline'],
                ])
            );
        }
    }
}

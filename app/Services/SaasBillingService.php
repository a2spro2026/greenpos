<?php

namespace App\Services;

use App\Billing\PaymentGatewayManager;
use App\Models\SaasInvoice;
use App\Models\SaasPayment;
use App\Models\SaasPlan;
use App\Models\SaasSubscription;
use App\Models\SaasTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaasBillingService
{
    public function __construct(
        private SaasService $saas,
        private SaasSubscriptionService $subscriptions,
        private PaymentGatewayManager $gateways,
    ) {
    }

    public function billingDashboard(): array
    {
        $active = SaasSubscription::query()->where('status', 'active');
        $trialing = SaasSubscription::query()->where('status', 'trialing');
        $expired = SaasSubscription::query()->where('status', 'expired');
        $cancelled = SaasSubscription::query()->where('status', 'cancelled');

        $activeCollection = (clone $active)->get();
        $mrr = $activeCollection->sum(fn (SaasSubscription $s) => $s->monthlyEquivalent());

        $revenueMonth = (float) SaasPayment::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount');

        $revenueYear = (float) SaasPayment::query()
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->startOfYear())
            ->sum('amount');

        $renewalsMonth = SaasSubscription::query()
            ->where('renewal_count', '>', 0)
            ->where('updated_at', '>=', now()->startOfMonth())
            ->count();

        $trialsTotal = SaasSubscription::query()->where(function ($q) {
            $q->where('status', 'trialing')->orWhereNotNull('converted_at')->orWhereNotNull('trial_ends_at');
        })->count();
        $converted = SaasSubscription::query()->whereNotNull('converted_at')->count();
        // Also count trials that became active without converted_at (legacy)
        $legacyConverted = SaasSubscription::query()
            ->whereNull('converted_at')
            ->where('status', 'active')
            ->whereNotNull('trial_ends_at')
            ->count();
        $conversionBase = max($trialsTotal, 1);
        $conversionRate = round((($converted + $legacyConverted) / $conversionBase) * 100, 1);

        $byMonth = collect(range(5, 0))->map(function (int $i) {
            $month = now()->subMonths($i)->startOfMonth();
            $end = (clone $month)->endOfMonth();

            return [
                'label' => $month->translatedFormat('M'),
                'revenue' => (float) SaasPayment::query()->where('status', 'paid')->whereBetween('paid_at', [$month, $end])->sum('amount'),
                'renewals' => SaasSubscription::query()->where('renewal_count', '>', 0)->whereBetween('updated_at', [$month, $end])->count(),
                'trials' => SaasSubscription::query()->where('status', 'trialing')->whereBetween('created_at', [$month, $end])->count()
                    + SaasSubscription::query()->whereNotNull('converted_at')->whereBetween('converted_at', [$month, $end])->count(),
                'conversions' => SaasSubscription::query()->whereNotNull('converted_at')->whereBetween('converted_at', [$month, $end])->count(),
            ];
        });

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($mrr * 12, 2),
            'revenue_month' => round($revenueMonth, 2),
            'revenue_year' => round($revenueYear, 2),
            'renewals' => $renewalsMonth,
            'active' => (clone $active)->count(),
            'expired' => (clone $expired)->count() + (clone $cancelled)->where('ends_at', '<', now())->count(),
            'trials' => (clone $trialing)->count(),
            'conversion_rate' => $conversionRate,
            'invoices_open' => SaasInvoice::query()->whereIn('status', ['issued', 'draft'])->count(),
            'invoices_paid' => SaasInvoice::query()->where('status', 'paid')->count(),
            'by_month' => $byMonth,
            'gateways' => $this->gateways->statusBoard(),
            'expiring' => SaasSubscription::query()
                ->whereIn('status', ['active', 'trialing'])
                ->whereBetween('ends_at', [now(), now()->addDays(14)])
                ->with(['tenant', 'plan'])
                ->orderBy('ends_at')
                ->limit(8)
                ->get(),
            'recent_invoices' => SaasInvoice::query()->with('tenant')->latest()->limit(8)->get(),
            'recent_payments' => SaasPayment::query()->with('tenant')->latest()->limit(8)->get(),
        ];
    }

    public function changePlan(SaasSubscription $subscription, SaasPlan $newPlan, string $direction = 'change', ?string $cycle = null): SaasSubscription
    {
        return DB::transaction(function () use ($subscription, $newPlan, $direction, $cycle) {
            $oldPlan = $subscription->plan;
            $cycle = $cycle ?: $subscription->billing_cycle;
            $amount = $cycle === 'yearly' ? $newPlan->price_yearly : $newPlan->price_monthly;

            $subscription->update([
                'saas_plan_id' => $newPlan->id,
                'billing_cycle' => $cycle,
                'amount' => $amount,
            ]);

            $label = match ($direction) {
                'upgrade' => 'Montée en gamme',
                'downgrade' => 'Descente de gamme',
                default => 'Changement de plan',
            };

            $this->subscriptions->alert(
                $subscription,
                $direction === 'upgrade' ? 'upgraded' : ($direction === 'downgrade' ? 'downgraded' : 'plan_changed'),
                'info',
                $label,
                ($oldPlan?->name ?? '?').' → '.$newPlan->name
            );

            // Invoice prorated difference on upgrade (full period amount for simplicity in SaaS admin)
            if ($direction === 'upgrade' && $subscription->status === 'active') {
                $this->chargeViaGateway($subscription, (float) $amount, $label.' — '.$newPlan->name);
            }

            return $subscription->fresh(['tenant', 'plan']);
        });
    }

    public function upgrade(SaasSubscription $subscription, int $planId, ?string $cycle = null): SaasSubscription
    {
        $newPlan = SaasPlan::query()->findOrFail($planId);
        $old = $subscription->plan;
        if ($old && $newPlan->sort_order < $old->sort_order) {
            return $this->changePlan($subscription, $newPlan, 'downgrade', $cycle);
        }

        return $this->changePlan($subscription, $newPlan, 'upgrade', $cycle);
    }

    public function downgrade(SaasSubscription $subscription, int $planId, ?string $cycle = null): SaasSubscription
    {
        $newPlan = SaasPlan::query()->findOrFail($planId);

        return $this->changePlan($subscription, $newPlan, 'downgrade', $cycle);
    }

    public function convertTrial(SaasSubscription $subscription, ?string $provider = null): SaasSubscription
    {
        return DB::transaction(function () use ($subscription, $provider) {
            $cycle = $subscription->billing_cycle;
            $ends = $cycle === 'yearly' ? now()->addYear() : now()->addMonth();
            $provider = $provider ?: $subscription->provider ?: 'manual';

            $subscription->update([
                'status' => 'active',
                'converted_at' => now(),
                'ends_at' => $ends,
                'renews_at' => $ends,
                'provider' => $provider,
                'auto_renew' => true,
            ]);

            $subscription->tenant?->update([
                'status' => 'active',
                'trial_ends_at' => null,
            ]);

            $this->chargeViaGateway(
                $subscription,
                (float) $subscription->amount,
                'Conversion essai → '.$subscription->plan?->name
            );

            $this->subscriptions->alert(
                $subscription,
                'trial_converted',
                'info',
                'Essai converti',
                'Abonnement activé jusqu’au '.$ends->format('d/m/Y')
            );

            return $subscription->fresh(['tenant', 'plan', 'payments']);
        });
    }

    public function processExpiredTrials(bool $autoConvert = true): array
    {
        $subs = SaasSubscription::query()
            ->where('status', 'trialing')
            ->where(function ($q) {
                $q->where('trial_ends_at', '<=', now())
                    ->orWhere(function ($q2) {
                        $q2->whereNull('trial_ends_at')->where('ends_at', '<=', now());
                    });
            })
            ->with(['tenant', 'plan'])
            ->get();

        $converted = 0;
        $expired = 0;

        foreach ($subs as $sub) {
            if ($autoConvert && $sub->auto_renew) {
                $this->convertTrial($sub);
                $converted++;
            } else {
                $sub->update(['status' => 'expired', 'auto_renew' => false]);
                $sub->tenant?->update(['status' => 'cancelled']);
                $this->subscriptions->alert($sub, 'trial_expired', 'warning', 'Essai expiré', 'Conversion automatique désactivée');
                $expired++;
            }
        }

        return ['converted' => $converted, 'expired' => $expired];
    }

    public function processAutoRenewals(): array
    {
        $subs = SaasSubscription::query()
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('ends_at', '<=', now()->addDay())
            ->with(['tenant', 'plan'])
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($subs as $sub) {
            try {
                $this->subscriptions->renew($sub, $sub->provider);
                // Use gateway for the charge already done in renew via recordPayment — mark reminder cleared
                $sub->update(['last_reminder_at' => null]);
                $ok++;
            } catch (\Throwable $e) {
                $this->subscriptions->markPastDue($sub, $e->getMessage());
                $failed++;
            }
        }

        return ['renewed' => $ok, 'failed' => $failed];
    }

    public function sendRenewalReminders(int $days = 7): int
    {
        $subs = SaasSubscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereBetween('ends_at', [now(), now()->addDays($days)])
            ->where(function ($q) {
                $q->whereNull('last_reminder_at')
                    ->orWhere('last_reminder_at', '<', now()->subDays(3));
            })
            ->with(['tenant', 'plan'])
            ->get();

        $count = 0;
        foreach ($subs as $sub) {
            $this->subscriptions->alert(
                $sub,
                'renewal_reminder',
                'warning',
                'Rappel de renouvellement',
                ($sub->tenant?->name ?? 'Client').' — échéance '.$sub->ends_at->format('d/m/Y').' · '
                .($sub->daysRemaining() ?? 0).' j restants'
            );
            $sub->update(['last_reminder_at' => now()]);
            $count++;
        }

        return $count;
    }

    public function chargeViaGateway(SaasSubscription $subscription, float $amount, string $description, ?string $provider = null): SaasPayment
    {
        $provider = $provider ?: ($subscription->provider ?: 'manual');
        $gateway = $this->gateways->driver($provider);
        $result = $gateway->charge($subscription, $amount, $subscription->currency ?: 'MAD', $description);

        return $this->saas->recordPayment([
            'saas_tenant_id' => $subscription->saas_tenant_id,
            'saas_subscription_id' => $subscription->id,
            'provider' => $provider,
            'provider_payment_id' => $result['provider_payment_id'] ?? null,
            'status' => $result['status'] ?? 'pending',
            'amount' => $amount,
            'currency' => $subscription->currency ?: 'MAD',
            'description' => $description,
            'meta' => $result['meta'] ?? null,
        ]);
    }

    public function issueInvoice(SaasSubscription $subscription, ?float $amount = null, string $status = 'issued'): SaasInvoice
    {
        $amount = $amount ?? (float) $subscription->amount;
        $tax = round($amount * 0.20, 2); // TVA 20% demo
        $subtotal = $amount;
        $total = $subtotal + $tax;

        return SaasInvoice::query()->create([
            'saas_tenant_id' => $subscription->saas_tenant_id,
            'saas_subscription_id' => $subscription->id,
            'number' => 'SAAS-'.now()->format('Ymd').'-'.strtoupper(Str::random(5)),
            'status' => $status,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'currency' => $subscription->currency ?: 'MAD',
            'issued_on' => now()->toDateString(),
            'due_on' => now()->addDays(15)->toDateString(),
            'line_items' => [
                [
                    'label' => 'Abonnement '.$subscription->plan?->name.' ('.$subscription->billing_cycle.')',
                    'qty' => 1,
                    'unit_price' => $subtotal,
                    'amount' => $subtotal,
                ],
                [
                    'label' => 'TVA 20%',
                    'qty' => 1,
                    'unit_price' => $tax,
                    'amount' => $tax,
                ],
            ],
        ]);
    }

    public function markInvoicePaid(SaasInvoice $invoice, ?string $provider = null): SaasInvoice
    {
        return DB::transaction(function () use ($invoice, $provider) {
            $sub = $invoice->subscription;
            $provider = $provider ?: ($sub?->provider ?: 'manual');

            if (! $invoice->saas_payment_id && $sub) {
                $payment = $this->chargeViaGateway($sub, (float) $invoice->total, 'Paiement facture '.$invoice->number, $provider);
                $invoice->saas_payment_id = $payment->id;
            }

            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'saas_payment_id' => $invoice->saas_payment_id,
            ]);

            return $invoice->fresh(['tenant', 'payment', 'subscription']);
        });
    }

    public function voidInvoice(SaasInvoice $invoice): SaasInvoice
    {
        $invoice->update(['status' => 'void']);

        return $invoice->fresh();
    }

    public function createPlan(array $data): SaasPlan
    {
        $code = $data['code'] ?? Str::slug($data['name']);

        return SaasPlan::query()->create([
            'code' => $code,
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'] ?? 0,
            'price_yearly' => $data['price_yearly'] ?? 0,
            'currency' => $data['currency'] ?? 'MAD',
            'max_users' => $data['max_users'] ?? 5,
            'max_stores' => $data['max_stores'] ?? 1,
            'storage_gb' => $data['storage_gb'] ?? 5,
            'api_enabled' => (bool) ($data['api_enabled'] ?? false),
            'support_included' => (bool) ($data['support_included'] ?? true),
            'support_level' => $data['support_level'] ?? 'email',
            'backups_enabled' => (bool) ($data['backups_enabled'] ?? false),
            'custom_domain_enabled' => (bool) ($data['custom_domain_enabled'] ?? false),
            'trial_days' => $data['trial_days'] ?? 14,
            'modules' => $data['modules'] ?? [],
            'features' => $data['features'] ?? [],
            'is_public' => (bool) ($data['is_public'] ?? true),
            'is_active' => (bool) ($data['is_active'] ?? true),
            'sort_order' => $data['sort_order'] ?? ((int) SaasPlan::query()->max('sort_order') + 1),
        ]);
    }
}

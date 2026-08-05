<?php

namespace App\Billing\Gateways;

use App\Models\SaasSubscription;

class StripeGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return 'Stripe';
    }

    public function isConfigured(): bool
    {
        $creds = $this->config?->credentials ?? [];

        return parent::isConfigured() && ! empty($creds['secret_key'] ?? null);
    }

    public function charge(SaasSubscription $subscription, float $amount, string $currency, string $description = ''): array
    {
        if (! $this->isConfigured()) {
            // Sandbox stub: simulate successful Stripe PaymentIntent when keys missing in demo
            return $this->ok('Stripe (sandbox) — PaymentIntent simulé', [
                'meta' => [
                    'gateway' => 'stripe',
                    'mode' => 'sandbox',
                    'simulated' => true,
                    'amount' => $amount,
                    'currency' => $currency,
                    'description' => $description,
                ],
            ]);
        }

        // Extensible: replace with real Stripe SDK PaymentIntent::create(...)
        return $this->pending(
            'Stripe Checkout prêt — finaliser via webhook',
            '/superadmin/payments?provider=stripe',
            [
                'meta' => [
                    'gateway' => 'stripe',
                    'mode' => $this->config?->mode ?? 'test',
                    'amount' => $amount,
                    'currency' => $currency,
                    'customer' => $subscription->tenant?->email,
                ],
            ]
        );
    }
}

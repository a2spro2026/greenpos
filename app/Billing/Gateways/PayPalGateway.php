<?php

namespace App\Billing\Gateways;

use App\Models\SaasSubscription;

class PayPalGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'paypal';
    }

    public function label(): string
    {
        return 'PayPal';
    }

    public function isConfigured(): bool
    {
        $creds = $this->config?->credentials ?? [];

        return parent::isConfigured() && ! empty($creds['client_id'] ?? null);
    }

    public function charge(SaasSubscription $subscription, float $amount, string $currency, string $description = ''): array
    {
        if (! $this->isConfigured()) {
            return $this->ok('PayPal (sandbox) — ordre simulé', [
                'meta' => [
                    'gateway' => 'paypal',
                    'mode' => 'sandbox',
                    'simulated' => true,
                    'amount' => $amount,
                    'currency' => $currency,
                ],
            ]);
        }

        return $this->pending(
            'PayPal Order créé — en attente d’approbation',
            'https://www.sandbox.paypal.com/checkoutnow',
            ['meta' => ['gateway' => 'paypal', 'amount' => $amount, 'currency' => $currency]]
        );
    }
}

<?php

namespace App\Billing\Gateways;

use App\Models\SaasSubscription;

class CmiGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'cmi';
    }

    public function label(): string
    {
        return 'CMI';
    }

    public function isConfigured(): bool
    {
        $creds = $this->config?->credentials ?? [];

        return parent::isConfigured() && ! empty($creds['merchant_id'] ?? null);
    }

    public function charge(SaasSubscription $subscription, float $amount, string $currency, string $description = ''): array
    {
        if (! $this->isConfigured()) {
            return $this->ok('CMI (sandbox) — transaction 3-D Secure simulée', [
                'meta' => [
                    'gateway' => 'cmi',
                    'mode' => 'sandbox',
                    'simulated' => true,
                    'amount' => $amount,
                    'currency' => $currency,
                ],
            ]);
        }

        return $this->pending(
            'Redirection CMI 3-D Secure',
            '/superadmin/payments?provider=cmi',
            ['meta' => ['gateway' => 'cmi', 'amount' => $amount, 'currency' => $currency]]
        );
    }
}

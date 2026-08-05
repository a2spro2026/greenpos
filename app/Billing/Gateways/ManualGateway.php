<?php

namespace App\Billing\Gateways;

use App\Models\SaasSubscription;

class ManualGateway extends AbstractGateway
{
    public function code(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return 'Paiement manuel';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function charge(SaasSubscription $subscription, float $amount, string $currency, string $description = ''): array
    {
        return $this->ok('Paiement manuel enregistré', [
            'meta' => [
                'gateway' => 'manual',
                'amount' => $amount,
                'currency' => $currency,
                'description' => $description,
                'recorded_by' => auth()->id(),
            ],
        ]);
    }
}

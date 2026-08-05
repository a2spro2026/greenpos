<?php

namespace App\Billing\Contracts;

use App\Models\SaasPayment;
use App\Models\SaasSubscription;

interface PaymentGatewayInterface
{
    public function code(): string;

    public function label(): string;

    public function isConfigured(): bool;

    /**
     * Initiate a charge / checkout for a subscription renewal or payment.
     * Returns a result array with keys: success, status, provider_payment_id, message, meta, redirect_url?
     */
    public function charge(SaasSubscription $subscription, float $amount, string $currency, string $description = ''): array;

    /**
     * Mark / sync a pending payment (webhook simulation or capture).
     */
    public function capture(SaasPayment $payment, array $payload = []): array;

    public function refund(SaasPayment $payment, ?float $amount = null): array;
}

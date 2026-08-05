<?php

namespace App\Billing\Gateways;

use App\Billing\Contracts\PaymentGatewayInterface;
use App\Models\SaasPayment;
use App\Models\SaasPaymentGateway;
use App\Models\SaasSubscription;
use Illuminate\Support\Str;

abstract class AbstractGateway implements PaymentGatewayInterface
{
    public function __construct(protected ?SaasPaymentGateway $config = null)
    {
    }

    public function isConfigured(): bool
    {
        if (! $this->config) {
            return false;
        }

        return $this->config->is_enabled && $this->config->status !== 'disabled';
    }

    protected function ok(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'status' => 'paid',
            'provider_payment_id' => strtoupper($this->code()).'_'.Str::upper(Str::random(12)),
            'message' => $message,
            'meta' => ['gateway' => $this->code(), 'mode' => $this->config?->mode ?? 'test'],
        ], $extra);
    }

    protected function pending(string $message, ?string $redirect = null, array $extra = []): array
    {
        return array_merge([
            'success' => true,
            'status' => 'pending',
            'provider_payment_id' => strtoupper($this->code()).'_'.Str::upper(Str::random(12)),
            'message' => $message,
            'redirect_url' => $redirect,
            'meta' => ['gateway' => $this->code(), 'mode' => $this->config?->mode ?? 'test'],
        ], $extra);
    }

    protected function fail(string $message, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'status' => 'failed',
            'provider_payment_id' => null,
            'message' => $message,
            'meta' => ['gateway' => $this->code()],
        ], $extra);
    }

    public function capture(SaasPayment $payment, array $payload = []): array
    {
        return $this->ok('Paiement capturé ('.$this->label().')', [
            'provider_payment_id' => $payment->provider_payment_id ?: strtoupper($this->code()).'_CAP_'.Str::upper(Str::random(8)),
            'meta' => array_merge($payment->meta ?? [], $payload, ['captured_at' => now()->toIso8601String()]),
        ]);
    }

    public function refund(SaasPayment $payment, ?float $amount = null): array
    {
        return $this->ok('Remboursement initié ('.$this->label().')', [
            'status' => 'refunded',
            'meta' => [
                'refund_amount' => $amount ?? (float) $payment->amount,
                'refunded_at' => now()->toIso8601String(),
            ],
        ]);
    }
}

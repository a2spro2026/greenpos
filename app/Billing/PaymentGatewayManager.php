<?php

namespace App\Billing;

use App\Billing\Contracts\PaymentGatewayInterface;
use App\Billing\Gateways\CmiGateway;
use App\Billing\Gateways\ManualGateway;
use App\Billing\Gateways\PayPalGateway;
use App\Billing\Gateways\StripeGateway;
use App\Models\SaasPaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /** @var array<string, class-string<PaymentGatewayInterface>> */
    protected array $drivers = [
        'stripe' => StripeGateway::class,
        'paypal' => PayPalGateway::class,
        'cmi' => CmiGateway::class,
        'manual' => ManualGateway::class,
    ];

    public function register(string $code, string $class): void
    {
        $this->drivers[$code] = $class;
    }

    public function driver(string $code): PaymentGatewayInterface
    {
        if (! isset($this->drivers[$code])) {
            throw new InvalidArgumentException("Passerelle de paiement inconnue : {$code}");
        }

        $config = SaasPaymentGateway::query()->where('code', $code)->first();

        return new $this->drivers[$code]($config);
    }

    public function codes(): array
    {
        return array_keys($this->drivers);
    }

    public function ensureDefaults(): void
    {
        $defs = [
            'stripe' => ['name' => 'Stripe', 'is_enabled' => true, 'status' => 'ready'],
            'paypal' => ['name' => 'PayPal', 'is_enabled' => true, 'status' => 'ready'],
            'cmi' => ['name' => 'CMI Maroc', 'is_enabled' => true, 'status' => 'ready'],
            'manual' => ['name' => 'Paiement manuel', 'is_enabled' => true, 'status' => 'connected'],
        ];

        foreach ($defs as $code => $attrs) {
            SaasPaymentGateway::query()->firstOrCreate(
                ['code' => $code],
                array_merge($attrs, [
                    'is_sandbox' => true,
                    'mode' => 'test',
                    'credentials' => [],
                    'settings' => [],
                    'status_message' => 'Connecteur prêt — configurer les clés API pour la production.',
                ])
            );
        }
    }

    /** @return list<array{code:string,label:string,configured:bool,enabled:bool,status:string}> */
    public function statusBoard(): array
    {
        $this->ensureDefaults();

        return collect($this->codes())->map(function (string $code) {
            $gw = $this->driver($code);
            $row = SaasPaymentGateway::query()->where('code', $code)->first();

            return [
                'code' => $code,
                'label' => $gw->label(),
                'configured' => $gw->isConfigured(),
                'enabled' => (bool) ($row?->is_enabled),
                'status' => $row?->status ?? 'ready',
                'mode' => $row?->mode ?? 'test',
                'message' => $row?->status_message,
            ];
        })->all();
    }
}

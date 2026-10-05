<?php

namespace App\Services;

use App\Models\CustomList;
use App\Models\PaymentCollection;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Support\Workspace;
use Illuminate\Validation\ValidationException;

class SalePaymentWorkflowService
{
    public const STATUSES = [
        'to_pay' => 'À payer',
        'to_collect' => 'À encaisser',
        'paid' => 'Payé',
        'collected' => 'Encaissé',
        'unpaid' => 'Impayé',
    ];

    public const OPEN_COLLECTION = ['scheduled', 'pending', 'overdue'];

    public const SETTLED_COLLECTION = ['collected', 'confirmed'];

    public function resolve(int $companyId, array $input): array
    {
        $list = null;
        if (! empty($input['custom_list_id'])) {
            $list = CustomList::query()
                ->forCompany($companyId)
                ->where('type', 'mode_de_paiement')
                ->whereKey($input['custom_list_id'])
                ->first();
            if (! $list) {
                throw ValidationException::withMessages(['payments' => 'Mode de paiement inconnu.']);
            }
        }

        $method = $list?->meta('method') ?: ($input['method'] ?? 'cash');
        if (! array_key_exists($method, SalePayment::METHODS)) {
            throw ValidationException::withMessages(['payments' => 'Mode de paiement invalide.']);
        }

        $timing = $list?->meta('timing', 'immediate') ?: 'immediate';
        $isDeferred = $timing === 'deferred' || ! empty($input['is_deferred']);
        $required = array_values(array_filter((array) ($list?->meta('required_fields', []) ?? [])));

        foreach ($required as $field) {
            if (! array_key_exists($field, CustomList::PAYMENT_FIELDS)) {
                continue;
            }
            if (! filled($input[$field] ?? null)) {
                throw ValidationException::withMessages([
                    $field => CustomList::PAYMENT_FIELDS[$field].' est obligatoire pour '.$list->name.'.',
                ]);
            }
        }

        $amount = round((float) ($input['amount'] ?? 0), 2);
        $received = isset($input['received_amount']) ? (float) $input['received_amount'] : (isset($input['tendered']) ? (float) $input['tendered'] : null);
        $change = $received !== null ? max(0, round($received - $amount, 2)) : (isset($input['change_amount']) ? (float) $input['change_amount'] : null);

        return [
            'method' => $method,
            'custom_list_id' => $list?->id,
            'is_deferred' => $isDeferred,
            'amount' => $amount,
            'reference' => $input['reference'] ?? ($input['transaction_number'] ?? $input['piece_number'] ?? null),
            'transfer_mode' => $input['transfer_mode'] ?? null,
            'transaction_number' => $input['transaction_number'] ?? null,
            'piece_number' => $input['piece_number'] ?? null,
            'bank_name' => $input['bank_name'] ?? null,
            'issue_date' => $input['issue_date'] ?? null,
            'due_date' => $input['due_date'] ?? ($isDeferred ? now()->addDays(7)->toDateString() : null),
            'confirmed_at' => $isDeferred ? null : now(),
            'collection_status' => $isDeferred ? 'scheduled' : 'confirmed',
            'received_amount' => $received,
            'change_amount' => $method === 'cash' ? $change : null,
            'scheduled_for' => $isDeferred ? ($input['due_date'] ?? $input['scheduled_for'] ?? now()->addDays(7)) : null,
            'tendered' => $received,
            'notes' => $input['notes'] ?? null,
            'paid_at' => $input['paid_at'] ?? now()->toDateString(),
        ];
    }

    /** @param  list<array<string, mixed>>  $resolved */
    public function assertCovers(float $total, array $resolved): void
    {
        $covered = 0.0;
        foreach ($resolved as $payment) {
            $covered += (float) ($payment['amount'] ?? 0);
        }
        if (round($covered, 2) + 0.009 < round($total, 2)) {
            throw ValidationException::withMessages(['payments' => 'Paiement insuffisant.']);
        }
    }

    public function settledAmount(iterable $payments): float
    {
        $sum = 0.0;
        foreach ($payments as $payment) {
            if ($this->countsAsSettled($payment)) {
                $sum += (float) $payment->amount;
            }
        }

        return round($sum, 2);
    }

    public function openDeferredAmount(iterable $payments): float
    {
        $sum = 0.0;
        foreach ($payments as $payment) {
            if ($payment->is_deferred && in_array($payment->collection_status, self::OPEN_COLLECTION, true)) {
                $sum += (float) $payment->amount;
            }
        }

        return round($sum, 2);
    }

    public function hasCollectedDeferred(iterable $payments): bool
    {
        foreach ($payments as $payment) {
            if ($payment->is_deferred && in_array($payment->collection_status, self::SETTLED_COLLECTION, true)) {
                return true;
            }
        }

        return false;
    }

    public function countsAsSettled(object $payment): bool
    {
        if (($payment->collection_status ?? null) === 'cancelled') {
            return false;
        }
        if (! ($payment->is_deferred ?? false)) {
            return true;
        }

        return in_array($payment->collection_status, self::SETTLED_COLLECTION, true);
    }

    public function statusCode(float $net, float $settled, float $openDeferred, bool $hadCollectedDeferred): string
    {
        if ($net <= 0.009) {
            return $hadCollectedDeferred ? 'collected' : 'paid';
        }
        if ($settled + 0.009 >= $net) {
            return $hadCollectedDeferred ? 'collected' : 'paid';
        }
        if ($openDeferred > 0.009 && ($settled + $openDeferred + 0.009) >= $net) {
            return 'to_collect';
        }
        if ($settled > 0.009) {
            return 'to_pay';
        }

        return 'unpaid';
    }

    public function recalculateSale(Sale $sale): string
    {
        $sale->loadMissing('payments');
        $net = max(0, round((float) $sale->total_ttc - (float) $sale->amount_returned, 2));
        $code = $this->statusCode(
            $net,
            $this->settledAmount($sale->payments),
            $this->openDeferredAmount($sale->payments),
            $this->hasCollectedDeferred($sale->payments)
        );
        if ($sale->payment_status_code !== $code) {
            $sale->update(['payment_status_code' => $code]);
        }

        return $code;
    }

    public function recalculatePos(PosSale $sale): string
    {
        $sale->loadMissing('payments');
        $refunded = (float) ($sale->amount_refunded ?? 0);
        $net = max(0, round((float) $sale->total_ttc - $refunded, 2));
        $code = $this->statusCode(
            $net,
            $this->settledAmount($sale->payments),
            $this->openDeferredAmount($sale->payments),
            $this->hasCollectedDeferred($sale->payments)
        );
        if ($sale->payment_status_code !== $code) {
            $sale->update(['payment_status_code' => $code]);
        }

        return $code;
    }

    public function recordHistory(int $companyId, string $action, float $amount, ?int $salePaymentId = null, ?int $posPaymentId = null, ?string $notes = null, $scheduledFor = null): PaymentCollection
    {
        return PaymentCollection::query()->create([
            'company_id' => $companyId,
            'sale_payment_id' => $salePaymentId,
            'pos_payment_id' => $posPaymentId,
            'created_by' => Workspace::user()?->id,
            'action' => $action,
            'amount' => $amount,
            'scheduled_for' => $scheduledFor,
            'notes' => $notes,
        ]);
    }
}

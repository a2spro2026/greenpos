<?php

namespace App\Services;

use App\Models\PosPayment;
use App\Models\SalePayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Follow-up for deferred SalePayment / PosPayment rows.
 * Immediate payments stay on the existing recordPayment / POS checkout path.
 */
class CollectionService
{
    public function __construct(private SalePaymentWorkflowService $workflow)
    {
    }

    public function collect(SalePayment|PosPayment $payment, ?string $notes = null): SalePayment|PosPayment
    {
        $this->assertDeferredOpen($payment);

        return DB::transaction(function () use ($payment, $notes) {
            $payment->update([
                'collection_status' => 'collected',
                'confirmed_at' => now(),
            ]);

            if ($payment instanceof SalePayment) {
                $sale = $payment->sale;
                $sale->update([
                    'amount_paid' => round((float) $sale->amount_paid + (float) $payment->amount, 2),
                ]);
                $this->workflow->recordHistory($sale->company_id, 'collected', (float) $payment->amount, $payment->id, null, $notes);
                $this->workflow->recalculateSale($sale->fresh());
            } else {
                $sale = $payment->sale;
                $this->workflow->recordHistory($sale->company_id, 'collected', (float) $payment->amount, null, $payment->id, $notes);
                $this->workflow->recalculatePos($sale->fresh());
            }

            return $payment->fresh();
        });
    }

    public function reschedule(SalePayment|PosPayment $payment, string $dueDate, ?string $notes = null): SalePayment|PosPayment
    {
        $this->assertDeferredOpen($payment);

        return DB::transaction(function () use ($payment, $dueDate, $notes) {
            $payment->update([
                'due_date' => $dueDate,
                'scheduled_for' => $dueDate,
                'collection_status' => 'scheduled',
            ]);
            $companyId = $payment->sale->company_id;
            $this->workflow->recordHistory(
                $companyId,
                'rescheduled',
                (float) $payment->amount,
                $payment instanceof SalePayment ? $payment->id : null,
                $payment instanceof PosPayment ? $payment->id : null,
                $notes,
                $dueDate
            );

            return $payment->fresh();
        });
    }

    public function cancel(SalePayment|PosPayment $payment, ?string $notes = null): SalePayment|PosPayment
    {
        if (! $payment->is_deferred) {
            throw ValidationException::withMessages(['payment' => 'Seuls les paiements différés se annulent ici.']);
        }
        if ($payment->collection_status === 'cancelled') {
            return $payment;
        }
        if (in_array($payment->collection_status, SalePaymentWorkflowService::SETTLED_COLLECTION, true)) {
            throw ValidationException::withMessages(['payment' => 'Un paiement déjà encaissé ne peut pas être annulé depuis le suivi.']);
        }

        return DB::transaction(function () use ($payment, $notes) {
            $payment->update(['collection_status' => 'cancelled']);
            $sale = $payment->sale;
            $this->workflow->recordHistory(
                $sale->company_id,
                'cancelled',
                (float) $payment->amount,
                $payment instanceof SalePayment ? $payment->id : null,
                $payment instanceof PosPayment ? $payment->id : null,
                $notes
            );
            if ($payment instanceof SalePayment) {
                $this->workflow->recalculateSale($sale->fresh());
            } else {
                $this->workflow->recalculatePos($sale->fresh());
            }

            return $payment->fresh();
        });
    }

    private function assertDeferredOpen(SalePayment|PosPayment $payment): void
    {
        if (! $payment->is_deferred) {
            throw ValidationException::withMessages(['payment' => 'Ce paiement n\'est pas différé.']);
        }
        if (! in_array($payment->collection_status, SalePaymentWorkflowService::OPEN_COLLECTION, true)) {
            throw ValidationException::withMessages(['payment' => 'Ce paiement n\'est plus en attente d\'encaissement.']);
        }
    }
}

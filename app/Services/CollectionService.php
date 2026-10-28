<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SalePayment;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectionService
{
    public function __construct(private CashJournalService $journal)
    {
    }

    public function pendingQuery(int $companyId)
    {
        return SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) {
                $q->where('is_deferred', true)->orWhere('status', 'pending');
            })
            ->where('status', '!=', 'cancelled')
            ->with(['sale.customer', 'sale.store']);
    }

    public function markCollected(SalePayment $payment, array $data = []): SalePayment
    {
        if ($payment->status === 'collected') {
            return $payment;
        }

        return DB::transaction(function () use ($payment, $data) {
            $payment->update([
                'status' => 'collected',
                'is_deferred' => false,
                'collected_at' => now(),
                'paid_at' => $payment->paid_at ?? now(),
                'collection_notes' => $data['notes'] ?? $payment->collection_notes,
            ]);

            $sale = $payment->sale;
            if ($sale) {
                $collected = (float) $sale->payments()->where('status', 'collected')->sum('amount');
                $sale->update(['amount_paid' => $collected]);
            }

            $this->journal->record([
                'entry_date' => now()->toDateString(),
                'direction' => 'in',
                'source_type' => 'collection',
                'source_id' => $payment->id,
                'label' => 'Encaissement '.$payment->sale?->number,
                'amount' => (float) $payment->amount,
                'store_id' => $sale?->store_id,
                'payment_method' => $payment->method,
                'notes' => $data['notes'] ?? null,
            ]);

            return $payment->fresh(['sale']);
        });
    }

    public function reschedule(SalePayment $payment, string $dueAt): SalePayment
    {
        $payment->update([
            'due_at' => $dueAt,
            'is_deferred' => true,
            'status' => 'pending',
        ]);

        return $payment;
    }

    public function cancel(SalePayment $payment, ?string $notes = null): SalePayment
    {
        $payment->update([
            'status' => 'cancelled',
            'collection_notes' => $notes,
        ]);

        $sale = $payment->sale;
        if ($sale) {
            $collected = (float) $sale->payments()->where('status', 'collected')->sum('amount');
            $sale->update(['amount_paid' => $collected]);
        }

        return $payment;
    }

    public function createDeferred(Sale $sale, array $data): SalePayment
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Montant invalide.']);
        }

        return SalePayment::query()->create([
            'sale_id' => $sale->id,
            'method' => $data['method'] ?? 'transfer',
            'amount' => $amount,
            'status' => 'pending',
            'is_deferred' => true,
            'due_at' => $data['due_at'] ?? now()->addDays(7),
            'reference' => $data['reference'] ?? null,
            'paid_at' => null,
            'created_by' => Workspace::user()?->id,
        ]);
    }
}

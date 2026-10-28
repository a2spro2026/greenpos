<?php

namespace App\Services;

use App\Models\CashJournalEntry;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;

class CashJournalService
{
    public function record(array $data): CashJournalEntry
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data) {
            $storeId = $data['store_id'] ?? null;
            $direction = $data['direction'];
            $amount = abs((float) $data['amount']);

            $previous = CashJournalEntry::query()
                ->forCompany($company->id)
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
                ->when(! $storeId, fn ($q) => $q->whereNull('store_id'))
                ->orderByDesc('entry_date')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            $prevBalance = $previous ? (float) $previous->balance_after : 0.0;
            $signed = $direction === 'in' ? $amount : -$amount;
            $balanceAfter = round($prevBalance + $signed, 2);

            return CashJournalEntry::query()->create([
                'company_id' => $company->id,
                'store_id' => $storeId,
                'entry_date' => $data['entry_date'],
                'direction' => $direction,
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'label' => $data['label'],
                'amount' => $amount,
                'payment_method' => $data['payment_method'] ?? null,
                'balance_after' => $balanceAfter,
                'created_by' => Workspace::user()?->id,
                'notes' => $data['notes'] ?? null,
            ]);
        });
    }

    /**
     * Rebuild running balances chronologically for a company (optionally scoped to a store).
     */
    public function rebuildBalances(?int $storeId = null): int
    {
        $company = Workspace::company();
        $count = 0;

        if ($storeId === null) {
            $byStore = CashJournalEntry::query()
                ->forCompany($company->id)
                ->orderBy('entry_date')
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($e) => $e->store_id ?? 0);

            foreach ($byStore as $group) {
                $balance = 0.0;
                foreach ($group as $entry) {
                    $signed = $entry->direction === 'in'
                        ? abs((float) $entry->amount)
                        : -abs((float) $entry->amount);
                    $balance = round($balance + $signed, 2);
                    $entry->update(['balance_after' => $balance]);
                    $count++;
                }
            }

            return $count;
        }

        $balance = 0.0;
        $entries = CashJournalEntry::query()
            ->forCompany($company->id)
            ->where('store_id', $storeId)
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        foreach ($entries as $entry) {
            $signed = $entry->direction === 'in'
                ? abs((float) $entry->amount)
                : -abs((float) $entry->amount);
            $balance = round($balance + $signed, 2);
            $entry->update(['balance_after' => $balance]);
            $count++;
        }

        return $count;
    }
}

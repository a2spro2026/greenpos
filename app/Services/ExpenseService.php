<?php

namespace App\Services;

use App\Models\Expense;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private CashJournalService $cashJournal)
    {
    }

    public function create(array $data, bool $postToJournal = true): Expense
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $postToJournal) {
            $expense = Expense::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'created_by' => Workspace::user()?->id,
                'label' => $data['label'],
                'category' => $data['category'] ?? 'other',
                'expense_type' => $data['expense_type'] ?? 'variable',
                'amount' => $data['amount'],
                'currency' => $data['currency'] ?? ($company->currency ?? 'MAD'),
                'expense_date' => $data['expense_date'],
                'payment_method' => $data['payment_method'] ?? null,
                'is_recurring' => (bool) ($data['is_recurring'] ?? false),
                'recurrence' => ! empty($data['is_recurring']) ? ($data['recurrence'] ?? null) : null,
                'next_due_date' => $data['next_due_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($postToJournal) {
                $this->cashJournal->record([
                    'store_id' => $expense->store_id,
                    'entry_date' => $expense->expense_date->format('Y-m-d'),
                    'direction' => 'out',
                    'source_type' => 'expense',
                    'source_id' => $expense->id,
                    'label' => $expense->label,
                    'amount' => $expense->amount,
                    'payment_method' => $expense->payment_method,
                    'notes' => $expense->notes,
                ]);
            }

            return $expense->fresh(['store', 'supplier', 'creator']);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        $expense->update([
            'store_id' => $data['store_id'] ?? null,
            'supplier_id' => $data['supplier_id'] ?? null,
            'label' => $data['label'],
            'category' => $data['category'] ?? 'other',
            'expense_type' => $data['expense_type'] ?? 'variable',
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? $expense->currency,
            'expense_date' => $data['expense_date'],
            'payment_method' => $data['payment_method'] ?? null,
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'recurrence' => ! empty($data['is_recurring']) ? ($data['recurrence'] ?? null) : null,
            'next_due_date' => $data['next_due_date'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        return $expense->fresh(['store', 'supplier', 'creator']);
    }

    public function softDelete(Expense $expense): void
    {
        $expense->delete();
    }
}

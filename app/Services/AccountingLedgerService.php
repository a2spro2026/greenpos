<?php

namespace App\Services;

use App\Models\AccountAccount;
use App\Models\AccountEntry;
use App\Models\AccountEntryLine;
use App\Models\Invoice;
use App\Models\Sale;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingLedgerService
{
    public function seedDefaultAccounts(int $companyId): void
    {
        $defaults = [
            ['code' => '101', 'name' => 'Caisse', 'type' => 'asset'],
            ['code' => '411', 'name' => 'Clients', 'type' => 'asset'],
            ['code' => '401', 'name' => 'Fournisseurs', 'type' => 'liability'],
            ['code' => '701', 'name' => 'Ventes', 'type' => 'income'],
            ['code' => '607', 'name' => 'Achats', 'type' => 'expense'],
            ['code' => '613', 'name' => 'Charges diverses', 'type' => 'expense'],
            ['code' => '445', 'name' => 'TVA collectée', 'type' => 'liability'],
            ['code' => '4456', 'name' => 'TVA déductible', 'type' => 'asset'],
            ['code' => '120', 'name' => 'Résultat', 'type' => 'equity'],
        ];

        foreach ($defaults as $row) {
            AccountAccount::query()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $row['code']],
                ['name' => $row['name'], 'type' => $row['type'], 'is_active' => true]
            );
        }
    }

    public function createEntry(array $data, array $lines): AccountEntry
    {
        $company = Workspace::company();
        $debit = collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0));
        $credit = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));

        if (round($debit, 2) !== round($credit, 2) || $debit <= 0) {
            throw ValidationException::withMessages(['lines' => 'Écriture non équilibrée (débit = crédit requis).']);
        }

        return DB::transaction(function () use ($company, $data, $lines) {
            $seq = AccountEntry::query()->forCompany($company->id)->count() + 1;
            $entry = AccountEntry::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'] ?? Workspace::store()?->id,
                'entry_date' => $data['entry_date'] ?? now()->toDateString(),
                'number' => $data['number'] ?? ('EC-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT)),
                'label' => $data['label'],
                'source_type' => $data['source_type'] ?? 'manual',
                'source_id' => $data['source_id'] ?? null,
                'created_by' => Workspace::user()?->id,
            ]);

            foreach ($lines as $line) {
                AccountEntryLine::query()->create([
                    'account_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                    'label' => $line['label'] ?? null,
                ]);
            }

            return $entry->load('lines.account');
        });
    }

    public function bilanSummary(int $companyId, ?string $asOf = null): array
    {
        $asOf = $asOf ?: now()->toDateString();
        $accounts = AccountAccount::query()->forCompany($companyId)->where('is_active', true)->orderBy('code')->get();

        $totals = ['asset' => 0, 'liability' => 0, 'equity' => 0, 'income' => 0, 'expense' => 0];
        $rows = [];

        foreach ($accounts as $account) {
            $debit = (float) AccountEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($q) => $q->where('company_id', $companyId)->whereDate('entry_date', '<=', $asOf))
                ->sum('debit');
            $credit = (float) AccountEntryLine::query()
                ->where('account_id', $account->id)
                ->whereHas('entry', fn ($q) => $q->where('company_id', $companyId)->whereDate('entry_date', '<=', $asOf))
                ->sum('credit');

            $balance = in_array($account->type, ['asset', 'expense'], true)
                ? $debit - $credit
                : $credit - $debit;

            $totals[$account->type] = ($totals[$account->type] ?? 0) + $balance;
            $rows[] = [
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->type,
                'balance' => round($balance, 2),
            ];
        }

        return [
            'as_of' => $asOf,
            'rows' => $rows,
            'totals' => array_map(fn ($v) => round($v, 2), $totals),
            'result' => round(($totals['income'] ?? 0) - ($totals['expense'] ?? 0), 2),
        ];
    }

    public function vatSummary(int $companyId, string $from, string $to): array
    {
        $salesVat = (float) Sale::query()
            ->forCompany($companyId)
            ->whereBetween('sold_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('tax_total');

        $invoiceVat = (float) Invoice::query()
            ->where('company_id', $companyId)
            ->whereBetween('invoiced_at', [$from, $to])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->sum('tax_total');

        return [
            'from' => $from,
            'to' => $to,
            'sales_vat' => round($salesVat, 2),
            'invoice_vat' => round($invoiceVat, 2),
            'collected' => round($salesVat + $invoiceVat, 2),
        ];
    }
}

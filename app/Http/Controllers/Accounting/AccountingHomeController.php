<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CashJournalEntry;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PosPayment;
use App\Models\SupplierReturn;
use App\Services\SalePaymentWorkflowService;
use App\Support\Workspace;
use Illuminate\View\View;

// Rimal V2 — page d'accueil Comptabilité : indicateurs du mois + accès rapides.
class AccountingHomeController extends Controller
{
    public function index(): View
    {
        $this->authorize('accounting.view');
        $company = Workspace::company();
        $cid = $company->id;
        $from = now()->startOfMonth()->toDateString();
        $to = now()->endOfMonth()->toDateString();

        $cash = CashJournalEntry::query()->where('company_id', $cid)->whereBetween('entry_date', [$from, $to])
            ->selectRaw('direction, sum(amount) total')->groupBy('direction')->pluck('total', 'direction');
        $lastBalance = CashJournalEntry::query()->where('company_id', $cid)->latest('entry_date')->latest('id')->value('balance_after');

        $expensesMonth = (float) Expense::query()->where('company_id', $cid)->whereBetween('expense_date', [$from, $to])->sum('amount');
        $expensesCount = Expense::query()->where('company_id', $cid)->whereBetween('expense_date', [$from, $to])->count();

        $receivables = PosPayment::query()->where('is_deferred', true)
            ->whereIn('collection_status', SalePaymentWorkflowService::OPEN_COLLECTION)
            ->whereHas('sale', fn ($q) => $q->where('company_id', $cid));
        $receivablesTotal = (float) (clone $receivables)->sum('amount');
        $receivablesCount = (clone $receivables)->count();
        $overdueCount = (clone $receivables)->whereDate('due_date', '<', today())->count();

        $invoicesOpen = Invoice::query()->where('company_id', $cid)->open();
        $invoicesDue = (float) (clone $invoicesOpen)->sum('balance_due');
        $invoicesCount = (clone $invoicesOpen)->count();

        $pendingCredits = SupplierReturn::query()->forCompany($cid)->where('status', 'validated');
        $pendingCreditsTotal = (float) (clone $pendingCredits)->sum('total_ttc');
        $pendingCreditsCount = (clone $pendingCredits)->count();

        $recent = CashJournalEntry::query()->where('company_id', $cid)->latest('entry_date')->latest('id')->limit(8)->get();

        return view('accounting.home', [
            'cashIn' => (float) ($cash['in'] ?? 0),
            'cashOut' => (float) ($cash['out'] ?? 0),
            'lastBalance' => $lastBalance,
            'expensesMonth' => $expensesMonth,
            'expensesCount' => $expensesCount,
            'receivablesTotal' => $receivablesTotal,
            'receivablesCount' => $receivablesCount,
            'overdueCount' => $overdueCount,
            'invoicesDue' => $invoicesDue,
            'invoicesCount' => $invoicesCount,
            'pendingCreditsTotal' => $pendingCreditsTotal,
            'pendingCreditsCount' => $pendingCreditsCount,
            'recent' => $recent,
            'currency' => $company->currency ?? 'MAD',
            'monthLabel' => now()->translatedFormat('F Y'),
        ]);
    }
}

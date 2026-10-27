<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\AccountAccount;
use App\Models\AccountEntry;
use App\Services\AccountingLedgerService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    public function __construct(private AccountingLedgerService $ledger)
    {
    }

    public function accounts(): View
    {
        $this->authorize('accounting.view');
        $company = Workspace::company();
        $this->ledger->seedDefaultAccounts($company->id);
        $accounts = AccountAccount::query()->forCompany($company->id)->orderBy('code')->get();

        return view('accounting.accounts.index', [
            'accounts' => $accounts,
            'types' => AccountAccount::TYPES,
        ]);
    }

    public function storeAccount(Request $request): RedirectResponse
    {
        $this->authorize('accounting.create');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:200'],
            'type' => ['required', 'in:asset,liability,equity,income,expense'],
        ]);
        AccountAccount::query()->create([
            ...$data,
            'company_id' => Workspace::company()->id,
            'is_active' => true,
        ]);

        return back()->with('success', 'Compte créé.');
    }

    public function entries(): View
    {
        $this->authorize('accounting.view');
        $entries = AccountEntry::query()
            ->forCompany(Workspace::company()->id)
            ->with('lines.account')
            ->latest('entry_date')
            ->paginate(20);

        return view('accounting.ledger.index', [
            'entries' => $entries,
            'accounts' => AccountAccount::query()->forCompany(Workspace::company()->id)->orderBy('code')->get(),
        ]);
    }

    public function storeEntry(Request $request): RedirectResponse
    {
        $this->authorize('accounting.create');
        $data = $request->validate([
            'label' => ['required', 'string', 'max:200'],
            'entry_date' => ['required', 'date'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', 'exists:account_accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->ledger->createEntry($data, $data['lines']);

        return redirect()->route('accounting.ledger.index')->with('success', 'Écriture enregistrée.');
    }

    public function bilan(Request $request): View
    {
        $this->authorize('accounting.view');
        $summary = $this->ledger->bilanSummary(Workspace::company()->id, $request->input('as_of'));

        return view('accounting.bilan', compact('summary'));
    }

    public function vat(Request $request): View|StreamedResponse
    {
        $this->authorize('accounting.view');
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $summary = $this->ledger->vatSummary(Workspace::company()->id, $from, $to);

        if ($request->boolean('export')) {
            return response()->streamDownload(function () use ($summary) {
                echo "from,to,sales_vat,invoice_vat,collected\n";
                echo "{$summary['from']},{$summary['to']},{$summary['sales_vat']},{$summary['invoice_vat']},{$summary['collected']}\n";
            }, 'tva-export.csv', ['Content-Type' => 'text/csv']);
        }

        return view('accounting.vat', compact('summary', 'from', 'to'));
    }
}

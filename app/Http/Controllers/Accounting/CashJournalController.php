<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CashJournalEntry;
use App\Models\Store;
use App\Services\CashJournalService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashJournalController extends Controller
{
    public function __construct(private CashJournalService $journal)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('accounting.view');
        $company = Workspace::company();

        $entries = CashJournalEntry::query()
            ->forCompany($company->id)
            ->with(['store', 'creator'])
            ->when($request->filled('direction'), fn ($q) => $q->where('direction', $request->string('direction')))
            ->when($request->filled('source_type'), fn ($q) => $q->where('source_type', $request->string('source_type')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('entry_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('entry_date', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('label', 'like', $term)->orWhere('notes', 'like', $term);
                });
            })
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $in = (float) CashJournalEntry::query()->forCompany($company->id)->where('direction', 'in')->sum('amount');
        $out = (float) CashJournalEntry::query()->forCompany($company->id)->where('direction', 'out')->sum('amount');

        // Rimal : la trésorerie intègre aussi les flux du point de vente (encaissements réels + mouvements de caisse).
        $from = $request->filled('from') ? \Illuminate\Support\Carbon::parse($request->string('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? \Illuminate\Support\Carbon::parse($request->string('to'))->endOfDay() : now()->endOfDay();
        $storeId = $request->filled('store_id') ? $request->integer('store_id') : null;
        $rimal = app(\App\Services\RimalReportService::class);
        $posInflows = $rimal->inflowsByMethod($company->id, $from, $to, $storeId);
        $mv = \App\Models\PosCashMovement::query()->where('company_id', $company->id)->whereBetween('created_at', [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId));
        $posCashIn = (float) (clone $mv)->where('direction', 'in')->sum('amount');
        $posCashOut = (float) (clone $mv)->where('direction', 'out')->sum('amount');
        $posTotal = round(array_sum($posInflows) + $posCashIn - $posCashOut, 2);

        return view('accounting.cash.index', [
            'pos' => ['from' => $from, 'to' => $to, 'inflows' => $posInflows, 'cash_in' => $posCashIn, 'cash_out' => $posCashOut, 'total' => $posTotal, 'receivable' => $rimal->openReceivables($company->id, $storeId)],
            'entries' => $entries,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'directions' => CashJournalEntry::DIRECTIONS,
            'sourceTypes' => CashJournalEntry::SOURCE_TYPES,
            'filters' => $request->only(['q', 'direction', 'source_type', 'store_id', 'from', 'to']),
            'stats' => [
                'in' => $in,
                'out' => $out,
                'balance' => round($in - $out, 2),
            ],
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function create(): View
    {
        $this->authorize('accounting.create');
        $company = Workspace::company();

        return view('accounting.cash.create', [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'directions' => CashJournalEntry::DIRECTIONS,
            'sourceTypes' => CashJournalEntry::SOURCE_TYPES,
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('accounting.create');

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'direction' => ['required', 'in:in,out'],
            'source_type' => ['required', 'in:sale,expense,collection,manual,payroll,other'],
            'label' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'payment_method' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $this->journal->record($data);

        return redirect()->route('accounting.cash.index')->with('success', 'Écriture de caisse enregistrée.');
    }

    public function rebuild(Request $request): RedirectResponse
    {
        $this->authorize('accounting.validate');
        $storeId = $request->filled('store_id') ? $request->integer('store_id') : null;
        $count = $this->journal->rebuildBalances($storeId);

        return back()->with('success', "Soldes recalculés ({$count} écritures).");
    }
}

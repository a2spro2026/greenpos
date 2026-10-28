<?php

namespace App\Http\Controllers;

use App\Models\PosPayment;
use App\Models\PosSession;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\User;
use App\Services\PosCashService;
use App\Services\SalePaymentWorkflowService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rimal V2 — journal de caisse, mouvements d'espèces et règlements à encaisser.
 */
class PosCashController extends Controller
{
    public function __construct(private PosCashService $cash)
    {
    }

    protected function sessionsQuery(Request $request)
    {
        $company = Workspace::company();

        return PosSession::query()
            ->forCompany($company->id)
            ->with(['store', 'opener', 'closer'])
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('user_id'), fn ($q) => $q->where(fn ($w) => $w->where('opened_by', $request->integer('user_id'))->orWhere('closed_by', $request->integer('user_id'))))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('opened_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('opened_at', '<=', $request->date('to')))
            ->orderByDesc('opened_at');
    }

    protected function journalData(Request $request): array
    {
        $sessions = $this->sessionsQuery($request)->limit(200)->get();
        $rows = $sessions->map(function (PosSession $s) {
            $ops = $this->cash->sessionOperations($s);

            return [
                'session' => $s,
                'operations' => $ops,
                'opening' => (float) $s->opening_float,
                'final' => $s->isOpen() ? $this->cash->expectedCash($s) : (float) $s->expected_cash,
                'counted' => $s->closing_counted !== null ? (float) $s->closing_counted : null,
                'difference' => $s->cash_difference !== null ? (float) $s->cash_difference : null,
            ];
        });

        return [$sessions, $rows];
    }

    public function journal(Request $request): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();
        [, $rows] = $this->journalData($request);

        return view('pos.journal.index', [
            'rows' => $rows,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))->orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['store_id', 'user_id', 'from', 'to']),
            'openSession' => app(\App\Services\PosService::class)->currentOpenSession(),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function print(Request $request): View
    {
        $this->authorize('pos.view');
        [, $rows] = $this->journalData($request);

        return view('pos.journal.print', [
            'rows' => $rows,
            'company' => Workspace::company(),
            'filters' => $request->only(['store_id', 'user_id', 'from', 'to']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('pos.view');
        [, $rows] = $this->journalData($request);

        return Response::streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($out, ['Session', 'Boutique', 'Date', 'Opération', 'Utilisateur', 'Entrée', 'Sortie', 'Solde'], ';');
            foreach ($rows as $row) {
                foreach ($row['operations'] as $op) {
                    fputcsv($out, [
                        $row['session']->number, $row['session']->store?->name,
                        optional($op['at'])->format('Y-m-d H:i'), $op['label'], $op['user'],
                        number_format($op['in'], 2, ',', ''), number_format($op['out'], 2, ',', ''), number_format($op['balance'], 2, ',', ''),
                    ], ';');
                }
                fputcsv($out, [$row['session']->number, '', '', 'Solde final théorique', '', '', '', number_format($row['final'], 2, ',', '')], ';');
                if ($row['counted'] !== null) {
                    fputcsv($out, [$row['session']->number, '', '', 'Compté / écart', '', number_format($row['counted'], 2, ',', ''), '', number_format((float) $row['difference'], 2, ',', '')], ';');
                }
            }
            fclose($out);
        }, 'journal-caisse-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $this->authorize('pos.sell');
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:200'],
        ]);
        $session = app(\App\Services\PosService::class)->currentOpenSession();
        if (! $session) {
            return back()->withErrors(['session' => 'Aucune caisse ouverte.']);
        }
        $this->cash->addMovement($session, $data['direction'], (float) $data['amount'], $data['reason']);

        return back()->with('success', 'Mouvement de caisse enregistré.');
    }

    public function collections(Request $request): View
    {
        $this->authorize('payments.collect');
        $company = Workspace::company();
        $open = SalePaymentWorkflowService::OPEN_COLLECTION;
        $method = $request->string('method')->toString();

        $pos = PosPayment::query()
            ->where('is_deferred', true)->whereIn('collection_status', $open)
            ->whereHas('sale', fn ($q) => $q->where('company_id', $company->id))
            ->with(['sale.customer', 'sale.store'])
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('to')))
            ->get()
            ->map(fn ($p) => ['kind' => 'pos', 'payment' => $p, 'number' => $p->sale?->number, 'customer' => $p->sale?->customer?->name, 'store' => $p->sale?->store?->name]);

        $sales = SalePayment::query()
            ->where('is_deferred', true)->whereIn('collection_status', $open)
            ->whereHas('sale', fn ($q) => $q->where('company_id', $company->id))
            ->with(['sale.customer', 'sale.store'])
            ->when($method, fn ($q) => $q->where('method', $method))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('due_date', '<=', $request->date('to')))
            ->get()
            ->map(fn ($p) => ['kind' => 'sale', 'payment' => $p, 'number' => $p->sale?->number, 'customer' => $p->sale?->customer?->name, 'store' => $p->sale?->store?->name]);

        $items = $pos->concat($sales)->sortBy(fn ($i) => optional($i['payment']->due_date)->timestamp ?? PHP_INT_MAX)->values();

        return view('pos.collections.index', [
            'items' => $items,
            'methods' => SalePayment::METHODS + PosPayment::METHODS,
            'filters' => $request->only(['method', 'to']),
            'total' => round($items->sum(fn ($i) => (float) $i['payment']->amount), 2),
            'overdue' => round($items->filter(fn ($i) => $i['payment']->due_date && $i['payment']->due_date->isPast())->sum(fn ($i) => (float) $i['payment']->amount), 2),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }
}

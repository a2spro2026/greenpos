<?php

namespace App\Http\Controllers;

use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\Printer;
use App\Models\SalePayment;
use App\Services\CollectionService;
use App\Services\PosService;
use App\Services\SaleRefundService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosTicketController extends Controller
{
    public function __construct(private PosService $pos)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('pos.history');
        $company = Workspace::company();

        $tickets = PosSale::query()
            ->forCompany($company->id)
            ->with(['cashier', 'customer', 'store', 'payments'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('completed_at', '>=', $request->date('from')))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('pos.tickets.index', [
            'tickets' => $tickets,
            'statuses' => PosSale::STATUSES,
            'filters' => $request->only(['q', 'status', 'from']),
        ]);
    }

    public function show(PosSale $sale): View
    {
        $this->authorize('pos.history');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $sale->load(['lines', 'payments', 'customer', 'cashier', 'store', 'session']);

        return view('pos.tickets.show', compact('sale'));
    }

    public function print(PosSale $sale): View
    {
        $this->authorize('pos.reprint');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $sale->load(['lines.product.category', 'payments', 'customer', 'cashier', 'store', 'company']);

        return view('pos.tickets.print', [
            'sale' => $sale,
            'printer' => $this->printerFor($sale, 'customer'),
            'channel' => 'customer',
        ]);
    }

    public function kitchen(PosSale $sale): View
    {
        $this->authorize('pos.reprint');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $sale->load(['lines.product.category', 'payments', 'customer', 'cashier', 'store', 'company']);

        return view('pos.tickets.print', [
            'sale' => $sale,
            'printer' => $this->printerFor($sale, 'kitchen'),
            'channel' => 'kitchen',
        ]);
    }

    public function refundForm(PosSale $sale): View
    {
        $this->authorize('sales.refund');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $sale->load('lines');

        return view('pos.tickets.refund', ['sale' => $sale, 'methods' => SalePayment::METHODS]);
    }

    public function refund(Request $request, PosSale $sale, SaleRefundService $refunds): RedirectResponse
    {
        $this->authorize('sales.refund');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:32'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['nullable', 'array'],
            'lines.*.line_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);
        $data['restock'] = $request->boolean('restock');
        $refunds->refundPos($sale, $data);

        return redirect()->route('pos.tickets.show', $sale)->with('success', 'Remboursement enregistré.');
    }

    public function collect(PosPayment $payment, CollectionService $collections): RedirectResponse
    {
        $this->authorize('payments.collect');
        $this->ensurePayment($payment);
        $collections->collect($payment);

        return back()->with('success', 'Encaissement enregistré.');
    }

    public function reschedule(Request $request, PosPayment $payment, CollectionService $collections): RedirectResponse
    {
        $this->authorize('payments.collect');
        $this->ensurePayment($payment);
        $data = $request->validate(['due_date' => ['required', 'date']]);
        $collections->reschedule($payment, $data['due_date']);

        return back()->with('success', 'Échéance mise à jour.');
    }

    public function cancelPayment(PosPayment $payment, CollectionService $collections): RedirectResponse
    {
        $this->authorize('payments.collect');
        $this->ensurePayment($payment);
        $collections->cancel($payment);

        return back()->with('success', 'Paiement différé annulé.');
    }

    private function printerFor(PosSale $sale, string $channel): ?Printer
    {
        return Printer::query()
            ->forCompany($sale->company_id)
            ->where('is_active', true)
            ->where(function ($q) use ($sale) {
                $q->whereNull('store_id')->orWhere('store_id', $sale->store_id);
            })
            ->whereIn('role', $channel === 'kitchen' ? ['kitchen', 'both'] : ['customer', 'both'])
            ->orderByDesc('is_default')
            ->first();
    }

    private function ensurePayment(PosPayment $payment): void
    {
        $payment->loadMissing('sale');
        if ($payment->sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }

    public function cancel(PosSale $sale): RedirectResponse
    {
        $this->authorize('pos.cancel');
        if ($sale->company_id !== Workspace::company()?->id) {
            abort(404);
        }

        if ($sale->status === 'held') {
            $this->pos->cancelSale($sale);
        } else {
            $this->pos->voidCompletedSale($sale);
        }

        return back()->with('success', 'Ticket annulé.');
    }
}

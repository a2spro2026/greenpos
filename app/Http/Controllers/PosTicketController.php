<?php

namespace App\Http\Controllers;

use App\Models\PosSale;
use App\Services\PosService;
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
        $sale->load(['lines', 'payments', 'customer', 'cashier', 'store']);

        return view('pos.tickets.print', compact('sale'));
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

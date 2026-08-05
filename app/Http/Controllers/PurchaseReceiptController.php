<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseReceipt;
use App\Services\PurchaseService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseReceiptController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index(): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();

        $receipts = PurchaseReceipt::query()
            ->forCompany($company->id)
            ->with(['order.supplier', 'store', 'receiver'])
            ->latest()
            ->paginate(15);

        return view('purchases.receipts.index', compact('receipts'));
    }

    public function create(PurchaseOrder $order): View
    {
        $this->authorize('purchases.receive');
        $this->ensureOrder($order);
        if (! $order->canReceive()) {
            abort(403, 'Commande non réceptionnable.');
        }
        $order->load('lines.product');

        return view('purchases.receipts.create', compact('order'));
    }

    public function store(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $this->ensureOrder($order);

        $data = $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
            'received_at' => ['nullable', 'date'],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
            'validate_now' => ['nullable', 'boolean'],
        ]);

        $receipt = $this->purchases->createReceipt(
            $order,
            $data['quantities'],
            $data['notes'] ?? null,
            $data['received_at'] ?? null
        );

        if ($request->boolean('validate_now')) {
            $this->purchases->validateReceipt($receipt);

            return redirect()
                ->route('purchases.orders.show', $order)
                ->with('success', 'Réception validée — stock mis à jour.');
        }

        return redirect()
            ->route('purchases.receipts.show', $receipt)
            ->with('success', 'Réception créée. Validez pour mettre à jour le stock.');
    }

    public function show(PurchaseReceipt $receipt): View
    {
        $this->authorize('purchases.view');
        $this->ensureReceipt($receipt);
        $receipt->load(['lines.product', 'order.supplier', 'store', 'receiver']);

        return view('purchases.receipts.show', compact('receipt'));
    }

    public function validateReceipt(PurchaseReceipt $receipt): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $this->ensureReceipt($receipt);
        $this->purchases->validateReceipt($receipt);

        return redirect()
            ->route('purchases.orders.show', $receipt->purchase_order_id)
            ->with('success', 'Réception validée — mouvements de stock créés.');
    }

    protected function ensureOrder(PurchaseOrder $order): void
    {
        if ($order->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }

    protected function ensureReceipt(PurchaseReceipt $receipt): void
    {
        if ($receipt->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

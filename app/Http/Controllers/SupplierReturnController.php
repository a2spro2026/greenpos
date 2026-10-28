<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReceipt;
use App\Models\Supplier;
use App\Models\SupplierReturn;
use App\Models\SupplierReturnLine;
use App\Services\StockService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// Rimal V2 — retours fournisseur : sortie de stock + suivi de l'avoir fournisseur.
class SupplierReturnController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();
        $status = $request->query('status');
        $supplierId = $request->query('supplier_id');

        $base = SupplierReturn::query()->forCompany($company->id);
        $returns = (clone $base)->with(['supplier', 'receipt', 'store'])
            ->when($status && isset(SupplierReturn::STATUSES[$status]), fn ($q) => $q->where('status', $status))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId))
            ->latest()->paginate(15)->withQueryString();

        $counts = (clone $base)->selectRaw('status, count(*) c, sum(total_ttc) t')->groupBy('status')->get()->keyBy('status');
        $suppliers = Supplier::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']);

        return view('purchases.returns.index', compact('returns', 'counts', 'suppliers', 'status', 'supplierId'));
    }

    public function create(Request $request): View
    {
        $this->authorize('purchases.receive');
        $company = Workspace::company();

        $receipts = PurchaseReceipt::query()->forCompany($company->id)->where('status', 'validated')
            ->with('order.supplier')->latest()->limit(200)->get();

        $receipt = null;
        $lines = collect();
        if ($request->filled('receipt')) {
            $receipt = $this->findReceipt((int) $request->query('receipt'));
            $lines = $this->returnableLines($receipt);
        }

        return view('purchases.returns.create', [
            'receipts' => $receipts, 'receipt' => $receipt, 'lines' => $lines, 'reasons' => SupplierReturn::REASONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $company = Workspace::company();
        $data = $request->validate([
            'receipt_id' => ['required', 'integer'],
            'reason' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'returned_at' => ['nullable', 'date'],
            'quantities' => ['required', 'array'],
            'quantities.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $receipt = $this->findReceipt((int) $data['receipt_id']);
        $available = $this->returnableLines($receipt)->keyBy('id');

        $rows = [];
        foreach ($data['quantities'] as $lineId => $qty) {
            $qty = (float) $qty;
            if ($qty <= 0) {
                continue;
            }
            $line = $available->get((int) $lineId);
            if (! $line) {
                continue;
            }
            if ($qty > $line->returnable + 0.0001) {
                throw ValidationException::withMessages(['quantities.'.$lineId => 'Quantité supérieure au retournable ('.rtrim(rtrim(number_format($line->returnable, 3, '.', ''), '0'), '.').') pour '.$line->product?->name.'.']);
            }
            $rows[] = [$line, $qty];
        }
        if (! $rows) {
            throw ValidationException::withMessages(['quantities' => 'Indiquez au moins une quantité à retourner.']);
        }

        $return = DB::transaction(function () use ($company, $receipt, $data, $rows) {
            $seq = SupplierReturn::query()->forCompany($company->id)->count() + 1;
            $return = SupplierReturn::query()->create([
                'company_id' => $company->id,
                'store_id' => $receipt->store_id,
                'supplier_id' => $receipt->order?->supplier_id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'purchase_receipt_id' => $receipt->id,
                'number' => 'RF-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'status' => 'draft',
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'returned_at' => $data['returned_at'] ?? now()->toDateString(),
                'created_by' => Workspace::user()?->id,
            ]);
            $ht = $tax = 0;
            foreach ($rows as [$line, $qty]) {
                $price = round((float) ($line->orderLine?->unit_price ?? 0) * (1 - (float) ($line->orderLine?->discount_percent ?? 0) / 100), 2);
                $rate = (float) ($line->orderLine?->tax_rate ?? 0);
                $total = round($price * $qty, 2);
                $ht += $total;
                $tax += round($total * $rate / 100, 2);
                SupplierReturnLine::query()->create([
                    'supplier_return_id' => $return->id, 'purchase_receipt_line_id' => $line->id, 'product_id' => $line->product_id,
                    'quantity' => $qty, 'unit_price' => $price, 'tax_rate' => $rate, 'line_total' => $total,
                ]);
            }
            $return->update(['total_ht' => $ht, 'total_tax' => $tax, 'total_ttc' => $ht + $tax]);

            return $return;
        });

        return redirect()->route('purchases.returns.show', $return)->with('success', 'Retour créé (brouillon). Validez-le pour sortir le stock.');
    }

    public function show(SupplierReturn $return): View
    {
        $this->authorize('purchases.view');
        $this->ensure($return);
        $return->load(['lines.product', 'supplier', 'receipt', 'order', 'store', 'creator']);

        return view('purchases.returns.show', compact('return'));
    }

    public function print(SupplierReturn $return): View
    {
        $this->authorize('purchases.view');
        $this->ensure($return);
        $return->load(['lines.product', 'supplier', 'receipt', 'order', 'store']);

        return view('purchases.returns.print', compact('return'));
    }

    public function validateReturn(SupplierReturn $return): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $this->ensure($return);
        if ($return->status !== 'draft') {
            return back()->withErrors(['status' => 'Ce retour a déjà été traité.']);
        }

        DB::transaction(function () use ($return) {
            $return->load('lines.product');
            foreach ($return->lines as $line) {
                if ($line->product?->track_stock && (float) $line->quantity > 0) {
                    $this->stock->applyMovement([
                        'store_id' => $return->store_id,
                        'product_id' => $line->product_id,
                        'type' => 'out',
                        'quantity' => (float) $line->quantity,
                        'unit_cost' => $line->unit_price,
                        'reference' => $return->number,
                        'comment' => 'Retour fournisseur '.$return->number.($return->reason ? ' — '.$return->reason : ''),
                        'moved_at' => now(),
                    ]);
                }
            }
            $return->update(['status' => 'validated', 'validated_at' => now()]);
        });

        return back()->with('success', 'Retour validé — stock sorti. Enregistrez l\'avoir dès réception.');
    }

    public function credit(Request $request, SupplierReturn $return): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $this->ensure($return);
        if ($return->status !== 'validated') {
            return back()->withErrors(['status' => 'Validez le retour avant d\'enregistrer l\'avoir.']);
        }
        $data = $request->validate([
            'credit_note_number' => ['required', 'string', 'max:60'],
            'credit_note_amount' => ['required', 'numeric', 'min:0'],
            'credited_at' => ['nullable', 'date'],
        ]);
        $return->update($data + ['status' => 'credited', 'credited_at' => $data['credited_at'] ?? now()->toDateString()]);

        return back()->with('success', 'Avoir fournisseur enregistré.');
    }

    public function cancel(SupplierReturn $return): RedirectResponse
    {
        $this->authorize('purchases.receive');
        $this->ensure($return);
        if ($return->status !== 'draft') {
            return back()->withErrors(['status' => 'Seul un brouillon peut être annulé.']);
        }
        $return->update(['status' => 'cancelled']);

        return back()->with('success', 'Retour annulé (conservé dans l\'historique).');
    }

    protected function findReceipt(int $id): PurchaseReceipt
    {
        return PurchaseReceipt::query()->forCompany(Workspace::company()->id)->where('status', 'validated')
            ->with(['lines.product', 'lines.orderLine', 'order.supplier', 'store'])->findOrFail($id);
    }

    protected function returnableLines(PurchaseReceipt $receipt)
    {
        $already = SupplierReturnLine::query()
            ->whereIn('purchase_receipt_line_id', $receipt->lines->pluck('id'))
            ->whereHas('supplierReturn', fn ($q) => $q->where('status', '!=', 'cancelled'))
            ->selectRaw('purchase_receipt_line_id, sum(quantity) q')->groupBy('purchase_receipt_line_id')->pluck('q', 'purchase_receipt_line_id');

        return $receipt->lines->filter(fn ($l) => (float) $l->quantity > 0)->map(function ($l) use ($already) {
            $l->already_returned = (float) ($already[$l->id] ?? 0);
            $l->returnable = max(0, (float) $l->quantity - $l->already_returned);

            return $l;
        })->values();
    }

    protected function ensure(SupplierReturn $return): void
    {
        if ($return->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\StockInventory;
use App\Models\StockInventoryLine;
use App\Models\Store;
use App\Services\StockService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockInventoryController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(): View
    {
        $this->authorize('stock.inventory');
        $company = Workspace::company();

        $inventories = StockInventory::query()
            ->forCompany($company->id)
            ->with(['store', 'creator', 'validator'])
            ->withCount(['lines', 'lines as counted_lines_count' => fn ($q) => $q->where('is_counted', true)])
            ->latest()
            ->paginate(12);

        return view('stock.inventories.index', compact('inventories'));
    }

    public function create(): View
    {
        $this->authorize('stock.inventory');
        $company = Workspace::company();

        return view('stock.inventories.create', [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stock.inventory');

        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'name' => ['required', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        Store::query()->whereKey($data['store_id'])->where('company_id', Workspace::company()->id)->firstOrFail();

        $inventory = $this->stock->createInventory($data);

        return redirect()
            ->route('stock.inventories.show', $inventory)
            ->with('success', 'Inventaire démarré. Comptez les articles.');
    }

    public function show(StockInventory $inventory): View
    {
        $this->authorize('stock.inventory');
        $this->ensureCompany($inventory);

        $inventory->load(['store', 'creator', 'validator', 'lines.product']);

        return view('stock.inventories.show', compact('inventory'));
    }

    public function count(Request $request, StockInventory $inventory): RedirectResponse
    {
        $this->authorize('stock.inventory');
        $this->ensureCompany($inventory);

        $data = $request->validate([
            'line_id' => ['required', 'exists:stock_inventory_lines,id'],
            'counted_qty' => ['required', 'numeric', 'min:0'],
            'barcode' => ['nullable', 'string', 'max:64'],
        ]);

        $line = StockInventoryLine::query()
            ->whereKey($data['line_id'])
            ->where('inventory_id', $inventory->id)
            ->firstOrFail();

        if ($request->filled('barcode')) {
            $barcode = $request->string('barcode')->toString();
            $product = $line->product;
            if ($product && $product->barcode && $product->barcode !== $barcode && $product->sku !== $barcode) {
                return back()->withErrors(['barcode' => 'Code-barres ne correspond pas à la ligne.']);
            }
        }

        $this->stock->countLine($line, (float) $data['counted_qty']);

        return back()->with('success', 'Comptage enregistré.');
    }

    public function scan(Request $request, StockInventory $inventory): RedirectResponse
    {
        $this->authorize('stock.inventory');
        $this->ensureCompany($inventory);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'counted_qty' => ['nullable', 'numeric', 'min:0'],
        ]);

        $code = trim($data['code']);
        $line = $inventory->lines()
            ->whereHas('product', function ($q) use ($code) {
                $q->where('barcode', $code)->orWhere('sku', $code);
            })
            ->first();

        if (! $line) {
            return back()->withErrors(['code' => 'Aucun produit trouvé pour ce code.']);
        }

        $qty = array_key_exists('counted_qty', $data) && $data['counted_qty'] !== null
            ? (float) $data['counted_qty']
            : ((float) ($line->counted_qty ?? $line->expected_qty) + 1);

        $this->stock->countLine($line, $qty);

        return back()->with('success', 'Scan : '.$line->product?->name.' → '.$qty);
    }

    public function validateInventory(StockInventory $inventory): RedirectResponse
    {
        $this->authorize('stock.inventory');
        $this->ensureCompany($inventory);

        $this->stock->validateInventory($inventory);

        return redirect()
            ->route('stock.inventories.show', $inventory)
            ->with('success', 'Inventaire validé. Les écarts ont été ajustés.');
    }

    public function cancel(StockInventory $inventory): RedirectResponse
    {
        $this->authorize('stock.inventory');
        $this->ensureCompany($inventory);

        $this->stock->cancelInventory($inventory);

        return redirect()
            ->route('stock.inventories.index')
            ->with('success', 'Inventaire annulé.');
    }

    protected function ensureCompany(StockInventory $inventory): void
    {
        if ($inventory->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

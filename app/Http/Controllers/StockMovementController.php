<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\StockService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockMovementController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();

        $movements = StockMovement::query()
            ->forCompany($company->id)
            ->with(['product', 'store', 'user', 'relatedStore'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('reference', 'like', $term)
                        ->orWhere('comment', 'like', $term)
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term)->orWhere('sku', 'like', $term));
                });
            })
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('moved_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('moved_at', '<=', $request->date('to')))
            ->latest('moved_at')
            ->paginate(20)
            ->withQueryString();

        return view('stock.movements.index', [
            'movements' => $movements,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'types' => StockMovement::TYPES,
            'filters' => $request->only(['q', 'type', 'store_id', 'from', 'to']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('stock.move');
        $company = Workspace::company();

        return view('stock.movements.create', [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()
                ->forCompany($company->id)
                ->where('track_stock', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku']),
            'types' => collect(StockMovement::TYPES)->except('transfer'),
            'prefill' => [
                'product_id' => $request->integer('product_id') ?: null,
                'store_id' => $request->integer('store_id') ?: Workspace::store()?->id,
                'type' => $request->string('type')->toString() ?: 'in',
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stock.move');

        $data = $request->validate([
            'type' => ['required', 'in:in,out,adjustment'],
            'store_id' => ['required', 'exists:stores,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric'],
            'reference' => ['nullable', 'string', 'max:120'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'moved_at' => ['nullable', 'date'],
        ]);

        if ($data['type'] === 'adjustment') {
            $this->authorize('stock.adjust');
        }

        $store = Store::query()->whereKey($data['store_id'])->where('company_id', Workspace::company()->id)->firstOrFail();
        $data['store_id'] = $store->id;

        $movement = $this->stock->applyMovement($data);

        return redirect()
            ->route('stock.movements.index')
            ->with('success', 'Mouvement enregistré (#'.$movement->id.').');
    }

    public function show(StockMovement $movement): View
    {
        $this->authorize('stock.view');
        if ($movement->company_id !== Workspace::company()?->id) {
            abort(404);
        }

        $movement->load(['product', 'store', 'user', 'relatedStore', 'inventory']);

        return view('stock.movements.show', compact('movement'));
    }
}

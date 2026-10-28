<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBomComponent;
use App\Models\Production;
use App\Models\Store;
use App\Services\ProductionService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionController extends Controller
{
    public function __construct(private ProductionService $productions)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();

        $items = Production::query()
            ->forCompany($company->id)
            ->with(['product', 'store', 'creator'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $term));
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('production.index', [
            'productions' => $items,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'statuses' => Production::STATUSES,
            'filters' => $request->only(['q', 'status', 'store_id']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('stock.move');
        $company = Workspace::company();

        return view('production.create', [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
            'suggestedNumber' => $this->productions->nextNumber($company->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stock.move');
        $data = $this->validated($request);
        $production = $this->productions->saveDraft($data);

        return redirect()->route('ops.production.show', $production)->with('success', 'Brouillon de production créé.');
    }

    public function show(Production $production): View
    {
        $this->authorize('stock.view');
        $this->ensureCompany($production);
        $production->load(['product', 'store', 'creator', 'validator', 'consumptions.product']);

        $bom = ProductBomComponent::query()
            ->forCompany($production->company_id)
            ->where('product_id', $production->product_id)
            ->with('component')
            ->orderBy('sort_order')
            ->get();

        return view('production.show', compact('production', 'bom'));
    }

    public function edit(Production $production): View
    {
        $this->authorize('stock.move');
        $this->ensureCompany($production);
        if (! $production->isEditable()) {
            abort(403, 'Production validée.');
        }
        $company = Workspace::company();

        return view('production.edit', [
            'production' => $production,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function update(Request $request, Production $production): RedirectResponse
    {
        $this->authorize('stock.move');
        $this->ensureCompany($production);
        $this->productions->saveDraft($this->validated($request), $production);

        return redirect()->route('ops.production.show', $production)->with('success', 'Production mise à jour.');
    }

    public function validateProduction(Production $production): RedirectResponse
    {
        $this->authorize('stock.move');
        $this->ensureCompany($production);
        $this->productions->validate($production);

        return redirect()->route('ops.production.show', $production)->with('success', 'Production validée — stocks mis à jour.');
    }

    public function bomForm(Request $request): View
    {
        $this->authorize('stock.move');
        $company = Workspace::company();
        $productId = $request->integer('product_id') ?: null;
        $bom = collect();
        if ($productId) {
            $bom = ProductBomComponent::query()
                ->forCompany($company->id)
                ->where('product_id', $productId)
                ->with('component')
                ->orderBy('sort_order')
                ->get();
        }

        return view('production.bom', [
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
            'components' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'purchase_price']),
            'productId' => $productId,
            'bom' => $bom,
        ]);
    }

    public function bomStore(Request $request): RedirectResponse
    {
        $this->authorize('stock.move');
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'components' => ['nullable', 'array'],
            'components.*.component_product_id' => ['nullable', 'exists:products,id'],
            'components.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'components.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->productions->syncBom((int) $data['product_id'], $data['components'] ?? []);

        return redirect()
            ->route('ops.production.bom', ['product_id' => $data['product_id']])
            ->with('success', 'Nomenclature enregistrée.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'produced_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function ensureCompany(Production $production): void
    {
        if ($production->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

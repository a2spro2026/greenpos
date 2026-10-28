<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockLoss;
use App\Models\Store;
use App\Services\StockLossService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StockLossController extends Controller
{
    public function __construct(private StockLossService $losses)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();

        $items = StockLoss::query()
            ->forCompany($company->id)
            ->with(['store', 'creator'])
            ->when($request->filled('loss_type'), fn ($q) => $q->where('loss_type', $request->string('loss_type')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('loss_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('loss_date', '<=', $request->date('to')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('responsible_name', 'like', $term)
                        ->orWhere('notes', 'like', $term);
                });
            })
            ->latest('loss_date')
            ->paginate(20)
            ->withQueryString();

        return view('losses.index', [
            'losses' => $items,
            'types' => StockLoss::TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'loss_type', 'store_id', 'from', 'to']),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function create(): View
    {
        $this->authorize('stock.adjust');
        $company = Workspace::company();

        return view('losses.create', [
            'types' => StockLoss::TYPES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()
                ->forCompany($company->id)
                ->where('track_stock', true)
                ->where('status', 'active')
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'purchase_price']),
            'suggestedNumber' => $this->losses->nextNumber($company->id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('stock.adjust');

        $data = $request->validate([
            'store_id' => ['required', 'exists:stores,id'],
            'loss_type' => ['required', 'in:loss,breakage,expiration,theft'],
            'loss_date' => ['required', 'date'],
            'employee_id' => ['nullable', 'integer'],
            'responsible_name' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'exists:products,id'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:500'],
        ]);

        $data['lines'] = collect($data['lines'] ?? [])
            ->filter(fn ($row) => ! empty($row['product_id']) && (float) ($row['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        $loss = $this->losses->create($data);

        return redirect()->route('ops.losses.show', $loss)->with('success', 'Perte enregistrée — stock mis à jour.');
    }

    public function show(StockLoss $loss): View
    {
        $this->authorize('stock.view');
        if ($loss->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $loss->load(['lines.product', 'store', 'creator']);

        return view('losses.show', compact('loss'));
    }
}

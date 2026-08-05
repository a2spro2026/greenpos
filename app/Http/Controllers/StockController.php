<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Services\StockService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();
        $today = now()->startOfDay();

        $levels = StockLevel::query()
            ->forCompany($company->id)
            ->with('product:id,name,purchase_price,sale_price')
            ->select(['id', 'company_id', 'store_id', 'product_id', 'quantity', 'min_quantity', 'max_quantity', 'reserved_quantity'])
            ->limit(5000)
            ->get();

        $stats = [
            'value' => $levels->sum(fn (StockLevel $l) => $l->valuation()),
            'available' => $levels->filter(fn (StockLevel $l) => $l->stockStatus() === 'ok')->count(),
            'low' => $levels->filter(fn (StockLevel $l) => $l->stockStatus() === 'low')->count(),
            'out' => $levels->filter(fn (StockLevel $l) => $l->stockStatus() === 'out')->count(),
            'in_today' => StockMovement::query()->forCompany($company->id)->where('type', 'in')->where('moved_at', '>=', $today)->count(),
            'out_today' => StockMovement::query()->forCompany($company->id)->where('type', 'out')->where('moved_at', '>=', $today)->count(),
        ];

        $recent = StockMovement::query()
            ->forCompany($company->id)
            ->with(['product:id,name', 'store:id,name', 'user:id,name,first_name,last_name'])
            ->latest('moved_at')
            ->limit(8)
            ->get();

        $from = now()->subDays(6)->startOfDay();
        $weekMoves = StockMovement::query()
            ->forCompany($company->id)
            ->where('moved_at', '>=', $from)
            ->get(['type', 'quantity', 'moved_at']);

        $evolution = collect(range(6, 0))->map(function (int $daysAgo) use ($weekMoves) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $end = (clone $day)->endOfDay();
            $net = $weekMoves
                ->filter(fn (StockMovement $m) => $m->moved_at >= $day && $m->moved_at <= $end)
                ->sum(fn (StockMovement $m) => $m->signedQuantity());

            return [
                'label' => $day->format('D'),
                'net' => round($net, 2),
            ];
        });

        $movementsChart = collect(range(6, 0))->map(function (int $daysAgo) use ($weekMoves) {
            $day = now()->subDays($daysAgo)->startOfDay();
            $end = (clone $day)->endOfDay();
            $dayMoves = $weekMoves->filter(fn (StockMovement $m) => $m->moved_at >= $day && $m->moved_at <= $end);

            return [
                'label' => $day->format('D'),
                'in' => (float) $dayMoves->where('type', 'in')->sum('quantity'),
                'out' => (float) $dayMoves->where('type', 'out')->sum('quantity'),
            ];
        });

        return view('stock.dashboard', compact('stats', 'recent', 'evolution', 'movementsChart'));
    }

    public function levels(Request $request): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();

        $columns = $request->session()->get('stock_columns', ['product', 'sku', 'category', 'store', 'quantity', 'min', 'max', 'value', 'status']);
        if ($request->filled('columns')) {
            $columns = array_values(array_intersect(
                ['product', 'sku', 'category', 'store', 'quantity', 'min', 'max', 'value', 'status'],
                (array) $request->input('columns')
            ));
            $request->session()->put('stock_columns', $columns);
        }

        $query = StockLevel::query()
            ->forCompany($company->id)
            ->with(['product.category', 'store'])
            ->whereHas('product', function ($q) use ($request) {
                $q->where('track_stock', true);
                if ($request->filled('q')) {
                    $term = '%'.$request->string('q').'%';
                    $q->where(function ($inner) use ($term) {
                        $inner->where('name', 'like', $term)
                            ->orWhere('sku', 'like', $term)
                            ->orWhere('barcode', 'like', $term);
                    });
                }
                if ($request->filled('category_id')) {
                    $q->where('category_id', $request->integer('category_id'));
                }
            })
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('status'), function ($q) use ($request) {
                $status = $request->string('status')->toString();
                if ($status === 'out') {
                    $q->where('quantity', '<=', 0);
                } elseif ($status === 'low') {
                    $q->whereColumn('quantity', '<=', 'min_quantity')->where('quantity', '>', 0)->where('min_quantity', '>', 0);
                } elseif ($status === 'over') {
                    $q->whereNotNull('max_quantity')->whereColumn('quantity', '>', 'max_quantity');
                } elseif ($status === 'ok') {
                    $q->where('quantity', '>', 0)
                        ->where(function ($inner) {
                            $inner->where('min_quantity', '<=', 0)
                                ->orWhereColumn('quantity', '>', 'min_quantity');
                        })
                        ->where(function ($inner) {
                            $inner->whereNull('max_quantity')
                                ->orWhereColumn('quantity', '<=', 'max_quantity');
                        });
                }
            });

        $sort = $request->string('sort', 'updated_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowed = ['quantity', 'min_quantity', 'max_quantity', 'updated_at', 'last_movement_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'updated_at';
        }
        $query->orderBy($sort, $dir);

        $levels = $query->paginate(15)->withQueryString();

        return view('stock.levels', [
            'levels' => $levels,
            'columns' => $columns,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['q', 'store_id', 'category_id', 'status', 'sort', 'dir']),
            'canViewPurchase' => Workspace::can('products.view_purchase_price') || Workspace::can('stock.valuation'),
        ]);
    }

    public function updateLevel(Request $request, StockLevel $level): RedirectResponse
    {
        $this->authorize('stock.adjust');
        $this->ensureCompany($level);

        $data = $request->validate([
            'min_quantity' => ['nullable', 'numeric', 'min:0'],
            'max_quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->stock->updateThresholds(
            $level,
            isset($data['min_quantity']) ? (float) $data['min_quantity'] : null,
            array_key_exists('max_quantity', $data) ? ($data['max_quantity'] !== null ? (float) $data['max_quantity'] : null) : $level->max_quantity
        );

        return back()->with('success', 'Seuils de stock mis à jour.');
    }

    public function exportLevels(Request $request): StreamedResponse
    {
        $this->authorize('stock.export');
        $company = Workspace::company();
        $filename = 'stock-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sku', 'product', 'category', 'store', 'quantity', 'min', 'max', 'value', 'status'], ';');

            StockLevel::query()
                ->forCompany($company->id)
                ->with(['product.category', 'store'])
                ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
                ->orderBy('id')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $level) {
                        fputcsv($out, [
                            $level->product?->sku,
                            $level->product?->name,
                            $level->product?->category?->name,
                            $level->store?->name,
                            $level->quantity,
                            $level->min_quantity,
                            $level->max_quantity,
                            number_format($level->valuation(), 2, '.', ''),
                            $level->statusLabel(),
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function ensureCompany(StockLevel|StockMovement $model): void
    {
        if ($model->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

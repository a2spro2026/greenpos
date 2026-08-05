<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\StockLevel;
use App\Models\Store;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockAlertController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('stock.view');
        $company = Workspace::company();
        $type = $request->string('type', 'low')->toString();
        if (! in_array($type, ['low', 'out', 'over'], true)) {
            $type = 'low';
        }

        $query = StockLevel::query()
            ->forCompany($company->id)
            ->with(['product.category', 'store'])
            ->whereHas('product', fn ($q) => $q->where('track_stock', true))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('category_id'), function ($q) use ($request) {
                $q->whereHas('product', fn ($p) => $p->where('category_id', $request->integer('category_id')));
            });

        if ($type === 'out') {
            $query->where('quantity', '<=', 0);
        } elseif ($type === 'over') {
            $query->whereNotNull('max_quantity')->whereColumn('quantity', '>', 'max_quantity');
        } else {
            $query->where('quantity', '>', 0)
                ->where('min_quantity', '>', 0)
                ->whereColumn('quantity', '<=', 'min_quantity');
        }

        $alerts = $query->orderBy('quantity')->paginate(20)->withQueryString();

        $counts = [
            'low' => StockLevel::query()->forCompany($company->id)->where('quantity', '>', 0)->where('min_quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity')->count(),
            'out' => StockLevel::query()->forCompany($company->id)->where('quantity', '<=', 0)->count(),
            'over' => StockLevel::query()->forCompany($company->id)->whereNotNull('max_quantity')->whereColumn('quantity', '>', 'max_quantity')->count(),
        ];

        return view('stock.alerts', [
            'alerts' => $alerts,
            'type' => $type,
            'counts' => $counts,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'filters' => $request->only(['store_id', 'category_id', 'type']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('stock.export');
        $company = Workspace::company();
        $type = $request->string('type', 'low')->toString();
        $filename = 'alertes-stock-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request, $type) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['sku', 'product', 'store', 'quantity', 'min', 'max', 'status'], ';');

            $query = StockLevel::query()->forCompany($company->id)->with(['product', 'store']);
            if ($type === 'out') {
                $query->where('quantity', '<=', 0);
            } elseif ($type === 'over') {
                $query->whereNotNull('max_quantity')->whereColumn('quantity', '>', 'max_quantity');
            } else {
                $query->where('quantity', '>', 0)->where('min_quantity', '>', 0)->whereColumn('quantity', '<=', 'min_quantity');
            }
            $query->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')));

            foreach ($query->get() as $level) {
                fputcsv($out, [
                    $level->product?->sku,
                    $level->product?->name,
                    $level->store?->name,
                    $level->quantity,
                    $level->min_quantity,
                    $level->max_quantity,
                    $level->statusLabel(),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

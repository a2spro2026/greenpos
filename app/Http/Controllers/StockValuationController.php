<?php

namespace App\Http\Controllers;

use App\Models\StockLevel;
use App\Support\Workspace;
use Illuminate\View\View;

class StockValuationController extends Controller
{
    public function index(): View
    {
        $this->authorize('stock.valuation');
        $company = Workspace::company();

        $levels = StockLevel::query()
            ->forCompany($company->id)
            ->with(['product.category', 'store'])
            ->whereHas('product', fn ($q) => $q->where('track_stock', true))
            ->get();

        $total = $levels->sum(fn (StockLevel $l) => $l->valuation());

        $byCategory = $levels
            ->groupBy(fn (StockLevel $l) => $l->product?->category?->name ?: 'Sans catégorie')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'value' => round($group->sum(fn (StockLevel $l) => $l->valuation()), 2),
                'qty' => round($group->sum(fn (StockLevel $l) => (float) $l->quantity), 2),
            ])
            ->sortByDesc('value')
            ->values();

        $byStore = $levels
            ->groupBy(fn (StockLevel $l) => $l->store?->name ?: 'Boutique')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'value' => round($group->sum(fn (StockLevel $l) => $l->valuation()), 2),
                'qty' => round($group->sum(fn (StockLevel $l) => (float) $l->quantity), 2),
                'skus' => $group->count(),
            ])
            ->sortByDesc('value')
            ->values();

        $topProducts = $levels
            ->sortByDesc(fn (StockLevel $l) => $l->valuation())
            ->take(8)
            ->values();

        return view('stock.valuation', compact('total', 'byCategory', 'byStore', 'topProducts'));
    }
}

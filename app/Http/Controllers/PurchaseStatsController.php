<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Support\Workspace;
use Illuminate\View\View;

class PurchaseStatsController extends Controller
{
    public function index(): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();

        $orders = PurchaseOrder::query()
            ->forCompany($company->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with(['supplier', 'store', 'lines.product.category'])
            ->get();

        $evolution = collect(range(11, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M y'),
                'total' => (float) PurchaseOrder::query()
                    ->forCompany($company->id)
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->whereBetween('ordered_at', [$month, (clone $month)->endOfMonth()])
                    ->sum('total_ttc'),
            ];
        });

        $bySupplier = $orders
            ->groupBy(fn ($o) => $o->supplier?->name ?: 'Fournisseur')
            ->map(fn ($g, $name) => ['name' => $name, 'total' => round($g->sum('total_ttc'), 2)])
            ->sortByDesc('total')
            ->values();

        $byStore = $orders
            ->groupBy(fn ($o) => $o->store?->name ?: 'Boutique')
            ->map(fn ($g, $name) => ['name' => $name, 'total' => round($g->sum('total_ttc'), 2)])
            ->sortByDesc('total')
            ->values();

        $byCategoryMap = [];
        foreach ($orders as $order) {
            foreach ($order->lines as $line) {
                $cat = $line->product?->category?->name ?: 'Sans catégorie';
                $byCategoryMap[$cat] = ($byCategoryMap[$cat] ?? 0) + (float) $line->line_total;
            }
        }
        $byCategory = collect($byCategoryMap)
            ->map(fn ($total, $name) => ['name' => $name, 'total' => round($total, 2)])
            ->sortByDesc('total')
            ->values();

        return view('purchases.stats', compact('evolution', 'bySupplier', 'byStore', 'byCategory'));
    }
}

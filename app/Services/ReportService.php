<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\StockInventory;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    public const VALID_SALE_STATUSES = ['confirmed', 'preparing', 'delivered', 'completed', 'returned'];

    public function parseFilters(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->string('from'))
            : now()->startOfMonth();
        $to = $request->filled('to')
            ? Carbon::parse($request->string('to'))->endOfDay()
            : now()->endOfDay();

        return [
            'from' => $from,
            'to' => $to,
            'store_id' => $request->filled('store_id') ? $request->integer('store_id') : null,
            'user_id' => $request->filled('user_id') ? $request->integer('user_id') : null,
            'category_id' => $request->filled('category_id') ? $request->integer('category_id') : null,
            'product_id' => $request->filled('product_id') ? $request->integer('product_id') : null,
            'customer_id' => $request->filled('customer_id') ? $request->integer('customer_id') : null,
            'period' => $request->string('period', 'month')->toString(),
        ];
    }

    public function filterOptions(int $companyId): array
    {
        return [
            'stores' => Store::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))->orderBy('name')->get(['id', 'name']),
            'categories' => Category::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->forCompany($companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
            'customers' => Customer::query()->forCompany($companyId)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function biDashboard(int $companyId, array $filters): array
    {
        $sales = $this->salesQuery($companyId, $filters)->get();
        $posOnly = $this->posOnlyQuery($companyId, $filters)->get();

        $revenue = (float) $sales->sum('total_ttc') + (float) $posOnly->sum('total_ttc');
        $count = $sales->count() + $posOnly->count();
        $avgTicket = $count > 0 ? round($revenue / $count, 2) : 0;

        $margin = $this->estimateMargin($companyId, $filters);

        $activeCustomers = $sales->whereNotNull('customer_id')->pluck('customer_id')
            ->merge($posOnly->whereNotNull('customer_id')->pluck('customer_id'))
            ->unique()->count();

        $topProducts = $this->topProducts($companyId, $filters, 6);
        $criticalStock = StockLevel::query()
            ->forCompany($companyId)
            ->with('product')
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->whereHas('product', fn ($q) => $q->where('track_stock', true))
            ->get()
            ->filter(fn (StockLevel $l) => in_array($l->stockStatus(), ['low', 'out'], true))
            ->take(8)
            ->values();

        $monthly = collect(range(11, 0))->map(function (int $i) use ($companyId, $filters) {
            $m = now()->subMonths($i)->startOfMonth();
            $f = array_merge($filters, ['from' => $m, 'to' => (clone $m)->endOfMonth()]);
            $rev = (float) $this->salesQuery($companyId, $f)->sum('total_ttc')
                + (float) $this->posOnlyQuery($companyId, $f)->sum('total_ttc');

            return ['label' => $m->format('M y'), 'total' => round($rev, 2)];
        });

        $daily = collect(range(6, 0))->map(function (int $i) use ($companyId, $filters) {
            $d = now()->subDays($i)->startOfDay();
            $f = array_merge($filters, ['from' => $d, 'to' => (clone $d)->endOfDay()]);
            $rev = (float) $this->salesQuery($companyId, $f)->sum('total_ttc')
                + (float) $this->posOnlyQuery($companyId, $f)->sum('total_ttc');

            return ['label' => $d->format('D d'), 'total' => round($rev, 2)];
        });

        $prevMonth = array_merge($filters, [
            'from' => now()->subMonth()->startOfMonth(),
            'to' => now()->subMonth()->endOfMonth(),
        ]);
        $prevRevenue = (float) $this->salesQuery($companyId, $prevMonth)->sum('total_ttc')
            + (float) $this->posOnlyQuery($companyId, $prevMonth)->sum('total_ttc');
        $curMonth = array_merge($filters, ['from' => now()->startOfMonth(), 'to' => now()->endOfDay()]);
        $curRevenue = (float) $this->salesQuery($companyId, $curMonth)->sum('total_ttc')
            + (float) $this->posOnlyQuery($companyId, $curMonth)->sum('total_ttc');
        $growth = $prevRevenue > 0 ? round((($curRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : 0;

        $productsSold = $this->totalProductsSold($companyId, $filters);

        return compact('revenue', 'count', 'avgTicket', 'margin', 'activeCustomers', 'topProducts', 'criticalStock', 'monthly', 'daily', 'growth', 'productsSold', 'curRevenue');
    }

    public function salesReport(int $companyId, array $filters): array
    {
        $sales = $this->salesQuery($companyId, $filters)
            ->with(['customer', 'store', 'salesperson', 'lines'])
            ->orderByDesc('sold_at')
            ->get();

        $posOnly = $this->posOnlyQuery($companyId, $filters)
            ->with(['customer', 'store', 'cashier', 'lines'])
            ->orderByDesc('completed_at')
            ->get();

        $byDay = $this->groupByPeriod($sales, $posOnly, 'day', $filters);
        $byMonth = $this->groupByPeriod($sales, $posOnly, 'month', $filters);
        $byYear = $this->groupByPeriod($sales, $posOnly, 'year', $filters);

        $byStore = $this->aggregateByField($sales, $posOnly, 'store', 'store_id');
        $bySalesperson = $this->aggregateByUser($sales, $posOnly);

        $totalRevenue = (float) $sales->sum('total_ttc') + (float) $posOnly->sum('total_ttc');
        $totalCount = $sales->count() + $posOnly->count();

        return compact('sales', 'posOnly', 'byDay', 'byMonth', 'byYear', 'byStore', 'bySalesperson', 'totalRevenue', 'totalCount');
    }

    public function productsReport(int $companyId, array $filters): array
    {
        $topProducts = $this->topProducts($companyId, $filters, 15);
        $slowProducts = $this->slowProducts($companyId, $filters, 15);
        $noMovement = $this->productsNoMovement($companyId, $filters);
        $rotation = $this->stockRotation($companyId, $filters);

        $byCategory = $this->salesByCategory($companyId, $filters);

        return compact('topProducts', 'slowProducts', 'noMovement', 'rotation', 'byCategory');
    }

    public function customersReport(int $companyId, array $filters): array
    {
        $sales = $this->salesQuery($companyId, $filters)->with('customer')->get();
        $posOnly = $this->posOnlyQuery($companyId, $filters)->with('customer')->get();

        $customerMap = [];
        foreach ($sales as $s) {
            if (! $s->customer_id) { continue; }
            $id = $s->customer_id;
            $customerMap[$id] = $customerMap[$id] ?? ['customer' => $s->customer, 'count' => 0, 'total' => 0];
            $customerMap[$id]['count']++;
            $customerMap[$id]['total'] += (float) $s->total_ttc;
        }
        foreach ($posOnly as $s) {
            if (! $s->customer_id) { continue; }
            $id = $s->customer_id;
            $customerMap[$id] = $customerMap[$id] ?? ['customer' => $s->customer, 'count' => 0, 'total' => 0];
            $customerMap[$id]['count']++;
            $customerMap[$id]['total'] += (float) $s->total_ttc;
        }

        $bestCustomers = collect($customerMap)
            ->sortByDesc('total')
            ->take(15)
            ->values();

        $newCustomers = Customer::query()
            ->forCompany($companyId)
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        $inactiveCustomers = Customer::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->where(function ($q) use ($filters) {
                $q->whereNull('last_purchase_at')
                    ->orWhere('last_purchase_at', '<', $filters['from']);
            })
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->orderBy('last_purchase_at')
            ->limit(15)
            ->get();

        $revenueByCustomer = $bestCustomers;

        return compact('bestCustomers', 'newCustomers', 'inactiveCustomers', 'revenueByCustomer');
    }

    public function paymentsReport(int $companyId, array $filters): array
    {
        $invoicePayments = InvoicePayment::query()
            ->whereHas('invoice', function ($q) use ($companyId, $filters) {
                $q->forCompany($companyId)
                    ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                    ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id));
            })
            ->whereBetween('paid_at', [$filters['from'], $filters['to']])
            ->get();

        $salePayments = SalePayment::query()
            ->whereHas('sale', function ($q) use ($companyId, $filters) {
                $q->forCompany($companyId)
                    ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                    ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id));
            })
            ->whereBetween('paid_at', [$filters['from'], $filters['to']])
            ->get();

        $linkedPosIds = Sale::query()->forCompany($companyId)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');

        $posPayments = PosPayment::query()
            ->whereHas('sale', function ($q) use ($companyId, $filters, $linkedPosIds) {
                $q->forCompany($companyId)->where('status', 'completed')
                    ->whereNotIn('id', $linkedPosIds)
                    ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                    ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id))
                    ->whereBetween('completed_at', [$filters['from'], $filters['to']]);
            })
            ->get();

        $allPayments = collect()
            ->merge($invoicePayments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount, 'source' => 'invoice', 'date' => $p->paid_at]))
            ->merge($salePayments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount, 'source' => 'sale', 'date' => $p->paid_at]))
            ->merge($posPayments->map(fn ($p) => ['method' => $p->method, 'amount' => (float) $p->amount, 'source' => 'pos', 'date' => $p->sale?->completed_at]));

        $byMethod = $allPayments->groupBy('method')
            ->map(fn ($g, $method) => [
                'method' => $method,
                'label' => InvoicePayment::METHODS[$method] ?? $method,
                'total' => round($g->sum('amount'), 2),
                'count' => $g->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $pendingInvoices = Invoice::query()
            ->forCompany($companyId)
            ->invoices()
            ->whereIn('status', ['pending', 'partial', 'expired'])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id))
            ->sum('balance_due');

        $validatedTotal = round($allPayments->sum('amount'), 2);

        $refunds = SaleReturn::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id)))
            ->whereBetween('returned_at', [$filters['from'], $filters['to']])
            ->sum('total_returned');

        $monthly = collect(range(5, 0))->map(function (int $i) use ($companyId, $filters) {
            $m = now()->subMonths($i)->startOfMonth();
            $end = (clone $m)->endOfMonth();
            $total = InvoicePayment::query()
                ->whereHas('invoice', fn ($q) => $q->forCompany($companyId))
                ->whereBetween('paid_at', [$m, $end])
                ->sum('amount')
                + SalePayment::query()
                    ->whereHas('sale', fn ($q) => $q->forCompany($companyId))
                    ->whereBetween('paid_at', [$m, $end])
                    ->sum('amount');

            return ['label' => $m->format('M'), 'total' => round((float) $total, 2)];
        });

        return compact('byMethod', 'pendingInvoices', 'validatedTotal', 'refunds', 'monthly', 'allPayments');
    }

    public function stockReport(int $companyId, array $filters): array
    {
        $movements = StockMovement::query()
            ->forCompany($companyId)
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['product_id'], fn ($q, $id) => $q->where('product_id', $id))
            ->whereBetween('moved_at', [$filters['from'], $filters['to']])
            ->with(['product', 'store'])
            ->get();

        $entries = $movements->where('type', 'in');
        $exits = $movements->where('type', 'out');

        $inventories = StockInventory::query()
            ->forCompany($companyId)
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->whereBetween('created_at', [$filters['from'], $filters['to']])
            ->with('store')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $belowThreshold = StockLevel::query()
            ->forCompany($companyId)
            ->with(['product.category', 'store'])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->whereHas('product', fn ($q) => $q->where('track_stock', true))
            ->get()
            ->filter(fn (StockLevel $l) => in_array($l->stockStatus(), ['low', 'out'], true))
            ->sortBy('quantity')
            ->values();

        $levels = StockLevel::query()
            ->forCompany($companyId)
            ->with(['product.category', 'store'])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->whereHas('product', fn ($q) => $q->where('track_stock', true))
            ->get();

        $valuation = round($levels->sum(fn (StockLevel $l) => $l->valuation()), 2);

        $byCategory = $levels
            ->groupBy(fn (StockLevel $l) => $l->product?->category?->name ?: 'Sans catégorie')
            ->map(fn ($g, $name) => [
                'name' => $name,
                'value' => round($g->sum(fn (StockLevel $l) => $l->valuation()), 2),
                'qty' => round($g->sum(fn (StockLevel $l) => (float) $l->quantity), 2),
            ])
            ->sortByDesc('value')
            ->values();

        $monthlyMovements = collect(range(5, 0))->map(function (int $i) use ($companyId, $filters) {
            $m = now()->subMonths($i)->startOfMonth();
            $end = (clone $m)->endOfMonth();
            $in = StockMovement::query()->forCompany($companyId)
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                ->where('type', 'in')->whereBetween('moved_at', [$m, $end])->sum('quantity');
            $out = StockMovement::query()->forCompany($companyId)
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                ->where('type', 'out')->whereBetween('moved_at', [$m, $end])->sum('quantity');

            return ['label' => $m->format('M'), 'in' => round((float) $in, 2), 'out' => round((float) $out, 2)];
        });

        return compact('entries', 'exits', 'inventories', 'belowThreshold', 'valuation', 'byCategory', 'monthlyMovements');
    }

    protected function salesQuery(int $companyId, array $filters): Builder
    {
        return Sale::query()
            ->forCompany($companyId)
            ->whereIn('status', self::VALID_SALE_STATUSES)
            ->whereBetween('sold_at', [$filters['from'], $filters['to']])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['user_id'], fn ($q, $id) => $q->where('salesperson_id', $id))
            ->when($filters['product_id'], fn ($q, $id) => $q->whereHas('lines', fn ($l) => $l->where('product_id', $id)))
            ->when($filters['category_id'], fn ($q, $id) => $q->whereHas('lines.product', fn ($p) => $p->where('category_id', $id)));
    }

    protected function posOnlyQuery(int $companyId, array $filters): Builder
    {
        $linkedPosIds = Sale::query()->forCompany($companyId)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');

        return PosSale::query()
            ->forCompany($companyId)
            ->where('status', 'completed')
            ->whereNotIn('id', $linkedPosIds)
            ->whereBetween('completed_at', [$filters['from'], $filters['to']])
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->when($filters['customer_id'], fn ($q, $id) => $q->where('customer_id', $id))
            ->when($filters['user_id'], fn ($q, $id) => $q->where('cashier_id', $id))
            ->when($filters['product_id'], fn ($q, $id) => $q->whereHas('lines', fn ($l) => $l->where('product_id', $id)))
            ->when($filters['category_id'], fn ($q, $id) => $q->whereHas('lines.product', fn ($p) => $p->where('category_id', $id)));
    }

    protected function topProducts(int $companyId, array $filters, int $limit): Collection
    {
        $saleLines = SaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->whereIn('status', self::VALID_SALE_STATUSES)
                ->whereBetween('sold_at', [$filters['from'], $filters['to']])
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id)))
            ->when($filters['product_id'], fn ($q, $id) => $q->where('product_id', $id))
            ->when($filters['category_id'], fn ($q, $id) => $q->whereHas('product', fn ($p) => $p->where('category_id', $id)))
            ->selectRaw('product_id, product_name, sum(quantity) as qty, sum(line_total) as total')
            ->groupBy('product_id', 'product_name')
            ->get()
            ->keyBy('product_id');

        $linkedPosIds = Sale::query()->forCompany($companyId)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');
        $posLines = PosSaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->where('status', 'completed')
                ->whereNotIn('id', $linkedPosIds)
                ->whereBetween('completed_at', [$filters['from'], $filters['to']])
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id)))
            ->selectRaw('product_id, product_name, sum(quantity) as qty, sum(line_total) as total')
            ->groupBy('product_id', 'product_name')
            ->get();

        $merged = [];
        foreach ($saleLines as $pid => $line) {
            $merged[$pid] = ['product_id' => $pid, 'product_name' => $line->product_name, 'qty' => (float) $line->qty, 'total' => (float) $line->total];
        }
        foreach ($posLines as $line) {
            $pid = $line->product_id;
            if (isset($merged[$pid])) {
                $merged[$pid]['qty'] += (float) $line->qty;
                $merged[$pid]['total'] += (float) $line->total;
            } else {
                $merged[$pid] = ['product_id' => $pid, 'product_name' => $line->product_name, 'qty' => (float) $line->qty, 'total' => (float) $line->total];
            }
        }

        return collect($merged)->sortByDesc('total')->take($limit)->values();
    }

    protected function slowProducts(int $companyId, array $filters, int $limit): Collection
    {
        $soldIds = $this->topProducts($companyId, $filters, 999)->pluck('product_id')->filter()->all();

        return Product::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->when($filters['category_id'], fn ($q, $id) => $q->where('category_id', $id))
            ->whereNotIn('id', $soldIds)
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'sku', 'sale_price']);
    }

    protected function productsNoMovement(int $companyId, array $filters): Collection
    {
        $since = $filters['from'];
        $movedProductIds = StockMovement::query()
            ->forCompany($companyId)
            ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
            ->where('moved_at', '>=', $since)
            ->distinct()
            ->pluck('product_id');

        return Product::query()
            ->forCompany($companyId)
            ->where('status', 'active')
            ->where('track_stock', true)
            ->when($filters['category_id'], fn ($q, $id) => $q->where('category_id', $id))
            ->whereNotIn('id', $movedProductIds)
            ->with('category')
            ->limit(20)
            ->get();
    }

    protected function stockRotation(int $companyId, array $filters): Collection
    {
        $top = $this->topProducts($companyId, $filters, 20);

        return $top->map(function ($item) use ($companyId, $filters) {
            $level = StockLevel::query()
                ->forCompany($companyId)
                ->where('product_id', $item['product_id'])
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id))
                ->sum('quantity');
            $avgStock = max(1, (float) $level);
            $rotation = round($item['qty'] / $avgStock, 2);

            return array_merge($item, ['stock' => (float) $level, 'rotation' => $rotation]);
        });
    }

    protected function salesByCategory(int $companyId, array $filters): Collection
    {
        $map = [];
        $saleLines = SaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->whereIn('status', self::VALID_SALE_STATUSES)
                ->whereBetween('sold_at', [$filters['from'], $filters['to']]))
            ->with('product.category')
            ->get();

        foreach ($saleLines as $line) {
            $cat = $line->product?->category?->name ?: 'Sans catégorie';
            $map[$cat] = ($map[$cat] ?? 0) + (float) $line->line_total;
        }

        return collect($map)->map(fn ($total, $name) => ['name' => $name, 'total' => round($total, 2)])->sortByDesc('total')->values();
    }

    protected function estimateMargin(int $companyId, array $filters): float
    {
        $revenue = 0;
        $cost = 0;

        $saleLines = SaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->whereIn('status', self::VALID_SALE_STATUSES)
                ->whereBetween('sold_at', [$filters['from'], $filters['to']])
                ->when($filters['store_id'], fn ($q, $id) => $q->where('store_id', $id)))
            ->with('product')
            ->get();

        foreach ($saleLines as $line) {
            $revenue += (float) $line->line_subtotal;
            $cost += (float) $line->quantity * (float) ($line->product?->purchase_price ?? 0);
        }

        return round($revenue - $cost, 2);
    }

    protected function totalProductsSold(int $companyId, array $filters): int
    {
        $saleQty = (float) SaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->whereIn('status', self::VALID_SALE_STATUSES)
                ->whereBetween('sold_at', [$filters['from'], $filters['to']]))
            ->sum('quantity');

        $linkedPosIds = Sale::query()->forCompany($companyId)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');
        $posQty = (float) PosSaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($companyId)->where('status', 'completed')
                ->whereNotIn('id', $linkedPosIds)
                ->whereBetween('completed_at', [$filters['from'], $filters['to']]))
            ->sum('quantity');

        return (int) round($saleQty + $posQty);
    }

    protected function groupByPeriod(Collection $sales, Collection $posOnly, string $period, array $filters): Collection
    {
        $map = [];
        $format = match ($period) {
            'day' => 'Y-m-d',
            'month' => 'Y-m',
            'year' => 'Y',
        };
        $labelFormat = match ($period) {
            'day' => 'd/m/Y',
            'month' => 'M Y',
            'year' => 'Y',
        };

        foreach ($sales as $s) {
            $key = $s->sold_at->format($format);
            $map[$key] = $map[$key] ?? ['label' => $s->sold_at->format($labelFormat), 'count' => 0, 'total' => 0];
            $map[$key]['count']++;
            $map[$key]['total'] += (float) $s->total_ttc;
        }
        foreach ($posOnly as $s) {
            $date = $s->completed_at ?? $s->created_at;
            $key = $date->format($format);
            $map[$key] = $map[$key] ?? ['label' => $date->format($labelFormat), 'count' => 0, 'total' => 0];
            $map[$key]['count']++;
            $map[$key]['total'] += (float) $s->total_ttc;
        }

        return collect($map)->sortKeys()->values()->map(fn ($item) => [
            'label' => $item['label'],
            'count' => $item['count'],
            'total' => round($item['total'], 2),
        ]);
    }

    protected function aggregateByField(Collection $sales, Collection $posOnly, string $relation, string $field): Collection
    {
        $map = [];
        foreach ($sales as $s) {
            $name = $s->{$relation}?->name ?? '—';
            $map[$name] = ($map[$name] ?? 0) + (float) $s->total_ttc;
        }
        foreach ($posOnly as $s) {
            $name = $s->{$relation}?->name ?? '—';
            $map[$name] = ($map[$name] ?? 0) + (float) $s->total_ttc;
        }

        return collect($map)->map(fn ($total, $name) => ['name' => $name, 'total' => round($total, 2)])->sortByDesc('total')->values();
    }

    protected function aggregateByUser(Collection $sales, Collection $posOnly): Collection
    {
        $map = [];
        foreach ($sales as $s) {
            $name = $s->salesperson?->name ?? '—';
            $map[$name] = ($map[$name] ?? 0) + (float) $s->total_ttc;
        }
        foreach ($posOnly as $s) {
            $name = $s->cashier?->name ?? '—';
            $map[$name] = ($map[$name] ?? 0) + (float) $s->total_ttc;
        }

        return collect($map)->map(fn ($total, $name) => ['name' => $name, 'total' => round($total, 2)])->sortByDesc('total')->values();
    }
}

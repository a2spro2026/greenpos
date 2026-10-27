<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PosCashMovement;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\Production;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Rimal POS V2 — phase 4 : pilotage (comparaison de périodes, marge, trésorerie)
 * et rapports complémentaires (achats, commandes, production, financier, collaborateurs).
 * Toutes les données viennent de la base de la société active ; rien n'est codé en dur.
 */
class RimalReportService
{
    public const VALID_SALE_STATUSES = ['confirmed', 'preparing', 'delivered', 'completed', 'returned'];

    public const OPEN_COLLECTION = ['scheduled', 'pending', 'overdue'];

    public const REPORTS = [
        'achats' => 'Achats',
        'commandes' => 'Commandes',
        'production' => 'Production',
        'financier' => 'Financier',
        'collaborateurs' => 'Collaborateurs',
    ];

    /** Période précédente de même durée, juste avant [from, to]. */
    public function previousPeriod(Carbon $from, Carbon $to): array
    {
        $days = max(1, (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1);
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [$prevFrom, $prevTo];
    }

    public function metrics(int $companyId, Carbon $from, Carbon $to, ?int $storeId = null): array
    {
        $linked = Sale::query()->where('company_id', $companyId)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');

        $sales = Sale::query()->where('company_id', $companyId)
            ->whereIn('status', self::VALID_SALE_STATUSES)
            ->whereBetween('sold_at', [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId));
        $pos = PosSale::query()->where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereNotIn('id', $linked)
            ->whereBetween('completed_at', [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId));

        $revenue = (float) (clone $sales)->sum('total_ttc') + (float) (clone $pos)->sum('total_ttc');
        $revenueHt = (float) (clone $sales)->sum('subtotal_ht') + (float) (clone $pos)->sum('subtotal_ht');
        $count = (clone $sales)->count() + (clone $pos)->count();

        $saleIds = (clone $sales)->pluck('id');
        $posIds = (clone $pos)->pluck('id');
        $cost = (float) DB::table('sale_lines')->join('products', 'products.id', '=', 'sale_lines.product_id')
            ->whereIn('sale_lines.sale_id', $saleIds)
            ->sum(DB::raw('sale_lines.quantity * COALESCE(products.purchase_price, 0)'))
            + (float) DB::table('pos_sale_lines')->join('products', 'products.id', '=', 'pos_sale_lines.product_id')
            ->whereIn('pos_sale_lines.pos_sale_id', $posIds)
            ->sum(DB::raw('pos_sale_lines.quantity * COALESCE(products.purchase_price, 0)'));
        $margin = round($revenueHt - $cost, 2);

        $purchases = (float) PurchaseOrder::query()->where('company_id', $companyId)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->whereBetween('ordered_at', [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->sum('total_ttc');
        $expenses = (float) Expense::query()->where('company_id', $companyId)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->sum('amount');

        $inflows = $this->inflowsByMethod($companyId, $from, $to, $storeId);
        $cashIn = (float) PosCashMovement::query()->where('company_id', $companyId)->where('direction', 'in')
            ->whereBetween('created_at', [$from, $to])->when($storeId, fn ($q) => $q->where('store_id', $storeId))->sum('amount');
        $cashOut = (float) PosCashMovement::query()->where('company_id', $companyId)->where('direction', 'out')
            ->whereBetween('created_at', [$from, $to])->when($storeId, fn ($q) => $q->where('store_id', $storeId))->sum('amount');
        $collected = array_sum($inflows);
        $treasury = round($collected + $cashIn - $cashOut - $expenses, 2);
        $receivable = $this->openReceivables($companyId, $storeId);

        return [
            'revenue' => round($revenue, 2),
            'revenue_ht' => round($revenueHt, 2),
            'count' => $count,
            'avg_ticket' => $count ? round($revenue / $count, 2) : 0.0,
            'margin' => $margin,
            'margin_rate' => $revenueHt > 0 ? round($margin / $revenueHt * 100, 1) : 0.0,
            'purchases' => round($purchases, 2),
            'expenses' => round($expenses, 2),
            'collected' => round($collected, 2),
            'cash_in' => round($cashIn, 2),
            'cash_out' => round($cashOut, 2),
            'treasury' => $treasury,
            'receivable' => $receivable,
            'inflows' => $inflows,
        ];
    }

    /** Encaissements effectifs par mode (hors paiements différés non encore encaissés). */
    public function inflowsByMethod(int $companyId, Carbon $from, Carbon $to, ?int $storeId = null): array
    {
        $out = [];
        $pos = PosPayment::query()
            ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId)->where('status', 'completed')
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId)))
            ->where(fn ($q) => $q->whereNull('collection_status')->orWhereNotIn('collection_status', array_merge(self::OPEN_COLLECTION, ['cancelled'])))
            ->whereBetween(DB::raw('COALESCE(collected_at, created_at)'), [$from, $to])
            ->get(['method', 'amount', 'change_amount']);
        foreach ($pos as $p) {
            $label = PosPayment::METHODS[$p->method] ?? $p->method;
            $out[$label] = ($out[$label] ?? 0) + (float) $p->amount;
        }
        $sp = SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId)->whereNull('pos_sale_id')
                ->when($storeId, fn ($q) => $q->where('store_id', $storeId)))
            ->where(fn ($q) => $q->whereNull('collection_status')->orWhereNotIn('collection_status', array_merge(self::OPEN_COLLECTION, ['cancelled'])))
            ->whereBetween(DB::raw('COALESCE(collected_at, paid_at, created_at)'), [$from, $to])
            ->get(['method', 'amount']);
        foreach ($sp as $p) {
            $label = (SalePayment::METHODS[$p->method] ?? null) ?: (PosPayment::METHODS[$p->method] ?? $p->method);
            $out[$label] = ($out[$label] ?? 0) + (float) $p->amount;
        }
        arsort($out);

        return array_map(fn ($v) => round($v, 2), $out);
    }

    public function openReceivables(int $companyId, ?int $storeId = null): float
    {
        $pos = (float) PosPayment::query()
            ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId)->when($storeId, fn ($q) => $q->where('store_id', $storeId)))
            ->where('is_deferred', true)->whereIn('collection_status', self::OPEN_COLLECTION)->sum('amount');
        $sale = (float) SalePayment::query()
            ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId)->when($storeId, fn ($q) => $q->where('store_id', $storeId)))
            ->where('is_deferred', true)->whereIn('collection_status', self::OPEN_COLLECTION)->sum('amount');

        return round($pos + $sale, 2);
    }

    public function pilotage(int $companyId, Carbon $from, Carbon $to, ?int $storeId = null): array
    {
        [$pFrom, $pTo] = $this->previousPeriod($from, $to);
        $current = $this->metrics($companyId, $from, $to, $storeId);
        $previous = $this->metrics($companyId, $pFrom, $pTo, $storeId);
        $variation = [];
        foreach ($current as $k => $v) {
            if (is_numeric($v) && is_numeric($previous[$k] ?? null)) {
                $p = (float) $previous[$k];
                $variation[$k] = $p != 0.0 ? round(((float) $v - $p) / abs($p) * 100, 1) : null;
            }
        }
        $stores = Store::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']);
        $byStore = $storeId ? collect() : $stores->map(fn ($s) => ['store' => $s->name] + $this->metrics($companyId, $from, $to, $s->id));

        return compact('current', 'previous', 'variation', 'byStore', 'stores', 'pFrom', 'pTo');
    }

    /** @return array{title:string, columns:array<string,string>, rows:array<int,array>, totals:array<string,float>, money:array<int,string>} */
    public function report(string $type, int $companyId, Carbon $from, Carbon $to, ?int $storeId = null, ?int $userId = null): array
    {
        return match ($type) {
            'achats' => $this->achats($companyId, $from, $to, $storeId),
            'commandes' => $this->commandes($companyId, $from, $to, $storeId, $userId),
            'production' => $this->production($companyId, $from, $to, $storeId),
            'financier' => $this->financier($companyId, $from, $to, $storeId),
            'collaborateurs' => $this->collaborateurs($companyId, $from, $to, $storeId),
            default => abort(404),
        };
    }

    protected function achats(int $c, Carbon $from, Carbon $to, ?int $storeId): array
    {
        $rows = PurchaseOrder::query()->where('company_id', $c)->with(['supplier', 'store'])
            ->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->orderByDesc('ordered_at')->get()
            ->map(fn ($o) => [
                'date' => optional($o->ordered_at ?? $o->created_at)->format('d/m/Y'),
                'number' => $o->number,
                'supplier' => $o->supplier?->name ?? '—',
                'store' => $o->store?->name ?? '—',
                'status' => method_exists($o, 'statusLabel') ? $o->statusLabel() : $o->status,
                'ht' => (float) $o->subtotal_ht,
                'tva' => (float) $o->tax_total,
                'ttc' => (float) $o->total_ttc,
            ])->all();

        return $this->pack('Rapport des achats', [
            'date' => 'Date', 'number' => 'N° commande', 'supplier' => 'Fournisseur', 'store' => 'Boutique',
            'status' => 'Statut', 'ht' => 'Total HT', 'tva' => 'TVA', 'ttc' => 'Total TTC',
        ], $rows, ['ht', 'tva', 'ttc']);
    }

    protected function commandes(int $c, Carbon $from, Carbon $to, ?int $storeId, ?int $userId): array
    {
        $stockLabels = ['reserved' => 'Sortie en cours', 'out' => 'Livrée (stock sorti)'];
        $rows = Sale::query()->where('company_id', $c)->with(['customer', 'store', 'salesperson'])
            ->whereNull('pos_sale_id')
            ->whereBetween(DB::raw('COALESCE(sold_at, created_at)'), [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->when($userId, fn ($q) => $q->where('salesperson_id', $userId))
            ->orderByDesc('sold_at')->get()
            ->map(fn ($s) => [
                'date' => optional($s->sold_at ?? $s->created_at)->format('d/m/Y'),
                'number' => $s->number,
                'customer' => $s->customer?->name ?? '—',
                'store' => $s->store?->name ?? '—',
                'salesperson' => $s->salesperson?->name ?? '—',
                'status' => $s->statusLabel(),
                'stock' => $stockLabels[$s->stock_state] ?? '—',
                'delivery' => optional($s->expected_delivery_at)->format('d/m/Y') ?? '—',
                'ttc' => (float) $s->total_ttc,
                'paid' => (float) $s->amount_paid,
                'due' => round((float) $s->total_ttc - (float) $s->amount_paid, 2),
            ])->all();

        return $this->pack('Rapport des commandes', [
            'date' => 'Date', 'number' => 'N°', 'customer' => 'Client', 'store' => 'Boutique', 'salesperson' => 'Commercial',
            'status' => 'Statut', 'stock' => 'Stock', 'delivery' => 'Livraison prévue', 'ttc' => 'Total TTC', 'paid' => 'Payé', 'due' => 'Reste',
        ], $rows, ['ttc', 'paid', 'due']);
    }

    protected function production(int $c, Carbon $from, Carbon $to, ?int $storeId): array
    {
        $rows = Production::query()->where('company_id', $c)->with(['product', 'store'])
            ->whereBetween(DB::raw('COALESCE(produced_at, created_at)'), [$from, $to])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->orderByDesc('produced_at')->get()
            ->map(fn ($p) => [
                'date' => optional($p->produced_at ?? $p->created_at)->format('d/m/Y'),
                'number' => $p->number,
                'product' => $p->product?->name ?? '—',
                'store' => $p->store?->name ?? '—',
                'status' => method_exists($p, 'statusLabel') ? $p->statusLabel() : $p->status,
                'qty' => (float) $p->quantity,
                'unit_cost' => (float) $p->unit_cost,
                'total_cost' => (float) $p->total_cost,
            ])->all();

        return $this->pack('Rapport de production', [
            'date' => 'Date', 'number' => 'N°', 'product' => 'Produit', 'store' => 'Boutique', 'status' => 'Statut',
            'qty' => 'Quantité', 'unit_cost' => 'Coût unitaire', 'total_cost' => 'Coût total',
        ], $rows, ['qty', 'total_cost']);
    }

    protected function financier(int $c, Carbon $from, Carbon $to, ?int $storeId): array
    {
        $m = $this->metrics($c, $from, $to, $storeId);
        $rows = [];
        $rows[] = ['section' => 'Activité', 'label' => "Chiffre d'affaires TTC", 'amount' => $m['revenue']];
        $rows[] = ['section' => 'Activité', 'label' => "Chiffre d'affaires HT", 'amount' => $m['revenue_ht']];
        $rows[] = ['section' => 'Activité', 'label' => 'Marge brute estimée (HT − coût d\'achat)', 'amount' => $m['margin']];
        foreach ($m['inflows'] as $label => $amount) {
            $rows[] = ['section' => 'Encaissements', 'label' => $label, 'amount' => $amount];
        }
        $rows[] = ['section' => 'Caisse', 'label' => 'Entrées de caisse', 'amount' => $m['cash_in']];
        $rows[] = ['section' => 'Caisse', 'label' => 'Sorties de caisse', 'amount' => -$m['cash_out']];
        $cats = Expense::query()->where('company_id', $c)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw("COALESCE(category, 'Autres') as cat, SUM(amount) as total")->groupBy('cat')->get();
        foreach ($cats as $cat) {
            $rows[] = ['section' => 'Dépenses', 'label' => $cat->cat, 'amount' => -round((float) $cat->total, 2)];
        }
        $rows[] = ['section' => 'Achats', 'label' => 'Commandes fournisseurs (TTC)', 'amount' => $m['purchases']];
        $rows[] = ['section' => 'Synthèse', 'label' => 'Trésorerie nette de la période', 'amount' => $m['treasury']];
        $rows[] = ['section' => 'Synthèse', 'label' => 'Chèques / effets / crédits à encaisser (encours)', 'amount' => $m['receivable']];

        return ['title' => 'Rapport financier', 'columns' => ['section' => 'Rubrique', 'label' => 'Libellé', 'amount' => 'Montant'],
            'rows' => $rows, 'totals' => [], 'money' => ['amount']];
    }

    protected function collaborateurs(int $c, Carbon $from, Carbon $to, ?int $storeId): array
    {
        $linked = Sale::query()->where('company_id', $c)->whereNotNull('pos_sale_id')->pluck('pos_sale_id');
        $pos = PosSale::query()->where('company_id', $c)->where('status', 'completed')->whereNotIn('id', $linked)
            ->whereBetween('completed_at', [$from, $to])->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('cashier_id as uid, COUNT(*) as n, SUM(total_ttc) as total, SUM(discount_total) as disc')->groupBy('cashier_id')->get()->keyBy('uid');
        $sales = Sale::query()->where('company_id', $c)->whereIn('status', self::VALID_SALE_STATUSES)
            ->whereBetween('sold_at', [$from, $to])->when($storeId, fn ($q) => $q->where('store_id', $storeId))
            ->selectRaw('COALESCE(salesperson_id, created_by) as uid, COUNT(*) as n, SUM(total_ttc) as total, SUM(discount_total) as disc')
            ->groupBy(DB::raw('COALESCE(salesperson_id, created_by)'))->get()->keyBy('uid');
        $ids = $pos->keys()->merge($sales->keys())->filter()->unique();
        $users = User::query()->whereIn('id', $ids)->pluck('name', 'id');
        $rows = $ids->map(function ($id) use ($pos, $sales, $users) {
            $n = (int) ($pos[$id]->n ?? 0) + (int) ($sales[$id]->n ?? 0);
            $t = (float) ($pos[$id]->total ?? 0) + (float) ($sales[$id]->total ?? 0);

            return [
                'user' => $users[$id] ?? ('#'.$id),
                'pos_count' => (int) ($pos[$id]->n ?? 0),
                'sales_count' => (int) ($sales[$id]->n ?? 0),
                'revenue' => round($t, 2),
                'avg' => $n ? round($t / $n, 2) : 0.0,
                'discounts' => round((float) ($pos[$id]->disc ?? 0) + (float) ($sales[$id]->disc ?? 0), 2),
            ];
        })->sortByDesc('revenue')->values()->all();

        return $this->pack('Rapport collaborateurs', [
            'user' => 'Collaborateur', 'pos_count' => 'Tickets caisse', 'sales_count' => 'Ventes / commandes',
            'revenue' => 'CA TTC', 'avg' => 'Panier moyen', 'discounts' => 'Remises accordées',
        ], $rows, ['pos_count', 'sales_count', 'revenue', 'discounts'], ['revenue', 'avg', 'discounts']);
    }

    protected function pack(string $title, array $columns, array $rows, array $sum, ?array $money = null): array
    {
        $totals = [];
        foreach ($sum as $k) {
            $totals[$k] = round(array_sum(array_column($rows, $k)), 2);
        }
        $money ??= array_values(array_filter($sum, fn ($k) => ! in_array($k, ['qty', 'pos_count', 'sales_count'], true)));
        if (isset($columns['unit_cost'])) {
            $money[] = 'unit_cost';
        }

        return compact('title', 'columns', 'rows', 'totals', 'money');
    }

    /** Vue centrale : tous les documents commerciaux de la société. */
    public function documents(int $c, Carbon $from, Carbon $to, ?string $type = null, ?string $search = null): array
    {
        $docs = collect();
        $like = $search ? '%'.$search.'%' : null;
        if (! $type || $type === 'quote') {
            Quote::query()->where('company_id', $c)->with('customer')->whereBetween(DB::raw('COALESCE(quoted_at, created_at)'), [$from, $to])
                ->when($like, fn ($q) => $q->where('number', 'like', $like))->get()
                ->each(fn ($d) => $docs->push(['type' => 'Devis', 'date' => $d->quoted_at ?? $d->created_at, 'number' => $d->number, 'party' => $d->customer?->name, 'status' => $d->statusLabel(), 'total' => (float) $d->total_ttc, 'url' => route('quotes.show', $d), 'print' => route('quotes.print', $d)]));
        }
        if (! $type || $type === 'invoice') {
            Invoice::query()->where('company_id', $c)->with('customer')->whereBetween(DB::raw('COALESCE(invoiced_at, created_at)'), [$from, $to])
                ->when($like, fn ($q) => $q->where('number', 'like', $like))->get()
                ->each(fn ($d) => $docs->push(['type' => method_exists($d, 'typeLabel') ? $d->typeLabel() : 'Facture', 'date' => $d->invoiced_at ?? $d->created_at, 'number' => $d->number, 'party' => $d->customer?->name, 'status' => $d->statusLabel(), 'total' => (float) $d->total_ttc, 'url' => route('invoices.show', $d), 'print' => route('invoices.print', $d)]));
        }
        if (! $type || in_array($type, ['order', 'delivery'], true)) {
            Sale::query()->where('company_id', $c)->whereNull('pos_sale_id')->with('customer')->whereBetween(DB::raw('COALESCE(sold_at, created_at)'), [$from, $to])
                ->when($like, fn ($q) => $q->where('number', 'like', $like))->get()
                ->each(function ($d) use ($docs, $type) {
                    if (! $type || $type === 'order') {
                        $docs->push(['type' => 'Commande / vente', 'date' => $d->sold_at ?? $d->created_at, 'number' => $d->number, 'party' => $d->customer?->name, 'status' => $d->statusLabel(), 'total' => (float) $d->total_ttc, 'url' => route('sales.show', $d), 'print' => route('sales.print', $d)]);
                    }
                    if ((! $type || $type === 'delivery') && in_array($d->status, ['confirmed', 'preparing', 'delivered', 'completed'], true)) {
                        $docs->push(['type' => 'Bon de livraison', 'date' => $d->delivered_at ?? $d->sold_at ?? $d->created_at, 'number' => 'BL-'.$d->number, 'party' => $d->customer?->name, 'status' => $d->delivered_at ? 'Livré' : 'À livrer', 'total' => (float) $d->total_ttc, 'url' => route('sales.show', $d), 'print' => route('sales.delivery-note', $d)]);
                    }
                });
        }
        if (! $type || $type === 'purchase') {
            PurchaseOrder::query()->where('company_id', $c)->with('supplier')->whereBetween(DB::raw('COALESCE(ordered_at, created_at)'), [$from, $to])
                ->when($like, fn ($q) => $q->where('number', 'like', $like))->get()
                ->each(fn ($d) => $docs->push(['type' => 'Bon de commande fournisseur', 'date' => $d->ordered_at ?? $d->created_at, 'number' => $d->number, 'party' => $d->supplier?->name, 'status' => $d->statusLabel(), 'total' => (float) $d->total_ttc, 'url' => route('purchases.orders.show', $d), 'print' => route('purchases.orders.print', $d)]));
        }
        if (! $type || $type === 'ticket') {
            PosSale::query()->where('company_id', $c)->where('status', 'completed')->with('customer')->whereBetween('completed_at', [$from, $to])
                ->when($like, fn ($q) => $q->where('number', 'like', $like))->limit(500)->get()
                ->each(fn ($d) => $docs->push(['type' => 'Ticket de caisse', 'date' => $d->completed_at, 'number' => $d->number, 'party' => $d->customer?->name, 'status' => 'Encaissé', 'total' => (float) $d->total_ttc, 'url' => route('pos.tickets.show', $d), 'print' => route('pos.tickets.print', $d)]));
        }

        return $docs->sortByDesc(fn ($d) => optional($d['date'])->timestamp ?? 0)->values()->all();
    }
}

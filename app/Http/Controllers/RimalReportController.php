<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\BrandingService;
use App\Services\RimalReportService;
use App\Support\RimalSpreadsheet;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class RimalReportController extends Controller
{
    public function __construct(private RimalReportService $reports) {}

    protected function filters(Request $request): array
    {
        $from = $request->filled('from') ? Carbon::parse($request->string('from'))->startOfDay() : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->string('to'))->endOfDay() : now()->endOfDay();

        return [
            'from' => $from, 'to' => $to,
            'store_id' => $this->storeFilter($request),
            'user_id' => $request->filled('user_id') ? $request->integer('user_id') : null,
        ];
    }

    /** Boutique filtrée : respecte l'isolation boutique du workspace ; « all » = consolidé si autorisé. */
    protected function storeFilter(Request $request): ?int
    {
        $raw = $request->input('store_id');
        if ($raw === 'all' || $raw === null || $raw === '') {
            return Workspace::canAccessAllStores() ? null : Workspace::store()?->id;
        }
        $id = (int) $raw;
        if (! Workspace::canAccessAllStores() && ! Workspace::accessibleStores()->contains('id', $id)) {
            return Workspace::store()?->id;
        }

        return $id;
    }

    protected function query(array $f): array
    {
        return array_filter(['from' => $f['from']->toDateString(), 'to' => $f['to']->toDateString(), 'store_id' => $f['store_id'] ?? 'all', 'user_id' => $f['user_id']]);
    }

    protected function options(int $companyId): array
    {
        return [
            'stores' => Store::query()->where('company_id', $companyId)->orderBy('name')->get(['id', 'name']),
            'users' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))->orderBy('name')->get(['id', 'name']),
        ];
    }

    public function pilotage(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $f = $this->filters($request);
        $data = $this->reports->pilotage($company->id, $f['from'], $f['to'], $f['store_id']);

        return view('rimal.reports.pilotage', $data + $this->options($company->id) + ['filters' => $f, 'query' => $this->query($f), 'currency' => $company->currency ?? 'MAD']);
    }

    public function show(Request $request, string $type): View
    {
        $this->authorize($type === 'financier' ? 'reports.financial' : 'reports.view');
        $company = Workspace::company();
        $f = $this->filters($request);
        $report = $this->reports->report($type, $company->id, $f['from'], $f['to'], $f['store_id'], $f['user_id']);

        return view('rimal.reports.table', compact('report', 'type') + $this->options($company->id) + ['filters' => $f, 'query' => $this->query($f), 'currency' => $company->currency ?? 'MAD']);
    }

    public function export(Request $request, string $type)
    {
        $this->authorize('reports.export');
        if ($type === 'financier') {
            $this->authorize('reports.financial');
        }
        $company = Workspace::company();
        $f = $this->filters($request);
        $r = $this->reports->report($type, $company->id, $f['from'], $f['to'], $f['store_id'], $f['user_id']);
        $name = 'rapport-'.$type.'-'.$f['from']->format('Ymd').'-'.$f['to']->format('Ymd').'.xls';

        return RimalSpreadsheet::download($name, $r['title'], $r['columns'], $r['rows'], $r['totals']);
    }

    public function exportPilotage(Request $request)
    {
        $this->authorize('reports.export');
        $company = Workspace::company();
        $f = $this->filters($request);
        $d = $this->reports->pilotage($company->id, $f['from'], $f['to'], $f['store_id']);
        $labels = self::METRIC_LABELS;
        $rows = [];
        foreach ($labels as $k => $label) {
            $rows[] = ['metric' => $label, 'current' => (float) $d['current'][$k], 'previous' => (float) $d['previous'][$k], 'variation' => $d['variation'][$k] ?? ''];
        }
        foreach ($d['byStore'] as $s) {
            $rows[] = ['metric' => 'Boutique : '.$s['store'].' — CA TTC', 'current' => (float) $s['revenue'], 'previous' => '', 'variation' => ''];
        }

        return RimalSpreadsheet::download('pilotage-'.$f['from']->format('Ymd').'-'.$f['to']->format('Ymd').'.xls', 'Pilotage',
            ['metric' => 'Indicateur', 'current' => 'Période', 'previous' => 'Période précédente', 'variation' => 'Variation %'], $rows);
    }

    public function print(Request $request, string $type): View
    {
        $this->authorize('reports.print');
        $company = Workspace::company();
        $f = $this->filters($request);
        if ($type === 'pilotage') {
            $d = $this->reports->pilotage($company->id, $f['from'], $f['to'], $f['store_id']);
            $rows = [];
            foreach (self::METRIC_LABELS as $k => $label) {
                $rows[] = ['metric' => $label, 'current' => (float) $d['current'][$k], 'previous' => (float) $d['previous'][$k], 'variation' => isset($d['variation'][$k]) ? $d['variation'][$k].' %' : '—'];
            }
            $report = ['title' => 'Tableau de pilotage', 'columns' => ['metric' => 'Indicateur', 'current' => 'Période', 'previous' => 'Période précédente', 'variation' => 'Variation'], 'rows' => $rows, 'totals' => [], 'money' => ['current', 'previous']];
        } else {
            if ($type === 'financier') {
                $this->authorize('reports.financial');
            }
            $report = $this->reports->report($type, $company->id, $f['from'], $f['to'], $f['store_id'], $f['user_id']);
        }
        $brand = app(BrandingService::class)->forCompany($company);

        return view('rimal.reports.print', compact('report', 'brand', 'company') + ['filters' => $f, 'currency' => $company->currency ?? 'MAD']);
    }

    public function documents(Request $request): View
    {
        $company = Workspace::company();
        $f = $this->filters($request);
        $type = $request->string('type')->toString() ?: null;
        $search = $request->string('q')->toString() ?: null;
        $docs = $this->reports->documents($company->id, $f['from'], $f['to'], $type, $search);

        return view('rimal.documents.index', compact('docs', 'type', 'search') + ['filters' => $f, 'currency' => $company->currency ?? 'MAD']);
    }

    public function deliveryNote(Sale $sale): View
    {
        $this->authorize('sales.print');
        abort_unless($sale->company_id === Workspace::company()?->id, 404);
        $sale->load(['lines.product', 'customer', 'store', 'salesperson']);
        $company = Workspace::company();
        $brand = app(BrandingService::class)->forCompany($company);
        $agent = $sale->delivery_agent_id ? \App\Models\DeliveryAgent::query()->find($sale->delivery_agent_id) : null;

        return view('rimal.documents.delivery-note', compact('sale', 'brand', 'company', 'agent'));
    }

    public const METRIC_LABELS = [
        'revenue' => "Chiffre d'affaires TTC",
        'revenue_ht' => "Chiffre d'affaires HT",
        'count' => 'Nombre de ventes',
        'avg_ticket' => 'Panier moyen',
        'margin' => 'Marge brute estimée',
        'margin_rate' => 'Taux de marge %',
        'purchases' => 'Achats fournisseurs',
        'expenses' => 'Dépenses',
        'collected' => 'Encaissements',
        'treasury' => 'Trésorerie nette',
        'receivable' => 'Encours à encaisser',
    ];
}

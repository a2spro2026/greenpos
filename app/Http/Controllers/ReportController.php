<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports)
    {
    }

    public function dashboard(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->biDashboard($company->id, $filters);

        return view('reports.dashboard', array_merge($data, compact('filters', 'options')));
    }

    public function sales(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->salesReport($company->id, $filters);

        return view('reports.sales', array_merge($data, compact('filters', 'options')));
    }

    public function products(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->productsReport($company->id, $filters);

        return view('reports.products', array_merge($data, compact('filters', 'options')));
    }

    public function customers(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->customersReport($company->id, $filters);

        return view('reports.customers', array_merge($data, compact('filters', 'options')));
    }

    public function payments(Request $request): View
    {
        $this->authorize('reports.financial');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->paymentsReport($company->id, $filters);

        return view('reports.payments', array_merge($data, compact('filters', 'options')));
    }

    public function stock(Request $request): View
    {
        $this->authorize('reports.view');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $options = $this->reports->filterOptions($company->id);
        $data = $this->reports->stockReport($company->id, $filters);

        return view('reports.stock', array_merge($data, compact('filters', 'options')));
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('reports.export');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $type = $request->string('type', 'sales')->toString();

        return match ($type) {
            'sales' => $this->exportSales($company->id, $filters),
            'products' => $this->exportProducts($company->id, $filters),
            'customers' => $this->exportCustomers($company->id, $filters),
            'payments' => $this->exportPayments($company->id, $filters),
            'stock' => $this->exportStock($company->id, $filters),
            default => $this->exportSales($company->id, $filters),
        };
    }

    public function print(Request $request): View
    {
        $this->authorize('reports.print');
        $company = Workspace::company();
        $filters = $this->reports->parseFilters($request);
        $type = $request->string('type', 'sales')->toString();

        $data = match ($type) {
            'sales' => $this->reports->salesReport($company->id, $filters),
            'products' => $this->reports->productsReport($company->id, $filters),
            'customers' => $this->reports->customersReport($company->id, $filters),
            'payments' => $this->reports->paymentsReport($company->id, $filters),
            'stock' => $this->reports->stockReport($company->id, $filters),
            default => $this->reports->biDashboard($company->id, $filters),
        };

        return view('reports.print', array_merge($data, compact('filters', 'type', 'company')));
    }

    protected function exportSales(int $companyId, array $filters): StreamedResponse
    {
        $data = $this->reports->salesReport($companyId, $filters);

        return Response::streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['type', 'reference', 'date', 'client', 'boutique', 'total_ttc', 'statut'], ';');
            foreach ($data['sales'] as $s) {
                fputcsv($out, ['Vente', $s->number, optional($s->sold_at)->format('Y-m-d'), $s->customer?->name ?? 'Passage', $s->store?->name, $s->total_ttc, $s->statusLabel()], ';');
            }
            foreach ($data['posOnly'] as $s) {
                fputcsv($out, ['POS', $s->number, optional($s->completed_at)->format('Y-m-d'), $s->customer?->name ?? 'Passage', $s->store?->name, $s->total_ttc, $s->statusLabel()], ';');
            }
            fclose($out);
        }, 'rapport-ventes-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function exportProducts(int $companyId, array $filters): StreamedResponse
    {
        $data = $this->reports->productsReport($companyId, $filters);

        return Response::streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['produit', 'quantite_vendue', 'chiffre_affaires'], ';');
            foreach ($data['topProducts'] as $p) {
                fputcsv($out, [$p['product_name'], $p['qty'], $p['total']], ';');
            }
            fclose($out);
        }, 'rapport-produits-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function exportCustomers(int $companyId, array $filters): StreamedResponse
    {
        $data = $this->reports->customersReport($companyId, $filters);

        return Response::streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['client', 'ventes', 'chiffre_affaires'], ';');
            foreach ($data['bestCustomers'] as $c) {
                fputcsv($out, [$c['customer']?->name ?? '—', $c['count'], $c['total']], ';');
            }
            fclose($out);
        }, 'rapport-clients-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function exportPayments(int $companyId, array $filters): StreamedResponse
    {
        $data = $this->reports->paymentsReport($companyId, $filters);

        return Response::streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['mode', 'montant', 'nombre'], ';');
            foreach ($data['byMethod'] as $m) {
                fputcsv($out, [$m['label'], $m['total'], $m['count']], ';');
            }
            fclose($out);
        }, 'rapport-paiements-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function exportStock(int $companyId, array $filters): StreamedResponse
    {
        $data = $this->reports->stockReport($companyId, $filters);

        return Response::streamDownload(function () use ($data) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['produit', 'boutique', 'quantite', 'seuil_min', 'statut'], ';');
            foreach ($data['belowThreshold'] as $l) {
                fputcsv($out, [$l->product?->name, $l->store?->name, $l->quantity, $l->min_quantity, $l->statusLabel()], ';');
            }
            fclose($out);
        }, 'rapport-stock-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

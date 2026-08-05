<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\SaleService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SaleController extends Controller
{
    public function __construct(private SaleService $sales)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('sales.view');
        $company = Workspace::company();
        $today = now()->startOfDay();
        $startMonth = now()->startOfMonth();

        $base = Sale::query()->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft']);

        $stats = [
            'revenue' => (float) (clone $base)->sum('total_ttc'),
            'count' => (clone $base)->count(),
            'avg_ticket' => (clone $base)->count() > 0 ? round((float) (clone $base)->avg('total_ttc'), 2) : 0,
            'products_sold' => (int) \App\Models\SaleLine::query()->whereHas('sale', fn ($q) => $q->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft']))->sum('quantity'),
            'active_customers' => (clone $base)->whereNotNull('customer_id')->distinct('customer_id')->count(),
            'month_revenue' => (float) (clone $base)->where('sold_at', '>=', $startMonth)->sum('total_ttc'),
            'prev_month' => (float) Sale::query()->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft'])->whereBetween('sold_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->sum('total_ttc'),
        ];
        $stats['growth'] = $stats['prev_month'] > 0 ? round((($stats['month_revenue'] - $stats['prev_month']) / $stats['prev_month']) * 100, 1) : 0;

        $daily = collect(range(6, 0))->map(function (int $i) use ($company) {
            $day = now()->subDays($i)->startOfDay();
            return [
                'label' => $day->format('D d'),
                'total' => (float) Sale::query()->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft'])->whereDate('sold_at', $day)->sum('total_ttc'),
            ];
        });

        $monthly = collect(range(5, 0))->map(function (int $i) use ($company) {
            $m = now()->subMonths($i)->startOfMonth();
            return [
                'label' => $m->format('M'),
                'total' => (float) Sale::query()->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft'])->whereBetween('sold_at', [$m, (clone $m)->endOfMonth()])->sum('total_ttc'),
            ];
        });

        $topProducts = \App\Models\SaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($company->id)->whereNotIn('status', ['cancelled', 'draft']))
            ->selectRaw('product_id, product_name, sum(quantity) as qty, sum(line_total) as total')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $topCustomers = Sale::query()
            ->forCompany($company->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereNotNull('customer_id')
            ->with('customer')
            ->selectRaw('customer_id, count(*) as cnt, sum(total_ttc) as total')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $recent = Sale::query()
            ->forCompany($company->id)
            ->with(['customer', 'store', 'salesperson'])
            ->latest('sold_at')
            ->limit(8)
            ->get();

        return view('sales.dashboard', compact('stats', 'daily', 'monthly', 'topProducts', 'topCustomers', 'recent'));
    }

    public function index(Request $request): View
    {
        $this->authorize('sales.view');
        $company = Workspace::company();

        $sort = $request->string('sort', 'sold_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['sold_at', 'number', 'total_ttc', 'status', 'created_at'], true)) {
            $sort = 'sold_at';
        }

        $sales = Sale::query()
            ->forCompany($company->id)
            ->with(['customer', 'store', 'salesperson', 'creator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term)->orWhere('company_name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('origin'), fn ($q) => $q->where('origin', $request->string('origin')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sold_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sold_at', '<=', $request->date('to')))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', [
            'sales' => $sales,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'statuses' => Sale::STATUSES,
            'origins' => Sale::ORIGINS,
            'filters' => $request->only(['q', 'status', 'origin', 'store_id', 'from', 'to', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('sales.create');
        return view('sales.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('sales.create');
        $data = $this->validatedSale($request);
        $sale = $this->sales->create($data, $request->input('lines', []));

        if ($request->boolean('confirm')) {
            $this->sales->confirm($sale);
        }

        return redirect()->route('sales.show', $sale)->with('success', $request->boolean('confirm') ? 'Vente confirmée.' : 'Brouillon enregistré.');
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorize('sales.view');
        $this->ensureCompany($sale);
        $sale->load(['lines.product', 'customer', 'store', 'salesperson', 'creator', 'payments.creator', 'returns.returnLines.saleLine', 'returns.creator', 'logs.user', 'posSale', 'quote', 'invoice']);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'products', 'payments', 'returns', 'invoice', 'history', 'documents'], true)) {
            $tab = 'overview';
        }

        return view('sales.show', compact('sale', 'tab'));
    }

    public function edit(Sale $sale): View
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);
        if (! $sale->isEditable()) { abort(403, 'Vente non modifiable.'); }
        $sale->load('lines');
        return view('sales.edit', array_merge($this->formData(), ['sale' => $sale]));
    }

    public function update(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);
        $data = $this->validatedSale($request);
        $this->sales->update($sale, $data, $request->input('lines', []));
        return redirect()->route('sales.show', $sale)->with('success', 'Vente mise à jour.');
    }

    public function destroy(Sale $sale): RedirectResponse
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);
        $this->sales->deleteDraft($sale);
        return redirect()->route('sales.index')->with('success', 'Brouillon supprimé.');
    }

    public function confirm(Sale $sale): RedirectResponse
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);
        $this->sales->confirm($sale);
        return back()->with('success', 'Vente confirmée — stock décrémenté.');
    }

    public function advance(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);
        $this->sales->advanceStatus($sale, $request->string('target')->toString());
        return back()->with('success', 'Statut avancé.');
    }

    public function cancel(Sale $sale): RedirectResponse
    {
        $this->authorize('sales.cancel');
        $this->ensureCompany($sale);
        $this->sales->cancel($sale);
        return back()->with('success', 'Vente annulée.');
    }

    public function returnForm(Sale $sale): View
    {
        $this->authorize('sales.return');
        $this->ensureCompany($sale);
        $sale->load('lines.product');
        return view('sales.return', compact('sale'));
    }

    public function processReturn(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('sales.return');
        $this->ensureCompany($sale);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
            'restock' => ['nullable', 'boolean'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sale_line_id' => ['required', 'exists:sale_lines,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0'],
        ]);

        $this->sales->processReturn(
            $sale,
            $data['lines'],
            $data['reason'],
            $data['notes'] ?? null,
            $request->boolean('restock', true)
        );

        return redirect()->route('sales.show', ['sale' => $sale, 'tab' => 'returns'])->with('success', 'Retour enregistré.');
    }

    public function storePayment(Request $request, Sale $sale): RedirectResponse
    {
        $this->authorize('sales.update');
        $this->ensureCompany($sale);

        $data = $request->validate([
            'method' => ['required', 'in:cash,card,bank_transfer,mobile,check,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->sales->recordPayment($sale, $data);
        return back()->with('success', 'Paiement enregistré.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('sales.export');
        $company = Workspace::company();

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['number', 'date', 'client', 'origin', 'store', 'status', 'ht', 'tax', 'ttc', 'paid', 'returned', 'salesperson', 'currency'], ';');

            Sale::query()->forCompany($company->id)
                ->with(['customer', 'store', 'salesperson'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('sold_at')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $sale) {
                        fputcsv($out, [
                            $sale->number, optional($sale->sold_at)->format('Y-m-d'),
                            $sale->customer?->displayName() ?? 'Passage',
                            $sale->originLabel(), $sale->store?->name, $sale->statusLabel(),
                            $sale->subtotal_ht, $sale->tax_total, $sale->total_ttc,
                            $sale->amount_paid, $sale->amount_returned,
                            $sale->salesperson?->name, $sale->currency,
                        ], ';');
                    }
                });
            fclose($out);
        }, 'ventes-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Sale $sale): View
    {
        $this->authorize('sales.print');
        $this->ensureCompany($sale);
        $sale->load(['lines.product', 'customer', 'store', 'salesperson', 'payments']);
        return view('sales.print', compact('sale'));
    }

    protected function validatedSale(Request $request): array
    {
        $company = Workspace::company();
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'salesperson_id' => ['nullable', 'exists:users,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'sold_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
        Store::query()->whereKey($data['store_id'])->where('company_id', $company->id)->firstOrFail();
        return $data;
    }

    protected function formData(?Request $request = null): array
    {
        $company = Workspace::company();
        return [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'customers' => Customer::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(),
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'sale_price', 'tax_rate']),
            'salespeople' => User::query()->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))->orderBy('name')->get(['id', 'name']),
            'currency' => $company->currency ?? 'MAD',
        ];
    }

    protected function ensureCompany(Sale $sale): void
    {
        if ($sale->company_id !== Workspace::company()?->id) { abort(404); }
    }
}

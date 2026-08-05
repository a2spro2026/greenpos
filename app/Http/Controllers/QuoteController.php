<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Store;
use App\Models\User;
use App\Services\QuoteService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class QuoteController extends Controller
{
    public function __construct(private QuoteService $quotes)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('quotes.view');
        $company = Workspace::company();
        $this->quotes->syncCompanyExpired($company->id);

        $base = Quote::query()->forCompany($company->id);

        $stats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->whereIn('status', ['sent', 'pending'])->count(),
            'accepted' => (clone $base)->where('status', 'accepted')->count(),
            'refused' => (clone $base)->where('status', 'refused')->count(),
            'amount' => (float) (clone $base)->whereNotIn('status', ['draft', 'refused', 'expired'])->sum('total_ttc'),
            'conversion_rate' => $this->quotes->conversionRate($company->id),
        ];

        $monthly = collect(range(5, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M'),
                'total' => (float) Quote::query()
                    ->forCompany($company->id)
                    ->whereNotIn('status', ['draft', 'refused'])
                    ->whereBetween('quoted_at', [$month, (clone $month)->endOfMonth()])
                    ->sum('total_ttc'),
                'count' => Quote::query()
                    ->forCompany($company->id)
                    ->whereBetween('quoted_at', [$month, (clone $month)->endOfMonth()])
                    ->count(),
            ];
        });

        $recent = Quote::query()
            ->forCompany($company->id)
            ->with(['customer', 'store', 'salesperson'])
            ->latest('quoted_at')
            ->limit(8)
            ->get();

        $followUp = Quote::query()
            ->forCompany($company->id)
            ->whereIn('status', ['sent', 'pending'])
            ->whereNotNull('valid_until')
            ->where('valid_until', '<=', now()->addDays(7)->toDateString())
            ->with(['customer', 'salesperson'])
            ->orderBy('valid_until')
            ->limit(6)
            ->get();

        return view('quotes.dashboard', compact('stats', 'monthly', 'recent', 'followUp'));
    }

    public function index(Request $request): View
    {
        $this->authorize('quotes.view');
        $company = Workspace::company();
        $this->quotes->syncCompanyExpired($company->id);

        $sort = $request->string('sort', 'quoted_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['quoted_at', 'valid_until', 'number', 'total_ttc', 'status', 'created_at'], true)) {
            $sort = 'quoted_at';
        }

        $quotes = Quote::query()
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
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('quoted_at', '>=', $request->date('from')))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('quotes.index', [
            'quotes' => $quotes,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'customers' => Customer::query()->forCompany($company->id)->orderBy('name')->limit(200)->get(['id', 'name', 'company_name']),
            'statuses' => Quote::STATUSES,
            'filters' => $request->only(['q', 'status', 'store_id', 'customer_id', 'from', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('quotes.create');

        return view('quotes.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('quotes.create');
        $data = $this->validatedQuote($request);
        $quote = $this->quotes->create($data, $request->input('lines', []), $request->boolean('send'));

        return redirect()->route('quotes.show', $quote)->with('success', $request->boolean('send') ? 'Devis envoyé.' : 'Brouillon enregistré.');
    }

    public function show(Request $request, Quote $quote): View
    {
        $this->authorize('quotes.view');
        $this->ensureCompany($quote);
        $quote->load(['lines.product', 'customer', 'store', 'salesperson', 'creator', 'logs.user', 'convertedInvoice', 'convertedPosSale']);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'products', 'history', 'documents', 'notes'], true)) {
            $tab = 'overview';
        }

        return view('quotes.show', compact('quote', 'tab'));
    }

    public function edit(Quote $quote): View
    {
        $this->authorize('quotes.update');
        $this->ensureCompany($quote);
        if (! $quote->isEditable()) {
            abort(403, 'Devis non modifiable.');
        }
        $quote->load('lines');

        return view('quotes.edit', array_merge($this->formData(), ['quote' => $quote]));
    }

    public function update(Request $request, Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.update');
        $this->ensureCompany($quote);
        $data = $this->validatedQuote($request);
        $this->quotes->update($quote, $data, $request->input('lines', []));

        return redirect()->route('quotes.show', $quote)->with('success', 'Devis mis à jour.');
    }

    public function destroy(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.delete');
        $this->ensureCompany($quote);
        $this->quotes->deleteDraft($quote);

        return redirect()->route('quotes.index')->with('success', 'Brouillon supprimé.');
    }

    public function send(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.send');
        $this->ensureCompany($quote);
        $this->quotes->send($quote);

        return back()->with('success', 'Devis envoyé.');
    }

    public function accept(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.update');
        $this->ensureCompany($quote);
        $this->quotes->accept($quote);

        return back()->with('success', 'Devis accepté.');
    }

    public function refuse(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.update');
        $this->ensureCompany($quote);
        $this->quotes->refuse($quote);

        return back()->with('success', 'Devis refusé.');
    }

    public function duplicate(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.create');
        $this->ensureCompany($quote);
        $copy = $this->quotes->duplicate($quote);

        return redirect()->route('quotes.show', $copy)->with('success', 'Devis dupliqué.');
    }

    public function convertInvoice(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.convert');
        $this->ensureCompany($quote);
        $invoice = $this->quotes->convertToInvoice($quote);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Devis converti en facture '.$invoice->number.'.');
    }

    public function convertSale(Quote $quote): RedirectResponse
    {
        $this->authorize('quotes.convert');
        $this->ensureCompany($quote);

        try {
            $sale = $this->quotes->convertToSale($quote);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('pos.tickets.show', $sale)->with('success', 'Devis converti en vente '.$sale->number.'.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('quotes.export');
        $company = Workspace::company();
        $filename = 'devis-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['number', 'client', 'store', 'date', 'valid_until', 'status', 'total_ttc', 'salesperson', 'currency'], ';');

            Quote::query()
                ->forCompany($company->id)
                ->with(['customer', 'store', 'salesperson'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('quoted_at')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $quote) {
                        fputcsv($out, [
                            $quote->number,
                            $quote->customer?->displayName(),
                            $quote->store?->name,
                            optional($quote->quoted_at)->format('Y-m-d'),
                            optional($quote->valid_until)->format('Y-m-d'),
                            $quote->statusLabel(),
                            $quote->total_ttc,
                            $quote->salesperson?->name,
                            $quote->currency,
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Quote $quote): View
    {
        $this->authorize('quotes.print');
        $this->ensureCompany($quote);
        $quote->load(['lines.product', 'customer', 'store', 'salesperson']);

        return view('quotes.print', compact('quote'));
    }

    public function pdf(Quote $quote): View
    {
        $this->authorize('quotes.print');
        $this->ensureCompany($quote);
        $quote->load(['lines.product', 'customer', 'store', 'salesperson']);

        return view('quotes.pdf', compact('quote'));
    }

    public function publicView(string $token): View
    {
        $quote = Quote::query()->where('public_token', $token)->firstOrFail();
        $quote->load(['lines', 'customer', 'store', 'salesperson']);

        return view('quotes.pdf', compact('quote'));
    }

    protected function validatedQuote(Request $request): array
    {
        $company = Workspace::company();

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'salesperson_id' => ['nullable', 'exists:users,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'quoted_at' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quoted_at'],
            'terms' => ['nullable', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:500'],
        ]);

        Store::query()->whereKey($data['store_id'])->where('company_id', $company->id)->firstOrFail();
        Customer::query()->whereKey($data['customer_id'])->forCompany($company->id)->firstOrFail();

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
            'prefillCustomerId' => $request?->integer('customer_id'),
        ];
    }

    protected function ensureCompany(Quote $quote): void
    {
        if ($quote->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

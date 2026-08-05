<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Store;
use App\Services\InvoiceService;
use App\Support\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('invoices.view');
        $company = Workspace::company();
        $this->invoices->syncCompanyOverdue($company->id);

        $base = Invoice::query()->forCompany($company->id)->invoices();

        $stats = [
            'total' => (clone $base)->whereNotIn('status', ['cancelled'])->count(),
            'paid' => (clone $base)->where('status', 'paid')->count(),
            'pending' => (clone $base)->whereIn('status', ['pending', 'partial'])->count(),
            'overdue' => (clone $base)->where('status', 'expired')->count(),
            'revenue' => (float) (clone $base)->whereNotIn('status', ['cancelled', 'draft'])->sum('total_ttc'),
            'outstanding' => (float) (clone $base)->whereIn('status', ['pending', 'partial', 'expired'])->sum('balance_due'),
        ];

        $monthly = collect(range(5, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M'),
                'total' => (float) Invoice::query()
                    ->forCompany($company->id)
                    ->invoices()
                    ->whereNotIn('status', ['cancelled', 'draft'])
                    ->whereBetween('invoiced_at', [$month, (clone $month)->endOfMonth()])
                    ->sum('total_ttc'),
            ];
        });

        $recent = Invoice::query()
            ->forCompany($company->id)
            ->invoices()
            ->with(['customer', 'store'])
            ->latest('invoiced_at')
            ->limit(8)
            ->get();

        $dueSoon = Invoice::query()
            ->forCompany($company->id)
            ->invoices()
            ->whereIn('status', ['pending', 'partial', 'expired'])
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->with('customer')
            ->orderBy('due_at')
            ->limit(6)
            ->get();

        return view('invoices.dashboard', compact('stats', 'monthly', 'recent', 'dueSoon'));
    }

    public function index(Request $request): View
    {
        $this->authorize('invoices.view');
        $company = Workspace::company();
        $this->invoices->syncCompanyOverdue($company->id);

        $sort = $request->string('sort', 'invoiced_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['invoiced_at', 'due_at', 'number', 'total_ttc', 'balance_due', 'status', 'created_at'], true)) {
            $sort = 'invoiced_at';
        }

        $invoices = Invoice::query()
            ->forCompany($company->id)
            ->invoices()
            ->with(['customer', 'store', 'creator'])
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
            ->when($request->filled('from'), fn ($q) => $q->whereDate('invoiced_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('invoiced_at', '<=', $request->date('to')))
            ->orderBy($sort, $dir)
            ->paginate(20)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'customers' => Customer::query()->forCompany($company->id)->orderBy('name')->limit(200)->get(['id', 'name', 'company_name']),
            'statuses' => Invoice::STATUSES,
            'filters' => $request->only(['q', 'status', 'store_id', 'customer_id', 'from', 'to', 'sort', 'dir']),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('invoices.create');

        return view('invoices.create', $this->formData($request));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('invoices.create');
        $data = $this->validatedInvoice($request);
        $issue = $request->boolean('issue');
        $invoice = $this->invoices->create($data, $request->input('lines', []), $issue);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', $issue ? 'Facture émise.' : 'Brouillon enregistré.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        $this->authorize('invoices.view');
        $this->ensureCompany($invoice);
        $this->invoices->syncOverdue($invoice);

        $invoice->load(['lines.product', 'customer', 'store', 'creator', 'editor', 'payments.creator', 'logs.user', 'creditNotes', 'parentInvoice']);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'products', 'payments', 'history', 'documents', 'notes'], true)) {
            $tab = 'overview';
        }

        return view('invoices.show', compact('invoice', 'tab'));
    }

    public function edit(Invoice $invoice): View
    {
        $this->authorize('invoices.update');
        $this->ensureCompany($invoice);
        if (! $invoice->isEditable()) {
            abort(403, 'Facture non modifiable.');
        }
        $invoice->load('lines');

        return view('invoices.edit', array_merge($this->formData(), ['invoice' => $invoice]));
    }

    public function update(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.update');
        $this->ensureCompany($invoice);
        $data = $this->validatedInvoice($request);
        $this->invoices->update($invoice, $data, $request->input('lines', []));

        if ($request->boolean('issue')) {
            $this->invoices->issue($invoice->fresh());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Facture mise à jour.');
    }

    public function destroy(Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.delete');
        $this->ensureCompany($invoice);
        $this->invoices->deleteDraft($invoice);

        return redirect()->route('invoices.index')->with('success', 'Brouillon supprimé.');
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.approve');
        $this->ensureCompany($invoice);
        $this->invoices->issue($invoice);

        return back()->with('success', 'Facture émise.');
    }

    public function send(Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.send');
        $this->ensureCompany($invoice);
        $this->invoices->send($invoice);

        return back()->with('success', 'Facture marquée comme envoyée.');
    }

    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.cancel');
        $this->ensureCompany($invoice);
        $this->invoices->cancel($invoice);

        return back()->with('success', 'Facture annulée.');
    }

    public function creditNote(Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.create');
        $this->ensureCompany($invoice);
        $credit = $this->invoices->createCreditNote($invoice);

        return redirect()->route('invoices.show', $credit)->with('success', 'Avoir créé.');
    }

    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('invoices.update');
        $this->ensureCompany($invoice);

        $data = $request->validate([
            'method' => ['required', 'in:cash,card,bank_transfer,mobile,check,other'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'paid_at' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->invoices->recordPayment($invoice, $data);

        return back()->with('success', 'Paiement enregistré.');
    }

    public function productsJson(Request $request): JsonResponse
    {
        $this->authorize('invoices.create');
        $company = Workspace::company();
        $q = $request->string('q')->toString();

        $products = Product::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->when($q, fn ($query) => $query->search($q))
            ->orderBy('name')
            ->limit(30)
            ->get(['id', 'name', 'sku', 'sale_price', 'tax_rate']);

        return response()->json(['products' => $products]);
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('invoices.export');
        $company = Workspace::company();
        $filename = 'factures-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['number', 'client', 'store', 'date', 'due', 'status', 'ht', 'tax', 'ttc', 'paid', 'balance', 'currency'], ';');

            Invoice::query()
                ->forCompany($company->id)
                ->invoices()
                ->with(['customer', 'store'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('invoiced_at')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $invoice) {
                        fputcsv($out, [
                            $invoice->number,
                            $invoice->customer?->displayName(),
                            $invoice->store?->name,
                            optional($invoice->invoiced_at)->format('Y-m-d'),
                            optional($invoice->due_at)->format('Y-m-d'),
                            $invoice->statusLabel(),
                            $invoice->subtotal_ht,
                            $invoice->tax_total,
                            $invoice->total_ttc,
                            $invoice->amount_paid,
                            $invoice->balance_due,
                            $invoice->currency,
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('invoices.print');
        $this->ensureCompany($invoice);
        $invoice->load(['lines.product', 'customer', 'store', 'creator', 'payments']);

        return view('invoices.print', compact('invoice'));
    }

    public function pdf(Invoice $invoice): View
    {
        $this->authorize('invoices.pdf');
        $this->ensureCompany($invoice);
        $invoice->load(['lines.product', 'customer', 'store', 'creator', 'payments']);

        return view('invoices.pdf', compact('invoice'));
    }

    public function publicView(string $token): View
    {
        $invoice = Invoice::query()->where('public_token', $token)->firstOrFail();
        $invoice->load(['lines', 'customer', 'store', 'payments']);

        return view('invoices.pdf', compact('invoice'));
    }

    protected function validatedInvoice(Request $request): array
    {
        $company = Workspace::company();

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'invoiced_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date', 'after_or_equal:invoiced_at'],
            'payment_terms' => ['nullable', 'string', 'max:255'],
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
            'currency' => $company->currency ?? 'MAD',
            'prefillCustomerId' => $request?->integer('customer_id'),
        ];
    }

    protected function ensureCompany(Invoice $invoice): void
    {
        if ($invoice->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

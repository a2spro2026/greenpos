<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerDocument;
use App\Models\Store;
use App\Services\CustomerService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customers)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('customers.view');
        $company = Workspace::company();
        $base = Customer::query()->forCompany($company->id);

        $stats = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
            'revenue' => (float) (clone $base)->sum('lifetime_revenue'),
            'balance' => (float) (clone $base)->sum('balance'),
        ];

        $evolution = collect(range(5, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M'),
                'count' => Customer::query()
                    ->forCompany($company->id)
                    ->whereBetween('created_at', [$month, (clone $month)->endOfMonth()])
                    ->count(),
            ];
        });

        $top = Customer::query()
            ->forCompany($company->id)
            ->orderByDesc('lifetime_revenue')
            ->limit(6)
            ->get();

        $recent = Customer::query()->forCompany($company->id)->latest()->limit(8)->get();

        return view('customers.dashboard', compact('stats', 'evolution', 'top', 'recent'));
    }

    public function index(Request $request): View
    {
        $this->authorize('customers.view');
        $company = Workspace::company();

        $columns = $request->session()->get('customer_columns', ['code', 'name', 'company', 'phone', 'email', 'city', 'category', 'last_purchase', 'balance', 'status']);
        if ($request->filled('columns')) {
            $columns = array_values(array_intersect(
                ['code', 'name', 'company', 'phone', 'email', 'city', 'category', 'last_purchase', 'balance', 'status'],
                (array) $request->input('columns')
            ));
            $request->session()->put('customer_columns', $columns);
        }

        $query = Customer::query()
            ->forCompany($company->id)
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'));

        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'code', 'city', 'status', 'created_at', 'lifetime_revenue', 'balance', 'last_purchase_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'name';
        }
        $query->orderBy($sort, $dir);

        return view('customers.index', [
            'customers' => $query->paginate(15)->withQueryString(),
            'columns' => $columns,
            'statuses' => Customer::STATUSES,
            'types' => Customer::TYPES,
            'categories' => Customer::CATEGORIES,
            'filters' => $request->only(['q', 'status', 'type', 'category', 'city', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('customers.create');
        $company = Workspace::company();

        return view('customers.create', [
            'statuses' => Customer::STATUSES,
            'types' => Customer::TYPES,
            'categories' => Customer::CATEGORIES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suggestedCode' => $this->customers->nextCode($company->id),
            'currency' => $company->currency ?? 'MAD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('customers.create');
        $data = $this->validated($request);
        $docs = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                if ($file) {
                    $docs[] = ['file' => $file];
                }
            }
        }

        $customer = $this->customers->create($data, $request->input('contacts', []), $docs);

        return redirect()->route('customers.show', $customer)->with('success', 'Client créé.');
    }

    public function show(Request $request, Customer $customer): View
    {
        $this->authorize('customers.view');
        $this->ensureCompany($customer);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'purchases', 'invoices', 'payments', 'history', 'documents', 'stats'], true)) {
            $tab = 'overview';
        }

        $customer->load(['contacts', 'documents.uploader', 'changeLogs.user', 'creator', 'store']);

        return view('customers.show', compact('customer', 'tab'));
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('customers.update');
        $this->ensureCompany($customer);
        $company = Workspace::company();
        $customer->load('contacts');

        return view('customers.edit', [
            'customer' => $customer,
            'statuses' => Customer::STATUSES,
            'types' => Customer::TYPES,
            'categories' => Customer::CATEGORIES,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'currency' => $customer->currency ?: ($company->currency ?? 'MAD'),
        ]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('customers.update');
        $this->ensureCompany($customer);
        $this->customers->update($customer, $this->validated($request, $customer), $request->input('contacts', []));

        return redirect()->route('customers.show', $customer)->with('success', 'Client mis à jour.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('customers.delete');
        $this->ensureCompany($customer);
        $this->customers->softDelete($customer);

        return redirect()->route('customers.index')->with('success', 'Client archivé.');
    }

    public function storeDocument(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('customers.update');
        $this->ensureCompany($customer);
        $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:64'],
        ]);

        $this->customers->storeDocument(
            $customer,
            $request->file('document'),
            $request->input('title'),
            $request->input('category', 'other')
        );

        return redirect()
            ->route('customers.show', ['customer' => $customer, 'tab' => 'documents'])
            ->with('success', 'Document ajouté.');
    }

    public function destroyDocument(Customer $customer, CustomerDocument $document): RedirectResponse
    {
        $this->authorize('customers.update');
        $this->ensureCompany($customer);
        if ($document->customer_id !== $customer->id) {
            abort(404);
        }
        $this->customers->deleteDocument($document);

        return back()->with('success', 'Document supprimé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('customers.export');
        $company = Workspace::company();
        $filename = 'clients-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code', 'type', 'name', 'company', 'email', 'phone', 'city', 'country', 'category', 'status', 'balance', 'revenue'], ';');

            Customer::query()
                ->forCompany($company->id)
                ->search($request->string('q')->toString())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $customer) {
                        fputcsv($out, [
                            $customer->code,
                            $customer->typeLabel(),
                            $customer->name,
                            $customer->company_name,
                            $customer->email,
                            $customer->phone,
                            $customer->city,
                            $customer->country,
                            $customer->categoryLabel(),
                            $customer->statusLabel(),
                            $customer->balance,
                            $customer->lifetime_revenue,
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Customer $customer): View
    {
        $this->authorize('customers.print');
        $this->ensureCompany($customer);
        $customer->load('contacts');

        return view('customers.print', compact('customer'));
    }

    public function stats(): View
    {
        $this->authorize('customers.stats');
        $company = Workspace::company();

        $customers = Customer::query()->forCompany($company->id)->get();

        $top = $customers->sortByDesc('lifetime_revenue')->take(10)->values();

        $byRevenue = $top->map(fn (Customer $c) => [
            'name' => $c->displayName(),
            'total' => (float) $c->lifetime_revenue,
        ]);

        $evolution = collect(range(11, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M y'),
                'count' => Customer::query()
                    ->forCompany($company->id)
                    ->whereBetween('created_at', [$month, (clone $month)->endOfMonth()])
                    ->count(),
            ];
        });

        $byCountry = $customers
            ->groupBy(fn ($c) => $c->country ?: 'Non renseigné')
            ->map(fn ($g, $name) => ['name' => $name, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values();

        $inactive = $customers->where('status', 'inactive')->values();

        return view('customers.stats', compact('top', 'byRevenue', 'evolution', 'byCountry', 'inactive'));
    }

    protected function validated(Request $request, ?Customer $customer = null): array
    {
        $companyId = Workspace::company()->id;

        return $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('customers', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at'))
                    ->ignore($customer?->id),
            ],
            'type' => ['required', 'in:individual,company'],
            'name' => ['required', 'string', 'max:160'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive'],
            'store_id' => ['nullable', 'exists:stores,id'],
            'email' => ['nullable', 'email', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'mobile' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:160'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'currency' => ['nullable', 'string', 'max:8'],
            'payment_terms' => ['nullable', 'string', 'max:160'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'contacts' => ['nullable', 'array'],
            'contacts.*.name' => ['nullable', 'string', 'max:160'],
            'contacts.*.role' => ['nullable', 'string', 'max:120'],
            'contacts.*.email' => ['nullable', 'email', 'max:160'],
            'contacts.*.phone' => ['nullable', 'string', 'max:40'],
            'contacts.*.mobile' => ['nullable', 'string', 'max:40'],
            'contacts.*.is_primary' => ['nullable', 'boolean'],
            'documents.*' => ['nullable', 'file', 'max:10240'],
        ]);
    }

    protected function ensureCompany(Customer $customer): void
    {
        if ($customer->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierDocument;
use App\Services\SupplierService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $suppliers)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('suppliers.view');
        $company = Workspace::company();
        $base = Supplier::query()->forCompany($company->id);

        $stats = [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 'active')->count(),
            'new' => (clone $base)->where('created_at', '>=', now()->subDays(30))->count(),
            'month_purchases' => (float) PurchaseOrder::query()
                ->forCompany($company->id)
                ->whereNotIn('status', ['cancelled', 'draft'])
                ->where('ordered_at', '>=', now()->startOfMonth())
                ->sum('total_ttc'),
            'risk' => (clone $base)->where('status', 'risk')->count(),
            'inactive' => (clone $base)->where('status', 'inactive')->count(),
        ];

        $evolution = collect(range(5, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M'),
                'count' => Supplier::query()
                    ->forCompany($company->id)
                    ->whereBetween('created_at', [$month, (clone $month)->endOfMonth()])
                    ->count(),
            ];
        });

        $bySpend = PurchaseOrder::query()
            ->forCompany($company->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($group) => [
                'name' => $group->first()->supplier?->name ?: 'Fournisseur',
                'total' => round($group->sum('total_ttc'), 2),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values();

        $recent = Supplier::query()->forCompany($company->id)->latest()->limit(6)->get();

        return view('suppliers.dashboard', compact('stats', 'evolution', 'bySpend', 'recent'));
    }

    public function index(Request $request): View
    {
        $this->authorize('suppliers.view');
        $company = Workspace::company();

        $columns = $request->session()->get('supplier_columns', ['code', 'name', 'company', 'phone', 'email', 'city', 'country', 'orders', 'last_order', 'status']);
        if ($request->filled('columns')) {
            $columns = array_values(array_intersect(
                ['code', 'name', 'company', 'phone', 'email', 'city', 'country', 'orders', 'last_order', 'status'],
                (array) $request->input('columns')
            ));
            $request->session()->put('supplier_columns', $columns);
        }

        $query = Supplier::query()
            ->forCompany($company->id)
            ->withCount(['purchaseOrders as orders_count' => fn ($q) => $q->whereNotIn('status', ['cancelled'])])
            ->withMax(['purchaseOrders as last_order_at' => fn ($q) => $q->whereNotIn('status', ['cancelled'])], 'ordered_at')
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->string('category')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%'.$request->string('city').'%'))
            ->when($request->filled('country'), fn ($q) => $q->where('country', 'like', '%'.$request->string('country').'%'));

        $sort = $request->string('sort', 'name')->toString();
        $dir = $request->string('dir', 'asc')->toString() === 'desc' ? 'desc' : 'asc';
        $allowed = ['name', 'code', 'city', 'status', 'created_at', 'orders_count', 'last_order_at'];
        if (! in_array($sort, $allowed, true)) {
            $sort = 'name';
        }
        $query->orderBy($sort, $dir);

        $suppliers = $query->paginate(15)->withQueryString();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'columns' => $columns,
            'statuses' => Supplier::STATUSES,
            'categories' => Supplier::CATEGORIES,
            'filters' => $request->only(['q', 'status', 'category', 'city', 'country', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('suppliers.create');

        return view('suppliers.create', [
            'statuses' => Supplier::STATUSES,
            'categories' => Supplier::CATEGORIES,
            'suggestedCode' => $this->suppliers->nextCode(Workspace::company()->id),
            'currency' => Workspace::company()->currency ?? 'MAD',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('suppliers.create');
        $data = $this->validated($request);
        $docs = [];
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $i => $file) {
                if (! $file) {
                    continue;
                }
                $docs[] = [
                    'file' => $file,
                    'title' => $request->input('document_titles.'.$i),
                    'category' => $request->input('document_categories.'.$i, 'other'),
                ];
            }
        }

        $supplier = $this->suppliers->create($data, $docs);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur créé.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        $this->authorize('suppliers.view');
        $this->ensureCompany($supplier);

        $tab = $request->string('tab', 'overview')->toString();
        if (! in_array($tab, ['overview', 'orders', 'products', 'stats', 'documents', 'history'], true)) {
            $tab = 'overview';
        }

        $supplier->load(['documents.uploader', 'changeLogs.user', 'creator']);

        $orders = $supplier->purchaseOrders()
            ->with('store')
            ->latest('ordered_at')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        $products = $supplier->products()->with('category')->orderBy('name')->paginate(10, ['*'], 'products_page')->withQueryString();

        $orderStats = [
            'count' => $supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'draft'])->count(),
            'total' => (float) $supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'draft'])->sum('total_ttc'),
            'last' => $supplier->purchaseOrders()->whereNotIn('status', ['cancelled', 'draft'])->latest('ordered_at')->value('ordered_at'),
        ];

        return view('suppliers.show', compact('supplier', 'tab', 'orders', 'products', 'orderStats'));
    }

    public function edit(Supplier $supplier): View
    {
        $this->authorize('suppliers.update');
        $this->ensureCompany($supplier);

        return view('suppliers.edit', [
            'supplier' => $supplier,
            'statuses' => Supplier::STATUSES,
            'categories' => Supplier::CATEGORIES,
            'currency' => $supplier->currency ?: (Workspace::company()->currency ?? 'MAD'),
        ]);
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('suppliers.update');
        $this->ensureCompany($supplier);
        $this->suppliers->update($supplier, $this->validated($request, $supplier));

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $this->authorize('suppliers.delete');
        $this->ensureCompany($supplier);
        $this->suppliers->softDelete($supplier);

        return redirect()->route('suppliers.index')->with('success', 'Fournisseur archivé.');
    }

    public function storeDocument(Request $request, Supplier $supplier): RedirectResponse
    {
        $this->authorize('suppliers.update');
        $this->ensureCompany($supplier);

        $request->validate([
            'document' => ['required', 'file', 'max:10240'],
            'title' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:64'],
        ]);

        $this->suppliers->storeDocument(
            $supplier,
            $request->file('document'),
            $request->input('title'),
            $request->input('category', 'other')
        );

        return redirect()
            ->route('suppliers.show', ['supplier' => $supplier, 'tab' => 'documents'])
            ->with('success', 'Document ajouté.');
    }

    public function destroyDocument(Supplier $supplier, SupplierDocument $document): RedirectResponse
    {
        $this->authorize('suppliers.update');
        $this->ensureCompany($supplier);
        if ($document->supplier_id !== $supplier->id) {
            abort(404);
        }
        $this->suppliers->deleteDocument($document);

        return back()->with('success', 'Document supprimé.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('suppliers.export');
        $company = Workspace::company();
        $filename = 'fournisseurs-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['code', 'name', 'company', 'email', 'phone', 'city', 'country', 'status', 'category'], ';');

            Supplier::query()
                ->forCompany($company->id)
                ->search($request->string('q')->toString())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $supplier) {
                        fputcsv($out, [
                            $supplier->code,
                            $supplier->name,
                            $supplier->company_name,
                            $supplier->email,
                            $supplier->phone,
                            $supplier->city,
                            $supplier->country,
                            $supplier->statusLabel(),
                            $supplier->categoryLabel(),
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(Supplier $supplier): View
    {
        $this->authorize('suppliers.print');
        $this->ensureCompany($supplier);
        $supplier->loadCount(['purchaseOrders', 'products']);

        return view('suppliers.print', compact('supplier'));
    }

    public function stats(): View
    {
        $this->authorize('suppliers.stats');
        $company = Workspace::company();

        $suppliers = Supplier::query()->forCompany($company->id)->get();

        $spend = PurchaseOrder::query()
            ->forCompany($company->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id')
            ->map(fn ($group) => [
                'name' => $group->first()->supplier?->name ?: 'Fournisseur',
                'total' => round($group->sum('total_ttc'), 2),
                'orders' => $group->count(),
            ])
            ->sortByDesc('total')
            ->values();

        $byCountry = $suppliers
            ->groupBy(fn ($s) => $s->country ?: 'Non renseigné')
            ->map(fn ($g, $name) => ['name' => $name, 'count' => $g->count()])
            ->sortByDesc('count')
            ->values();

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

        $ranking = $spend->take(10);

        return view('suppliers.stats', compact('spend', 'byCountry', 'evolution', 'ranking'));
    }

    protected function validated(Request $request, ?Supplier $supplier = null): array
    {
        $companyId = Workspace::company()->id;

        return $request->validate([
            'code' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('suppliers', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId)->whereNull('deleted_at'))
                    ->ignore($supplier?->id),
            ],
            'name' => ['required', 'string', 'max:160'],
            'company_name' => ['nullable', 'string', 'max:160'],
            'category' => ['nullable', 'string', 'max:64'],
            'status' => ['required', 'in:active,inactive,risk'],
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
            'delivery_delay_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'documents.*' => ['nullable', 'file', 'max:10240'],
            'document_titles.*' => ['nullable', 'string', 'max:160'],
            'document_categories.*' => ['nullable', 'string', 'max:64'],
        ]);
    }

    protected function ensureCompany(Supplier $supplier): void
    {
        if ($supplier->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

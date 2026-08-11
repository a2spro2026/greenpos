<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\ProductService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    public function __construct(private ProductService $products)
    {
        $this->authorizeResource(Product::class, 'product');
    }

    public function index(Request $request): View
    {
        $company = Workspace::company();

        $columns = $request->session()->get('products_columns', ['image', 'name', 'sku', 'category', 'price', 'status']);
        if ($request->filled('columns')) {
            $columns = array_values(array_intersect(
                ['image', 'name', 'sku', 'barcode', 'category', 'brand', 'type', 'price', 'purchase_price', 'status', 'updated'],
                (array) $request->input('columns')
            ));
            $request->session()->put('products_columns', $columns);
        }

        $query = Product::query()
            ->forCompany($company->id)
            ->with(['category', 'brand', 'supplier'])
            ->search($request->string('q')->toString())
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')));

        $sort = $request->string('sort', 'updated_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'sku', 'sale_price', 'status', 'updated_at', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'updated_at';
        }
        $query->orderBy($sort, $dir);

        $products = $query->paginate(12)->withQueryString();

        $stats = [
            'total' => Product::query()->forCompany($company->id)->count(),
            'active' => Product::query()->forCompany($company->id)->where('status', 'active')->count(),
            'inactive' => Product::query()->forCompany($company->id)->where('status', 'inactive')->count(),
            'archived' => Product::query()->forCompany($company->id)->where('status', 'archived')->count(),
        ];

        return view('products.index', [
            'products' => $products,
            'stats' => $stats,
            'columns' => $columns,
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'brands' => Brand::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'canViewPurchase' => Workspace::can('products.view_purchase_price'),
            'filters' => $request->only(['q', 'status', 'type', 'category_id', 'brand_id', 'supplier_id', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        return view('products.create', $this->formData());
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['image'], $data['variants'], $data['store_ids']);

        $product = $this->products->create(
            $data,
            $request->file('image'),
            $request->input('variants', []),
            $request->input('store_ids', [])
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit créé avec succès.');
    }

    public function show(Product $product): View
    {
        $this->ensureCompany($product);
        $product->load(['category', 'brand', 'supplier', 'variants', 'images', 'stores', 'changeLogs.user', 'creator', 'editor']);

        return view('products.show', [
            'product' => $product,
            'canViewPurchase' => Workspace::can('products.view_purchase_price'),
        ]);
    }

    public function edit(Product $product): View
    {
        $this->ensureCompany($product);
        $product->load(['variants', 'stores']);

        return view('products.edit', array_merge($this->formData(), [
            'product' => $product,
        ]));
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->ensureCompany($product);
        $data = $request->validated();
        unset($data['image'], $data['variants'], $data['store_ids']);

        $this->products->update(
            $product,
            $data,
            $request->file('image'),
            $request->input('variants', []),
            $request->input('store_ids', [])
        );

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Produit mis à jour.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->ensureCompany($product);
        $this->products->softDelete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Produit déplacé dans la corbeille (suppression logique).');
    }

    public function archive(Product $product): RedirectResponse
    {
        $this->authorize('archive', $product);
        $this->ensureCompany($product);
        $this->products->archive($product);

        return back()->with('success', 'Produit archivé.');
    }

    public function activate(Product $product): RedirectResponse
    {
        $this->authorize('archive', $product);
        $this->ensureCompany($product);
        $this->products->restoreStatus($product, 'active');

        return back()->with('success', 'Produit réactivé.');
    }

    public function duplicate(Product $product): RedirectResponse
    {
        $this->authorize('duplicate', $product);
        $this->ensureCompany($product);
        $copy = $this->products->duplicate($product);

        return redirect()
            ->route('products.edit', $copy)
            ->with('success', 'Produit dupliqué. Complétez la fiche si besoin.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Product::class);
        $company = Workspace::company();
        $canPurchase = Workspace::can('products.view_purchase_price');

        $filename = 'produits-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $canPurchase, $request) {
            $out = fopen('php://output', 'w');
            $headers = ['sku', 'name', 'type', 'barcode', 'unit', 'sale_price', 'tax_rate', 'status', 'category', 'brand'];
            if ($canPurchase) {
                array_splice($headers, 6, 0, ['purchase_price']);
            }
            fputcsv($out, $headers, ';');

            Product::query()
                ->forCompany($company->id)
                ->with(['category', 'brand'])
                ->search($request->string('q')->toString())
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderBy('name')
                ->chunk(200, function ($chunk) use ($out, $canPurchase) {
                    foreach ($chunk as $product) {
                        $row = [
                            $product->sku,
                            $product->name,
                            $product->type,
                            $product->barcode,
                            $product->unit,
                            $product->sale_price,
                            $product->tax_rate,
                            $product->status,
                            $product->category?->name,
                            $product->brand?->name,
                        ];
                        if ($canPurchase) {
                            array_splice($row, 5, 0, [$product->purchase_price]);
                        }
                        fputcsv($out, $row, ';');
                    }
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importForm(): View
    {
        $this->authorize('import', Product::class);

        return view('products.import');
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('import', Product::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $company = Workspace::company();
        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle, 0, ';');
        if (! $header) {
            $header = [];
        }
        $header = array_map(fn ($h) => Str::lower(trim((string) $h)), $header);

        $created = 0;
        $updated = 0;
        $errors = [];
        $line = 1;

        while (($row = fgetcsv($handle, 0, ';')) !== false) {
            $line++;
            if (count(array_filter($row, fn ($v) => $v !== null && $v !== '')) === 0) {
                continue;
            }

            $data = [];
            foreach ($header as $i => $key) {
                $data[$key] = $row[$i] ?? null;
            }

            $name = trim((string) ($data['name'] ?? ''));
            $sku = trim((string) ($data['sku'] ?? ''));

            if ($name === '') {
                $errors[] = "Ligne {$line} : nom manquant.";
                continue;
            }

            try {
                $payload = [
                    'type' => $data['type'] ?? 'physical',
                    'name' => $name,
                    'sku' => $sku ?: null,
                    'barcode' => $data['barcode'] ?? null,
                    'unit' => $data['unit'] ?? 'pce',
                    'sale_price' => (float) ($data['sale_price'] ?? 0),
                    'purchase_price' => (float) ($data['purchase_price'] ?? 0),
                    'tax_rate' => (float) ($data['tax_rate'] ?? 20),
                    'status' => $data['status'] ?? 'active',
                    'track_stock' => true,
                ];

                if (! array_key_exists($payload['type'], Product::TYPES)) {
                    $payload['type'] = 'physical';
                }
                if (! array_key_exists($payload['status'], Product::STATUSES)) {
                    $payload['status'] = 'active';
                }

                $existing = $sku
                    ? Product::query()->forCompany($company->id)->where('sku', $sku)->first()
                    : null;

                if ($existing) {
                    $this->products->update($existing, $payload);
                    $updated++;
                } else {
                    $this->products->create($payload);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = "Ligne {$line} : ".$e->getMessage();
            }
        }

        fclose($handle);

        $message = "Import terminé : {$created} créé(s), {$updated} mis à jour.";
        if ($errors) {
            $message .= ' Erreurs : '.count($errors).'.';
        }

        return redirect()
            ->route('products.index')
            ->with('success', $message)
            ->with('import_errors', array_slice($errors, 0, 20));
    }

    protected function formData(): array
    {
        $company = Workspace::company();

        return [
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'brands' => Brand::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'stores' => $company->stores()->orderBy('name')->get(),
            'types' => Product::TYPES,
            'statuses' => Product::STATUSES,
            'units' => Product::UNITS,
            'canViewPurchase' => Workspace::can('products.view_purchase_price'),
        ];
    }

    protected function ensureCompany(Product $product): void
    {
        if ((int) $product->company_id !== (int) Workspace::company()?->id) {
            abort(redirect()->route('products.index')->with('warning', 'Ce produit n’existe plus.'));
        }
    }
}

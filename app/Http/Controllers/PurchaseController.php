<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();
        $startMonth = now()->startOfMonth();

        $orders = PurchaseOrder::query()->forCompany($company->id);

        $stats = [
            'month_total' => (clone $orders)->where('ordered_at', '>=', $startMonth)->whereNotIn('status', ['cancelled'])->sum('total_ttc'),
            'pending' => (clone $orders)->whereIn('status', ['draft', 'sent'])->count(),
            'confirmed' => (clone $orders)->whereIn('status', ['confirmed', 'partial'])->count(),
            'receipts_today' => \App\Models\PurchaseReceipt::query()->forCompany($company->id)->whereDate('received_at', today())->where('status', 'validated')->count(),
            'suppliers' => Supplier::query()->where('company_id', $company->id)->count(),
            'spend_total' => (clone $orders)->whereNotIn('status', ['cancelled', 'draft'])->sum('total_ttc'),
        ];

        $monthly = collect(range(5, 0))->map(function (int $i) use ($company) {
            $month = now()->subMonths($i)->startOfMonth();

            return [
                'label' => $month->format('M'),
                'total' => (float) PurchaseOrder::query()
                    ->forCompany($company->id)
                    ->whereNotIn('status', ['cancelled'])
                    ->whereBetween('ordered_at', [$month, (clone $month)->endOfMonth()])
                    ->sum('total_ttc'),
            ];
        });

        $bySupplier = PurchaseOrder::query()
            ->forCompany($company->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->with('supplier')
            ->get()
            ->groupBy(fn ($o) => $o->supplier?->name ?: 'Fournisseur')
            ->map(fn ($group, $name) => ['name' => $name, 'total' => round($group->sum('total_ttc'), 2)])
            ->sortByDesc('total')
            ->take(6)
            ->values();

        $recent = PurchaseOrder::query()
            ->forCompany($company->id)
            ->with(['supplier', 'store', 'creator'])
            ->latest()
            ->limit(8)
            ->get();

        return view('purchases.dashboard', compact('stats', 'monthly', 'bySupplier', 'recent'));
    }

    public function index(Request $request): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();

        $orders = PurchaseOrder::query()
            ->forCompany($company->id)
            ->with(['supplier', 'store', 'creator'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->string('q').'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('number', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $term));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('store_id'), fn ($q) => $q->where('store_id', $request->integer('store_id')))
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->integer('supplier_id')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('ordered_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('ordered_at', '<=', $request->date('to')));

        $sort = $request->string('sort', 'ordered_at')->toString();
        $dir = $request->string('dir', 'desc')->toString() === 'asc' ? 'asc' : 'desc';
        if (! in_array($sort, ['ordered_at', 'number', 'total_ttc', 'status', 'created_at'], true)) {
            $sort = 'ordered_at';
        }
        $orders = $orders->orderBy($sort, $dir)->paginate(15)->withQueryString();

        return view('purchases.orders.index', [
            'orders' => $orders,
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'statuses' => PurchaseOrder::STATUSES,
            'filters' => $request->only(['q', 'status', 'store_id', 'supplier_id', 'from', 'to', 'sort', 'dir']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('purchases.create');

        return view('purchases.orders.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('purchases.create');
        $data = $this->validatedOrder($request);
        $order = $this->purchases->createOrder($data, $request->input('lines', []));

        return redirect()->route('purchases.orders.show', $order)->with('success', 'Bon de commande créé.');
    }

    public function show(PurchaseOrder $order): View
    {
        $this->authorize('purchases.view');
        $this->ensureCompany($order);
        $order->load(['lines.product', 'supplier', 'store', 'creator', 'receipts', 'logs.user']);

        return view('purchases.orders.show', compact('order'));
    }

    public function edit(PurchaseOrder $order): View
    {
        $this->authorize('purchases.update');
        $this->ensureCompany($order);
        if (! $order->isEditable()) {
            abort(403, 'Commande non modifiable.');
        }
        $order->load('lines');

        return view('purchases.orders.edit', array_merge($this->formData(), ['order' => $order]));
    }

    public function update(Request $request, PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('purchases.update');
        $this->ensureCompany($order);
        $data = $this->validatedOrder($request);
        $this->purchases->updateOrder($order, $data, $request->input('lines', []));

        return redirect()->route('purchases.orders.show', $order)->with('success', 'Commande mise à jour.');
    }

    public function send(PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('purchases.update');
        $this->ensureCompany($order);
        $this->purchases->send($order);

        return back()->with('success', 'Commande marquée comme envoyée.');
    }

    public function confirm(PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('purchases.update');
        $this->ensureCompany($order);
        $this->purchases->confirm($order);

        return back()->with('success', 'Commande confirmée.');
    }

    public function cancel(PurchaseOrder $order): RedirectResponse
    {
        $this->authorize('purchases.cancel');
        $this->ensureCompany($order);
        $this->purchases->cancel($order);

        return back()->with('success', 'Commande annulée.');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('purchases.export');
        $company = Workspace::company();
        $filename = 'achats-'.now()->format('Ymd-His').'.csv';

        return Response::streamDownload(function () use ($company, $request) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['number', 'supplier', 'store', 'date', 'status', 'total_ttc', 'currency', 'user'], ';');

            PurchaseOrder::query()
                ->forCompany($company->id)
                ->with(['supplier', 'store', 'creator'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
                ->orderByDesc('ordered_at')
                ->chunk(200, function ($chunk) use ($out) {
                    foreach ($chunk as $order) {
                        fputcsv($out, [
                            $order->number,
                            $order->supplier?->name,
                            $order->store?->name,
                            optional($order->ordered_at)->format('Y-m-d'),
                            $order->statusLabel(),
                            $order->total_ttc,
                            $order->currency,
                            $order->creator?->name,
                        ], ';');
                    }
                });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function print(PurchaseOrder $order): View
    {
        $this->authorize('purchases.print');
        $this->ensureCompany($order);
        $order->load(['lines.product', 'supplier', 'store', 'creator']);

        return view('purchases.orders.print', compact('order'));
    }

    protected function validatedOrder(Request $request): array
    {
        $company = Workspace::company();

        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'store_id' => ['required', 'exists:stores,id'],
            'reference' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'ordered_at' => ['nullable', 'date'],
            'expected_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        Store::query()->whereKey($data['store_id'])->where('company_id', $company->id)->firstOrFail();
        Supplier::query()->whereKey($data['supplier_id'])->where('company_id', $company->id)->firstOrFail();

        return $data;
    }

    protected function formData(): array
    {
        $company = Workspace::company();

        return [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'suppliers' => Supplier::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku', 'purchase_price', 'tax_rate']),
            'currency' => $company->currency ?? 'MAD',
        ];
    }

    protected function ensureCompany(PurchaseOrder $order): void
    {
        if ($order->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

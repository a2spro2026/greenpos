<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Store;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Support\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseRequestController extends Controller
{
    public function __construct(private PurchaseService $purchases)
    {
    }

    public function index(): View
    {
        $this->authorize('purchases.view');
        $company = Workspace::company();

        $requests = PurchaseRequest::query()
            ->forCompany($company->id)
            ->with(['store', 'requester'])
            ->latest()
            ->paginate(15);

        return view('purchases.requests.index', compact('requests'));
    }

    public function create(): View
    {
        $this->authorize('purchases.create');
        $company = Workspace::company();

        return view('purchases.requests.create', [
            'stores' => Store::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'products' => Product::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->get(['id', 'name', 'sku']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('purchases.create');
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'store_id' => ['required', 'exists:stores,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'exists:products,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        Store::query()->whereKey($data['store_id'])->where('company_id', Workspace::company()->id)->firstOrFail();
        $purchaseRequest = $this->purchases->createRequest($data, $data['lines']);

        return redirect()->route('purchases.requests.show', $purchaseRequest)->with('success', 'Demande d’achat créée.');
    }

    public function show(PurchaseRequest $purchaseRequest): View
    {
        $this->authorize('purchases.view');
        if ($purchaseRequest->company_id !== Workspace::company()?->id) {
            abort(404);
        }
        $purchaseRequest->load(['lines.product', 'store', 'requester', 'convertedOrder']);
        $suppliers = Supplier::query()->where('company_id', Workspace::company()->id)->orderBy('name')->get();

        return view('purchases.requests.show', [
            'request' => $purchaseRequest,
            'suppliers' => $suppliers,
        ]);
    }

    public function submit(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('purchases.create');
        $this->ensure($purchaseRequest);
        $this->purchases->submitRequest($purchaseRequest);

        return back()->with('success', 'Demande soumise.');
    }

    public function approve(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('purchases.approve');
        $this->ensure($purchaseRequest);
        $this->purchases->approveRequest($purchaseRequest);

        return back()->with('success', 'Demande approuvée.');
    }

    public function convert(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        $this->authorize('purchases.create');
        $this->ensure($purchaseRequest);
        $data = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
        ]);
        Supplier::query()->whereKey($data['supplier_id'])->where('company_id', Workspace::company()->id)->firstOrFail();

        if ($purchaseRequest->status === 'submitted') {
            $this->purchases->approveRequest($purchaseRequest);
        }

        $order = $this->purchases->convertRequestToOrder($purchaseRequest, (int) $data['supplier_id']);

        return redirect()->route('purchases.orders.show', $order)->with('success', 'Demande convertie en bon de commande.');
    }

    protected function ensure(PurchaseRequest $purchaseRequest): void
    {
        if ($purchaseRequest->company_id !== Workspace::company()?->id) {
            abort(404);
        }
    }
}

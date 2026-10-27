<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryPlatform;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSession;
use App\Models\Product;
use App\Services\CustomListService;
use App\Services\PosService;
use App\Support\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(private PosService $pos, private CustomListService $lists)
    {
    }

    public function dashboard(): View
    {
        $this->authorize('pos.view');
        $company = Workspace::company();
        $today = now()->startOfDay();

        $salesToday = PosSale::query()
            ->forCompany($company->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $today);

        $stats = [
            'sales_total' => (float) (clone $salesToday)->sum('total_ttc'),
            'tickets' => (clone $salesToday)->count(),
            'avg_ticket' => (clone $salesToday)->count() > 0
                ? round((float) (clone $salesToday)->avg('total_ttc'), 2)
                : 0,
            'open_session' => $this->pos->currentOpenSession()?->number,
        ];

        $topCashier = PosSale::query()
            ->forCompany($company->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', $today)
            ->selectRaw('cashier_id, count(*) as tickets, sum(total_ttc) as total')
            ->groupBy('cashier_id')
            ->orderByDesc('total')
            ->with('cashier')
            ->first();

        $topProducts = \App\Models\PosSaleLine::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($company->id)->where('status', 'completed')->where('completed_at', '>=', $today))
            ->selectRaw('product_id, product_name, sum(quantity) as qty, sum(line_total) as total')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('qty')
            ->limit(6)
            ->get();

        $paymentMix = PosPayment::query()
            ->whereHas('sale', fn ($q) => $q->forCompany($company->id)->where('status', 'completed')->where('completed_at', '>=', $today))
            ->selectRaw('method, sum(amount) as total')
            ->groupBy('method')
            ->get()
            ->map(fn ($row) => [
                'method' => PosPayment::METHODS[$row->method] ?? $row->method,
                'total' => (float) $row->total,
            ]);

        $hourly = collect(range(0, 11))->map(function (int $i) use ($company) {
            $hour = now()->startOfDay()->addHours(8 + $i);

            return [
                'label' => $hour->format('H\h'),
                'total' => (float) PosSale::query()
                    ->forCompany($company->id)
                    ->where('status', 'completed')
                    ->whereBetween('completed_at', [$hour, (clone $hour)->addHour()])
                    ->sum('total_ttc'),
            ];
        });

        $recent = PosSale::query()
            ->forCompany($company->id)
            ->where('status', 'completed')
            ->with(['cashier', 'customer'])
            ->latest('completed_at')
            ->limit(8)
            ->get();

        return view('pos.dashboard', compact('stats', 'topCashier', 'topProducts', 'paymentMix', 'hourly', 'recent'));
    }

    public function terminal(): View
    {
        $this->authorize('pos.sell');
        $company = Workspace::company();
        $session = $this->pos->currentOpenSession();

        $categories = Category::query()->where('company_id', $company->id)->orderBy('name')->get();
        $customers = Customer::query()->forCompany($company->id)->where('status', 'active')->orderBy('name')->limit(100)->get(['id', 'name', 'company_name', 'code']);
        $favorites = Product::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->when(
                \Illuminate\Support\Facades\Schema::hasColumn('products', 'is_favorite'),
                fn ($q) => $q->orderByDesc('is_favorite')->orderByDesc('updated_at'),
                fn ($q) => $q->orderByDesc('updated_at')
            )
            ->limit(8)
            ->get(['id', 'name', 'sale_price', 'sku', 'tax_rate']);

        $held = PosSale::query()
            ->forCompany($company->id)
            ->where('status', 'held')
            ->where('store_id', Workspace::store()?->id)
            ->latest('held_at')
            ->limit(10)
            ->get();

        $catalog = $this->pos->catalog(null, null, 48);

        $employees = \App\Models\Employee::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->limit(80)
            ->get(['id', 'first_name', 'last_name', 'code']);

        $deliveryAgents = \App\Models\DeliveryAgent::query()
            ->forCompany($company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name', 'type', 'commission_percent', 'commission_fixed']);

        $serviceModes = \App\Models\Sale::SERVICE_MODES;
        $posSettings = app(\App\Services\SettingService::class)->getGroup('pos', $company);
        $deliveryPlatforms = DeliveryPlatform::query()->forCompany($company->id)
            ->where('is_active', true)->where('kind', 'external')->orderBy('name')
            ->get(['id', 'name', 'commission_type', 'commission_value']);
        $categoriesPosition = in_array($posSettings['categories_position'] ?? 'bottom', ['bottom', 'left'], true)
            ? ($posSettings['categories_position'] ?? 'bottom')
            : 'bottom';
        $paymentMethods = PosPayment::METHODS;
        $instrumentMethods = PosPayment::INSTRUMENT_METHODS;
        $tables = \App\Models\PosTable::query()
            ->forCompany($company->id)
            ->where(fn ($q) => $q->whereNull('store_id')->orWhere('store_id', Workspace::store()?->id))
            ->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'group_name', 'status']);

        return view('pos.terminal', compact(
            'session', 'categories', 'customers', 'favorites', 'held', 'catalog',
            'employees', 'deliveryAgents', 'serviceModes', 'deliveryPlatforms',
            'categoriesPosition', 'paymentMethods', 'instrumentMethods', 'tables'
        ));
    }

    public function offlineSnapshot(): JsonResponse
    {
        $this->authorize('pos.sell');
        $company = Workspace::company();
        $this->lists->ensureDefaults($company);

        return response()->json([
            'saved_at' => now()->toIso8601String(),
            'limits' => [
                'guest_login' => false,
                'reason' => 'La session Blade reste requise. Le catalogue et la file de ventes tiennent dans IndexedDB, pas un mode invité hors ligne.',
            ],
            'products' => $this->pos->catalog(null, null, 500),
            'categories' => Category::query()->where('company_id', $company->id)->orderBy('name')->get(['id', 'name']),
            'payment_modes' => $this->lists->forType($company->id, 'mode_de_paiement'),
            'service_modes' => $this->lists->forType($company->id, 'mode_de_service'),
            'tickets' => $this->lists->forType($company->id, 'tickets_predefinis'),
            'discounts' => $this->lists->forType($company->id, 'remises'),
            'taxes' => $this->lists->forType($company->id, 'taxes'),
        ]);
    }

    public function offlineSync(Request $request): JsonResponse
    {
        $this->authorize('pos.sell');
        $sales = $request->validate([
            'sales' => ['required', 'array', 'min:1', 'max:50'],
            'sales.*.client_uuid' => ['required', 'uuid'],
            'sales.*.items' => ['required', 'array', 'min:1'],
            'sales.*.items.*.product_id' => ['required', 'integer'],
            'sales.*.items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'sales.*.items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'sales.*.items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sales.*.items.*.option_variant_ids' => ['nullable', 'array'],
            'sales.*.payments' => ['required', 'array', 'min:1'],
            'sales.*.customer_id' => ['nullable', 'integer'],
            'sales.*.notes' => ['nullable', 'string', 'max:500'],
            'sales.*.service_mode_list_id' => ['nullable', 'integer'],
            'sales.*.ticket_name' => ['nullable', 'string', 'max:255'],
            'sales.*.delivery_platform_id' => ['nullable', 'integer'],
        ])['sales'];

        $results = [];
        foreach ($sales as $payload) {
            try {
                $sale = $this->pos->completeSale(
                    $payload['items'],
                    $payload['payments'],
                    $payload['customer_id'] ?? null,
                    $payload['notes'] ?? null,
                    null,
                    [
                        'client_uuid' => $payload['client_uuid'],
                        'service_mode_list_id' => $payload['service_mode_list_id'] ?? null,
                        'ticket_name' => $payload['ticket_name'] ?? null,
                        'delivery_platform_id' => $payload['delivery_platform_id'] ?? null,
                        'appointment_at' => $payload['appointment_at'] ?? null,
                        'pickup_date' => $payload['pickup_date'] ?? null,
                        'delivery_address' => $payload['delivery_address'] ?? null,
                        'ticket_group' => $payload['ticket_group'] ?? null,
                        'predefined_ticket_id' => $payload['predefined_ticket_id'] ?? null,
                    ]
                );
                $results[] = ['client_uuid' => $payload['client_uuid'], 'ok' => true, 'number' => $sale->number, 'sale_id' => $sale->id];
            } catch (\Illuminate\Validation\ValidationException $e) {
                $results[] = [
                    'client_uuid' => $payload['client_uuid'],
                    'ok' => false,
                    'message' => collect($e->errors())->flatten()->first(),
                ];
            }
        }

        return response()->json(['results' => $results]);
    }

    public function catalog(Request $request): JsonResponse
    {
        $this->authorize('pos.sell');

        return response()->json([
            'products' => $this->pos->catalog(
                $request->string('q')->toString() ?: null,
                $request->filled('category_id') ? $request->integer('category_id') : null,
                80
            ),
        ]);
    }

    public function checkout(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('pos.sell');

        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.option_variant_ids' => ['nullable', 'array'],
            'items.*.option_variant_ids.*' => ['integer'],
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'in:'.implode(',', array_keys(PosPayment::METHODS))],
            'payments.*.reference' => ['nullable', 'string', 'max:120'],
            'payments.*.due_date' => ['nullable', 'date'],
            'payments.*.custom_list_id' => ['nullable', 'integer'],
            'payments.*.transaction_number' => ['nullable', 'string', 'max:120'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:120'],
            'payments.*.issue_date' => ['nullable', 'date'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.tendered' => ['nullable', 'numeric', 'min:0'],
            'payments.*.transaction_number' => ['nullable', 'string', 'max:120'],
            'payments.*.piece_number' => ['nullable', 'string', 'max:120'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:120'],
            'payments.*.transfer_mode' => ['nullable', 'string', 'max:64'],
            'payments.*.issue_date' => ['nullable', 'date'],
            'payments.*.due_date' => ['nullable', 'date'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'held_sale_id' => ['nullable', 'exists:pos_sales,id'],
            'service_mode' => ['nullable', 'in:dine_in,takeaway,delivery'],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'delivery_agent_id' => ['nullable', 'exists:delivery_agents,id'],
            'ticket_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ticket_name' => ['nullable', 'string', 'max:120'],
            'pos_table_id' => ['nullable', 'integer'],
            'service_mode_list_id' => ['nullable', 'integer'],
            'predefined_ticket_id' => ['nullable', 'integer'],
            'ticket_group' => ['nullable', 'string', 'max:255'],
            'delivery_platform_id' => ['nullable', 'integer'],
            'appointment_at' => ['nullable', 'date'],
            'pickup_date' => ['nullable', 'date'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'client_uuid' => ['nullable', 'uuid'],
        ]);

        try {
            $sale = $this->pos->completeSale(
                $data['items'],
                $data['payments'],
                $data['customer_id'] ?? null,
                $data['notes'] ?? null,
                null,
                [
                    'service_mode' => $data['service_mode'] ?? null,
                    'employee_id' => $data['employee_id'] ?? null,
                    'delivery_agent_id' => $data['delivery_agent_id'] ?? null,
                    'ticket_discount_percent' => $data['ticket_discount_percent'] ?? 0,
                    'ticket_name' => $data['ticket_name'] ?? null,
                    'pos_table_id' => $data['pos_table_id'] ?? null,
                    'service_mode_list_id' => $data['service_mode_list_id'] ?? null,
                    'predefined_ticket_id' => $data['predefined_ticket_id'] ?? null,
                    'ticket_group' => $data['ticket_group'] ?? null,
                    'delivery_platform_id' => $data['delivery_platform_id'] ?? null,
                    'appointment_at' => $data['appointment_at'] ?? null,
                    'pickup_date' => $data['pickup_date'] ?? null,
                    'delivery_address' => $data['delivery_address'] ?? null,
                    'client_uuid' => $data['client_uuid'] ?? null,
                ]
            );

            if (! empty($data['held_sale_id'])) {
                $held = PosSale::query()
                    ->forCompany(Workspace::company()->id)
                    ->whereKey($data['held_sale_id'])
                    ->where('status', 'held')
                    ->first();
                if ($held) {
                    $this->pos->cancelSale($held);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'sale_id' => $sale->id,
                'number' => $sale->number,
                'total' => $sale->total_ttc,
                'receipt_url' => route('pos.tickets.show', $sale),
                'kitchen_url' => in_array($sale->service_mode, ['dine_in', 'takeaway', 'delivery'], true)
                    ? route('pos.tickets.kitchen', $sale)
                    : null,
            ]);
        }

        return redirect()
            ->route('pos.tickets.show', $sale)
            ->with('success', 'Vente '.$sale->number.' enregistrée.');
    }

    public function hold(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('pos.hold');
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.option_variant_ids' => ['nullable', 'array'],
            'items.*.option_variant_ids.*' => ['integer'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'ticket_name' => ['nullable', 'string', 'max:120'],
            'ticket_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'service_mode' => ['nullable', 'in:dine_in,takeaway,delivery'],
            'pos_table_id' => ['nullable', 'integer'],
            'held_sale_id' => ['nullable', 'integer'],
        ]);

        try {
            $sale = $this->pos->holdSale($data['items'], $data['customer_id'] ?? null, $data['notes'] ?? null, [
                'ticket_name' => $data['ticket_name'] ?? null,
                'ticket_discount_percent' => $data['ticket_discount_percent'] ?? 0,
                'service_mode' => $data['service_mode'] ?? null,
                'pos_table_id' => $data['pos_table_id'] ?? null,
                'held_sale_id' => $data['held_sale_id'] ?? null,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
            }
            throw $e;
        }

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'sale_id' => $sale->id, 'number' => $sale->number]);
        }

        return redirect()->route('pos.terminal')->with('success', 'Vente suspendue ('.$sale->number.').');
    }

    public function resume(PosSale $sale): JsonResponse
    {
        $this->authorize('pos.hold');
        if ($sale->company_id !== Workspace::company()?->id || $sale->status !== 'held') {
            abort(404);
        }

        return response()->json([
            'payload' => $sale->held_payload,
            'number' => $sale->number,
            'ticket_name' => $sale->ticket_name,
            'pos_table_id' => $sale->pos_table_id,
            'ticket_discount_percent' => (float) $sale->ticket_discount_percent,
            'sale_id' => $sale->id,
        ]);
    }
}

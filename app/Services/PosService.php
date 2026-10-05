<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomList;
use App\Models\OptionVariant;
use App\Models\PosPayment;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\PosSession;
use App\Models\Product;
use App\Models\StockLevel;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PosService
{
    public function __construct(
        private StockService $stock,
        private SalePaymentWorkflowService $workflow,
    ) {
    }

    public function currentOpenSession(?int $storeId = null): ?PosSession
    {
        $company = Workspace::company();
        $storeId = $storeId ?: Workspace::store()?->id;

        return PosSession::query()
            ->forCompany($company->id)
            ->where('store_id', $storeId)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    public function openSession(float $openingFloat = 0, ?string $notes = null, ?int $storeId = null): PosSession
    {
        $company = Workspace::company();
        $storeId = $storeId ?: Workspace::store()?->id;
        if (! $storeId) {
            throw ValidationException::withMessages(['store_id' => 'Aucune boutique active.']);
        }

        if ($this->currentOpenSession($storeId)) {
            throw ValidationException::withMessages(['session' => 'Une caisse est déjà ouverte sur cette boutique.']);
        }

        $seq = PosSession::query()->forCompany($company->id)->count() + 1;

        return PosSession::query()->create([
            'company_id' => $company->id,
            'store_id' => $storeId,
            'opened_by' => Workspace::user()?->id,
            'number' => 'CAISSE-'.now()->format('Ymd').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT),
            'status' => 'open',
            'opening_float' => $openingFloat,
            'opening_notes' => $notes,
            'opened_at' => now(),
        ]);
    }

    public function closeSession(PosSession $session, float $countedCash, ?string $notes = null): PosSession
    {
        if (! $session->isOpen()) {
            throw ValidationException::withMessages(['session' => 'Cette caisse est déjà clôturée.']);
        }

        $cashSales = (float) $session->sales()
            ->where('status', 'completed')
            ->with('payments')
            ->get()
            ->sum(function (PosSale $sale) {
                return $sale->payments
                    ->filter(fn ($payment) => $payment->method === 'cash' && $this->workflow->countsAsSettled($payment))
                    ->sum('amount');
            });

        $expected = (float) $session->opening_float + $cashSales;
        $diff = $countedCash - $expected;

        $completed = $session->sales()->where('status', 'completed');
        $session->update([
            'status' => 'closed',
            'closed_by' => Workspace::user()?->id,
            'closing_counted' => $countedCash,
            'expected_cash' => round($expected, 2),
            'cash_difference' => round($diff, 2),
            'total_sales' => (float) (clone $completed)->sum('total_ttc'),
            'tickets_count' => (clone $completed)->count(),
            'closing_notes' => $notes,
            'closed_at' => now(),
        ]);

        return $session->fresh();
    }

    public function completeSale(array $items, array $payments, ?int $customerId = null, ?string $notes = null, ?int $sessionId = null, array $context = []): PosSale
    {
        $company = Workspace::company();
        $store = Workspace::store();
        if (! $store) {
            throw ValidationException::withMessages(['store_id' => 'Aucune boutique active.']);
        }

        $session = $sessionId
            ? PosSession::query()->forCompany($company->id)->whereKey($sessionId)->first()
            : $this->currentOpenSession($store->id);

        if (! $session || ! $session->isOpen()) {
            throw ValidationException::withMessages(['session' => 'Ouvrez une caisse avant de vendre.']);
        }

        if (count($items) === 0) {
            throw ValidationException::withMessages(['items' => 'Le panier est vide.']);
        }

        return DB::transaction(function () use ($company, $store, $session, $items, $payments, $customerId, $notes, $context) {
            if (! empty($context['client_uuid'])) {
                $existing = PosSale::query()->where('client_uuid', $context['client_uuid'])->first();
                if ($existing) {
                    return $existing->fresh(['lines', 'payments', 'customer', 'cashier']);
                }
            }

            $computed = $this->computeLines($company->id, $items);
            $resolved = [];
            foreach ($payments as $payment) {
                $row = $this->workflow->resolve($company->id, $payment);
                if ($row['amount'] <= 0) {
                    continue;
                }
                $resolved[] = $row;
            }
            $this->workflow->assertCovers($computed['total_ttc'], $resolved);

            $serviceList = null;
            if (! empty($context['service_mode_list_id'])) {
                $serviceList = CustomList::query()
                    ->forCompany($company->id)
                    ->where('type', 'mode_de_service')
                    ->whereKey($context['service_mode_list_id'])
                    ->first();
            }
            if ($serviceList && $serviceList->meta('requires_delivery_agent') && empty($context['delivery_platform_id'])) {
                throw ValidationException::withMessages(['delivery_platform_id' => 'Ce mode de service exige un livreur ou une plateforme.']);
            }

            $seq = PosSale::query()->forCompany($company->id)->count() + 1;
            $sale = PosSale::query()->create([
                'company_id' => $company->id,
                'store_id' => $store->id,
                'pos_session_id' => $session->id,
                'customer_id' => $customerId,
                'cashier_id' => Workspace::user()?->id,
                'number' => 'TK-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'client_uuid' => $context['client_uuid'] ?? null,
                'ticket_name' => $context['ticket_name'] ?? null,
                'ticket_group' => $context['ticket_group'] ?? null,
                'service_mode' => $serviceList?->meta('operational_mode') ?: ($context['service_mode'] ?? null),
                'service_mode_list_id' => $serviceList?->id,
                'predefined_ticket_id' => $context['predefined_ticket_id'] ?? null,
                'delivery_platform_id' => $context['delivery_platform_id'] ?? null,
                'appointment_at' => $context['appointment_at'] ?? null,
                'pickup_date' => $context['pickup_date'] ?? null,
                'delivery_address' => $context['delivery_address'] ?? null,
                'status' => 'completed',
                'subtotal_ht' => $computed['subtotal_ht'],
                'tax_total' => $computed['tax_total'],
                'discount_total' => $computed['discount_total'],
                'total_ttc' => $computed['total_ttc'],
                'currency' => $company->currency ?? 'MAD',
                'notes' => $notes,
                'completed_at' => now(),
            ]);

            foreach ($computed['lines'] as $i => $line) {
                PosSaleLine::query()->create([
                    'pos_sale_id' => $sale->id,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'sku' => $line['sku'],
                    'options_payload' => $line['options_payload'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'],
                    'tax_rate' => $line['tax_rate'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $i,
                ]);

                $product = Product::query()->find($line['product_id']);
                if ($product?->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $store->id,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $line['quantity'],
                        'reference' => $sale->number,
                        'comment' => 'Vente POS '.$sale->number,
                        'moved_at' => now(),
                    ]);
                }
            }

            foreach ($resolved as $payment) {
                $created = PosPayment::query()->create([
                    'pos_sale_id' => $sale->id,
                    'method' => $payment['method'],
                    'amount' => $payment['amount'],
                    'tendered' => $payment['tendered'],
                    'change_amount' => $payment['change_amount'],
                    'reference' => $payment['reference'],
                    'custom_list_id' => $payment['custom_list_id'],
                    'is_deferred' => $payment['is_deferred'],
                    'transfer_mode' => $payment['transfer_mode'],
                    'transaction_number' => $payment['transaction_number'],
                    'piece_number' => $payment['piece_number'],
                    'bank_name' => $payment['bank_name'],
                    'issue_date' => $payment['issue_date'],
                    'due_date' => $payment['due_date'],
                    'confirmed_at' => $payment['confirmed_at'],
                    'collection_status' => $payment['collection_status'],
                    'received_amount' => $payment['received_amount'],
                    'scheduled_for' => $payment['scheduled_for'],
                ]);
                $this->workflow->recordHistory(
                    $company->id,
                    $payment['is_deferred'] ? 'scheduled' : 'confirmed',
                    $payment['amount'],
                    null,
                    $created->id,
                    null,
                    $payment['scheduled_for']
                );
            }

            $this->workflow->recalculatePos($sale->fresh());

            if ($customerId) {
                $customer = Customer::query()->forCompany($company->id)->whereKey($customerId)->first();
                if ($customer) {
                    $customer->update([
                        'lifetime_revenue' => (float) $customer->lifetime_revenue + $computed['total_ttc'],
                        'last_purchase_at' => now(),
                    ]);
                }
            }

            $session->update([
                'total_sales' => (float) $session->sales()->where('status', 'completed')->sum('total_ttc'),
                'tickets_count' => $session->sales()->where('status', 'completed')->count(),
            ]);

            return $sale->fresh(['lines', 'payments', 'customer', 'cashier']);
        });
    }

    public function holdSale(array $items, ?int $customerId = null, ?string $notes = null): PosSale
    {
        $company = Workspace::company();
        $store = Workspace::store();
        $session = $this->currentOpenSession($store?->id);
        if (! $session) {
            throw ValidationException::withMessages(['session' => 'Ouvrez une caisse avant de suspendre.']);
        }
        if (count($items) === 0) {
            throw ValidationException::withMessages(['items' => 'Panier vide.']);
        }

        $seq = PosSale::query()->forCompany($company->id)->count() + 1;
        $computed = $this->computeLines($company->id, $items);

        return PosSale::query()->create([
            'company_id' => $company->id,
            'store_id' => $store->id,
            'pos_session_id' => $session->id,
            'customer_id' => $customerId,
            'cashier_id' => Workspace::user()?->id,
            'number' => 'HOLD-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'status' => 'held',
            'subtotal_ht' => $computed['subtotal_ht'],
            'tax_total' => $computed['tax_total'],
            'discount_total' => $computed['discount_total'],
            'total_ttc' => $computed['total_ttc'],
            'currency' => $company->currency ?? 'MAD',
            'notes' => $notes,
            'held_payload' => [
                'items' => $items,
                'customer_id' => $customerId,
                'notes' => $notes,
            ],
            'held_at' => now(),
        ]);
    }

    public function cancelSale(PosSale $sale): PosSale
    {
        if ($sale->status === 'cancelled') {
            return $sale;
        }
        if ($sale->status === 'completed') {
            throw ValidationException::withMessages(['sale' => 'Annulation d’un ticket validé : utilisez un avoir (V2). Pour la démo, seuls les tickets suspendus sont annulables.']);
        }

        $sale->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return $sale;
    }

    /**
     * Soft cancel completed sale and restock (manager permission).
     */
    public function voidCompletedSale(PosSale $sale): PosSale
    {
        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['sale' => 'Seul un ticket validé peut être annulé avec restock.']);
        }

        return DB::transaction(function () use ($sale) {
            $sale->load('lines');
            foreach ($sale->lines as $line) {
                $product = $line->product;
                if ($product?->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $sale->store_id,
                        'product_id' => $line->product_id,
                        'type' => 'in',
                        'quantity' => $line->quantity,
                        'reference' => 'VOID-'.$sale->number,
                        'comment' => 'Annulation ticket '.$sale->number,
                        'moved_at' => now(),
                    ]);
                }
            }

            if ($sale->customer_id) {
                $customer = Customer::query()->find($sale->customer_id);
                if ($customer) {
                    $customer->update([
                        'lifetime_revenue' => max(0, (float) $customer->lifetime_revenue - (float) $sale->total_ttc),
                    ]);
                }
            }

            $sale->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $sale->fresh(['lines', 'payments']);
        });
    }

    public function catalog(?string $q = null, ?int $categoryId = null, int $limit = 60): array
    {
        $company = Workspace::company();
        $storeId = Workspace::store()?->id;

        $products = Product::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->whereIn('type', ['physical', 'service', 'pack', 'digital'])
            ->when($categoryId, fn ($q2) => $q2->where('category_id', $categoryId))
            ->when($q, function ($query) use ($q) {
                $like = '%'.$q.'%';
                $query->where(function ($inner) use ($like, $q) {
                    $inner->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', $q);
                });
            })
            ->with(['category', 'options.variants'])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $levels = $storeId
            ? StockLevel::query()
                ->where('store_id', $storeId)
                ->whereIn('product_id', $products->pluck('id'))
                ->get()
                ->keyBy('product_id')
            : collect();

        return $products->map(function (Product $product) use ($levels) {
            $qty = $levels->get($product->id)?->quantity;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'sale_price' => (float) $product->sale_price,
                'tax_rate' => (float) $product->tax_rate,
                'track_stock' => (bool) $product->track_stock,
                'stock_qty' => $qty !== null ? (float) $qty : null,
                'category_id' => $product->category_id,
                'category' => $product->category?->name,
                'image' => $product->imageUrl(),
                'options' => $product->options->map(fn ($option) => [
                    'id' => $option->id,
                    'name' => $option->name,
                    'selection_mode' => $option->selection_mode,
                    'is_required' => (bool) $option->is_required,
                    'variants' => $option->variants->where('is_active', true)->map(fn ($variant) => [
                        'id' => $variant->id,
                        'name' => $variant->name,
                        'extra_price' => (float) $variant->extra_price,
                    ])->values(),
                ])->values(),
            ];
        })->all();
    }

    protected function computeLines(int $companyId, array $items): array
    {
        $lines = [];
        $subtotal = 0;
        $taxTotal = 0;
        $discountTotal = 0;

        foreach ($items as $item) {
            $product = Product::query()->forCompany($companyId)->whereKey($item['product_id'])->first();
            if (! $product) {
                throw ValidationException::withMessages(['items' => 'Produit introuvable.']);
            }
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            [$optionExtra, $optionPayload] = $this->optionExtras($product, $item['option_variant_ids'] ?? []);
            $basePrice = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->sale_price;
            $price = $basePrice + $optionExtra;
            $discount = (float) ($item['discount_percent'] ?? 0);
            $tax = isset($item['tax_rate']) ? (float) $item['tax_rate'] : (float) $product->tax_rate;

            $gross = $qty * $price;
            $discAmount = $gross * ($discount / 100);
            $net = $gross - $discAmount;
            $taxAmount = $net * ($tax / 100);

            $optionNames = collect($optionPayload)->pluck('name')->filter()->implode(', ');
            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $optionNames !== '' ? $product->name.' ('.$optionNames.')' : $product->name,
                'sku' => $product->sku,
                'options_payload' => $optionPayload,
                'quantity' => $qty,
                'unit_price' => $price,
                'discount_percent' => $discount,
                'tax_rate' => $tax,
                'line_subtotal' => round($net, 2),
                'line_tax' => round($taxAmount, 2),
                'line_total' => round($net + $taxAmount, 2),
            ];

            $subtotal += $net;
            $taxTotal += $taxAmount;
            $discountTotal += $discAmount;
        }

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['items' => 'Aucunees invalides.']);
        }

        return [
            'lines' => $lines,
            'subtotal_ht' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total_ttc' => round($subtotal + $taxTotal, 2),
        ];
    }

    /**
     * @param  array<int, mixed>  $variantIds
     * @return array{0: float, 1: list<array<string, mixed>>}
     */
    protected function optionExtras(Product $product, array $variantIds): array
    {
        $ids = array_values(array_filter(array_map('intval', $variantIds)));
        if ($ids === []) {
            return [0.0, []];
        }

        $attached = $product->options()->pluck('product_options.id');
        $variants = OptionVariant::query()->with('option')->whereIn('id', $ids)->get();
        $payload = [];
        $extra = 0.0;
        $byOption = [];

        foreach ($variants as $variant) {
            if (! $variant->option || (int) $variant->option->company_id !== (int) $product->company_id) {
                throw ValidationException::withMessages(['items' => 'Option inconnue.']);
            }
            if (! $attached->contains($variant->product_option_id)) {
                throw ValidationException::withMessages(['items' => 'Cette option n\'est pas liée au produit.']);
            }
            if (! $variant->is_active || ! $variant->option->is_active) {
                throw ValidationException::withMessages(['items' => 'Option inactive.']);
            }
            $byOption[$variant->product_option_id][] = $variant;
        }

        foreach ($byOption as $optionId => $chosen) {
            $option = $chosen[0]->option;
            if ($option->selection_mode === 'fixed' && count($chosen) > 1) {
                throw ValidationException::withMessages(['items' => $option->name.' n\'accepte qu\'un seul choix.']);
            }
            foreach ($chosen as $variant) {
                $extra += (float) $variant->extra_price;
                $payload[] = [
                    'option_id' => $optionId,
                    'option' => $option->name,
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'extra_price' => (float) $variant->extra_price,
                ];
            }
        }

        return [round($extra, 2), $payload];
    }
}

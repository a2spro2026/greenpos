<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderLog;
use App\Models\PurchaseReceipt;
use App\Models\PurchaseReceiptLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\Supplier;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(private StockService $stock)
    {
    }

    public function nextOrderNumber(int $companyId): string
    {
        $seq = PurchaseOrder::query()->forCompany($companyId)->count() + 1;

        return 'BC-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextReceiptNumber(int $companyId): string
    {
        $seq = PurchaseReceipt::query()->forCompany($companyId)->count() + 1;

        return 'BR-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextRequestNumber(int $companyId): string
    {
        $seq = PurchaseRequest::query()->forCompany($companyId)->count() + 1;

        return 'DA-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function createOrder(array $data, array $lines): PurchaseOrder
    {
        $company = Workspace::company();
        $this->assertSupplier($company->id, (int) $data['supplier_id']);

        return DB::transaction(function () use ($company, $data, $lines) {
            $order = PurchaseOrder::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'number' => $data['number'] ?? $this->nextOrderNumber($company->id),
                'reference' => $data['reference'] ?? null,
                'status' => 'draft',
                'currency' => $data['currency'] ?? ($company->currency ?? 'MAD'),
                'ordered_at' => $data['ordered_at'] ?? now()->toDateString(),
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncLines($order, $lines);
            $this->recalculate($order);
            $this->log($order, 'created', 'Bon de commande créé.');

            return $order->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function updateOrder(PurchaseOrder $order, array $data, array $lines): PurchaseOrder
    {
        if (! $order->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons sont modifiables.']);
        }

        $this->assertSupplier($order->company_id, (int) $data['supplier_id']);

        return DB::transaction(function () use ($order, $data, $lines) {
            $order->update([
                'store_id' => $data['store_id'],
                'supplier_id' => $data['supplier_id'],
                'updated_by' => Workspace::user()?->id,
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? $order->currency,
                'ordered_at' => $data['ordered_at'] ?? $order->ordered_at,
                'expected_at' => $data['expected_at'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $order->lines()->delete();
            $this->syncLines($order, $lines);
            $this->recalculate($order);
            $this->log($order, 'updated', 'Bon de commande modifié.');

            return $order->fresh(['lines.product', 'supplier', 'store']);
        });
    }

    public function send(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons peuvent être envoyés.']);
        }
        if ($order->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne.']);
        }

        $order->update([
            'status' => 'sent',
            'sent_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($order, 'sent', 'Commande envoyée au fournisseur.');

        return $order;
    }

    public function confirm(PurchaseOrder $order): PurchaseOrder
    {
        if (! in_array($order->status, ['sent', 'draft'], true)) {
            throw ValidationException::withMessages(['status' => 'Statut non confirmable.']);
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'sent_at' => $order->sent_at ?? now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($order, 'confirmed', 'Commande confirmée.');

        return $order;
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->status, ['received', 'cancelled'], true)) {
            throw ValidationException::withMessages(['status' => 'Cette commande ne peut plus être annulée.']);
        }
        if ((float) $order->lines()->sum('received_quantity') > 0) {
            throw ValidationException::withMessages(['status' => 'Impossible d’annuler : des quantités ont déjà été reçues.']);
        }

        $order->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($order, 'cancelled', 'Commande annulée.');

        return $order;
    }

    public function createReceipt(PurchaseOrder $order, array $quantities, ?string $notes = null, ?string $receivedAt = null): PurchaseReceipt
    {
        if (! $order->canReceive()) {
            throw ValidationException::withMessages(['status' => 'Cette commande n’est pas réceptionnable.']);
        }

        $order->load('lines');
        $hasQty = false;

        return DB::transaction(function () use ($order, $quantities, $notes, $receivedAt, &$hasQty) {
            $receipt = PurchaseReceipt::query()->create([
                'company_id' => $order->company_id,
                'purchase_order_id' => $order->id,
                'store_id' => $order->store_id,
                'received_by' => Workspace::user()?->id,
                'number' => $this->nextReceiptNumber($order->company_id),
                'status' => 'draft',
                'received_at' => $receivedAt ?? now()->toDateString(),
                'notes' => $notes,
            ]);

            foreach ($order->lines as $line) {
                $qty = (float) ($quantities[$line->id] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $remaining = $line->remainingQuantity();
                if ($qty > $remaining + 0.0001) {
                    throw ValidationException::withMessages([
                        'quantities' => "Quantité trop élevée pour {$line->product?->name} (reste {$remaining}).",
                    ]);
                }
                $hasQty = true;
                PurchaseReceiptLine::query()->create([
                    'purchase_receipt_id' => $receipt->id,
                    'purchase_order_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'ordered_qty' => $line->quantity,
                    'previously_received' => $line->received_quantity,
                    'quantity' => $qty,
                ]);
            }

            if (! $hasQty) {
                throw ValidationException::withMessages(['quantities' => 'Indiquez au moins une quantité reçue.']);
            }

            $this->log($order, 'receipt_created', 'Réception '.$receipt->number.' créée (brouillon).', $receipt->id);

            return $receipt->fresh(['lines.product', 'order']);
        });
    }

    public function validateReceipt(PurchaseReceipt $receipt): PurchaseReceipt
    {
        if ($receipt->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Réception déjà traitée.']);
        }

        return DB::transaction(function () use ($receipt) {
            $receipt->load(['lines.product', 'order.lines']);
            $order = $receipt->order;

            foreach ($receipt->lines as $rLine) {
                $qty = (float) $rLine->quantity;
                if ($qty <= 0) {
                    continue;
                }

                $orderLine = $order->lines->firstWhere('id', $rLine->purchase_order_line_id);
                if (! $orderLine) {
                    continue;
                }

                $product = $rLine->product;
                if ($product && $product->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $receipt->store_id,
                        'product_id' => $rLine->product_id,
                        'type' => 'in',
                        'quantity' => $qty,
                        'unit_cost' => $orderLine->unit_price,
                        'reference' => $receipt->number,
                        'comment' => 'Réception achat '.$order->number,
                        'moved_at' => $receipt->received_at?->endOfDay() ?? now(),
                    ]);
                }

                $orderLine->update([
                    'received_quantity' => (float) $orderLine->received_quantity + $qty,
                ]);
            }

            $receipt->update([
                'status' => 'validated',
                'validated_at' => now(),
            ]);

            $order->refresh()->load('lines');
            $remaining = $order->remainingQuantity();
            $receivedAny = $order->lines->sum(fn ($l) => (float) $l->received_quantity) > 0;

            $newStatus = $remaining <= 0.0001 ? 'received' : ($receivedAny ? 'partial' : $order->status);
            $order->update([
                'status' => $newStatus,
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($order, 'receipt_validated', 'Réception '.$receipt->number.' validée — stock mis à jour.', $receipt->id);

            return $receipt->fresh(['lines.product', 'order']);
        });
    }

    public function createRequest(array $data, array $lines): PurchaseRequest
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $lines) {
            $request = PurchaseRequest::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'requested_by' => Workspace::user()?->id,
                'number' => $this->nextRequestNumber($company->id),
                'status' => 'draft',
                'title' => $data['title'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lines as $line) {
                if (empty($line['product_id']) || (float) ($line['quantity'] ?? 0) <= 0) {
                    continue;
                }
                PurchaseRequestLine::query()->create([
                    'purchase_request_id' => $request->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'notes' => $line['notes'] ?? null,
                ]);
            }

            return $request->fresh(['lines.product', 'store']);
        });
    }

    public function submitRequest(PurchaseRequest $request): PurchaseRequest
    {
        if ($request->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Demande déjà traitée.']);
        }
        $request->update(['status' => 'submitted']);

        return $request;
    }

    public function approveRequest(PurchaseRequest $request): PurchaseRequest
    {
        if (! in_array($request->status, ['draft', 'submitted'], true)) {
            throw ValidationException::withMessages(['status' => 'Demande non approuvable.']);
        }
        $request->update(['status' => 'approved']);

        return $request;
    }

    public function convertRequestToOrder(PurchaseRequest $request, int $supplierId): PurchaseOrder
    {
        if (! in_array($request->status, ['approved', 'submitted'], true)) {
            throw ValidationException::withMessages(['status' => 'Approuvez la demande avant conversion.']);
        }

        $request->load('lines.product');
        $lines = $request->lines->map(fn ($l) => [
            'product_id' => $l->product_id,
            'quantity' => $l->quantity,
            'unit_price' => $l->product?->purchase_price ?? 0,
            'discount_percent' => 0,
            'tax_rate' => $l->product?->tax_rate ?? 20,
        ])->all();

        $order = $this->createOrder([
            'store_id' => $request->store_id,
            'supplier_id' => $supplierId,
            'ordered_at' => now()->toDateString(),
            'notes' => 'Converti depuis '.$request->number.($request->notes ? ' — '.$request->notes : ''),
            'reference' => $request->number,
        ], $lines);

        $request->update([
            'status' => 'converted',
            'converted_order_id' => $order->id,
        ]);

        $this->log($order, 'from_request', 'Créé depuis la demande '.$request->number);

        return $order;
    }

    protected function syncLines(PurchaseOrder $order, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $row) {
            if (empty($row['product_id']) || (float) ($row['quantity'] ?? 0) <= 0) {
                continue;
            }

            $product = Product::query()->forCompany($order->company_id)->whereKey($row['product_id'])->firstOrFail();
            $qty = (float) $row['quantity'];
            $price = (float) ($row['unit_price'] ?? $product->purchase_price ?? 0);
            $discount = (float) ($row['discount_percent'] ?? 0);
            $tax = (float) ($row['tax_rate'] ?? $product->tax_rate ?? 20);

            $gross = $qty * $price;
            $discountAmount = $gross * ($discount / 100);
            $subtotal = $gross - $discountAmount;
            $taxAmount = $subtotal * ($tax / 100);

            PurchaseOrderLine::query()->create([
                'purchase_order_id' => $order->id,
                'product_id' => $product->id,
                'description' => $product->name,
                'quantity' => $qty,
                'received_quantity' => 0,
                'unit_price' => $price,
                'discount_percent' => $discount,
                'tax_rate' => $tax,
                'line_subtotal' => round($subtotal, 2),
                'line_tax' => round($taxAmount, 2),
                'line_total' => round($subtotal + $taxAmount, 2),
                'sort_order' => $sort++,
            ]);
        }

        if ($order->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins un article.']);
        }
    }

    public function recalculate(PurchaseOrder $order): void
    {
        $order->load('lines');
        $subtotal = $order->lines->sum(fn ($l) => (float) $l->line_subtotal);
        $tax = $order->lines->sum(fn ($l) => (float) $l->line_tax);
        $discount = $order->lines->sum(function ($l) {
            $gross = (float) $l->quantity * (float) $l->unit_price;

            return $gross * ((float) $l->discount_percent / 100);
        });

        $order->update([
            'subtotal_ht' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'discount_total' => round($discount, 2),
            'total_ttc' => round($subtotal + $tax, 2),
        ]);
    }

    protected function assertSupplier(int $companyId, int $supplierId): void
    {
        $ok = Supplier::query()->where('company_id', $companyId)->whereKey($supplierId)->exists();
        if (! $ok) {
            throw ValidationException::withMessages(['supplier_id' => 'Fournisseur invalide.']);
        }
    }

    public function log(PurchaseOrder $order, string $action, string $message, ?int $receiptId = null, array $meta = []): void
    {
        PurchaseOrderLog::query()->create([
            'company_id' => $order->company_id,
            'purchase_order_id' => $order->id,
            'purchase_receipt_id' => $receiptId,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta ?: null,
        ]);
    }
}

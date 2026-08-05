<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleLog;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnLine;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function __construct(private StockService $stock)
    {
    }

    public function nextNumber(int $companyId): string
    {
        $seq = Sale::query()->forCompany($companyId)->count() + 1;

        return 'VTE-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function nextReturnNumber(int $companyId): string
    {
        $seq = SaleReturn::query()->whereHas('sale', fn ($q) => $q->where('company_id', $companyId))->count() + 1;

        return 'RET-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $lines): Sale
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $lines) {
            $computed = $this->computeLines($company->id, $lines);

            $sale = Sale::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? Workspace::user()?->id,
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'number' => $data['number'] ?? $this->nextNumber($company->id),
                'origin' => $data['origin'] ?? 'manual',
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? ($company->currency ?? 'MAD'),
                'sold_at' => $data['sold_at'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'subtotal_ht' => $computed['subtotal_ht'],
                'tax_total' => $computed['tax_total'],
                'discount_total' => $computed['discount_total'],
                'total_ttc' => $computed['total_ttc'],
                'pos_sale_id' => $data['pos_sale_id'] ?? null,
                'quote_id' => $data['quote_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
            ]);

            foreach ($computed['lines'] as $i => $line) {
                SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'sku' => $line['sku'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'],
                    'tax_rate' => $line['tax_rate'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $i,
                ]);
            }

            $this->log($sale, 'created', 'Vente créée en brouillon.');

            return $sale->fresh(['lines.product', 'customer', 'store']);
        });
    }

    public function update(Sale $sale, array $data, array $lines): Sale
    {
        if (! $sale->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons sont modifiables.']);
        }

        $company = Workspace::company();

        return DB::transaction(function () use ($company, $sale, $data, $lines) {
            $computed = $this->computeLines($company->id, $lines);

            $sale->update([
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'salesperson_id' => $data['salesperson_id'] ?? $sale->salesperson_id,
                'updated_by' => Workspace::user()?->id,
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? $sale->currency,
                'sold_at' => $data['sold_at'] ?? $sale->sold_at,
                'notes' => $data['notes'] ?? null,
                'subtotal_ht' => $computed['subtotal_ht'],
                'tax_total' => $computed['tax_total'],
                'discount_total' => $computed['discount_total'],
                'total_ttc' => $computed['total_ttc'],
            ]);

            $sale->lines()->delete();
            foreach ($computed['lines'] as $i => $line) {
                SaleLine::query()->create([
                    'sale_id' => $sale->id,
                    'product_id' => $line['product_id'],
                    'product_name' => $line['product_name'],
                    'sku' => $line['sku'],
                    'description' => $line['description'] ?? null,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'],
                    'tax_rate' => $line['tax_rate'],
                    'line_subtotal' => $line['line_subtotal'],
                    'line_tax' => $line['line_tax'],
                    'line_total' => $line['line_total'],
                    'sort_order' => $i,
                ]);
            }

            $this->log($sale, 'updated', 'Vente modifiée.');

            return $sale->fresh(['lines.product', 'customer', 'store']);
        });
    }

    public function confirm(Sale $sale): Sale
    {
        if ($sale->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Seul un brouillon peut être confirmé.']);
        }

        return DB::transaction(function () use ($sale) {
            $sale->load('lines');

            foreach ($sale->lines as $line) {
                $product = Product::query()->find($line->product_id);
                if ($product?->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $sale->store_id,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $line->quantity,
                        'reference' => $sale->number,
                        'comment' => 'Vente '.$sale->number,
                        'moved_at' => now(),
                    ]);
                }
            }

            if ($sale->customer_id) {
                $customer = Customer::query()->find($sale->customer_id);
                $customer?->update([
                    'lifetime_revenue' => (float) $customer->lifetime_revenue + (float) $sale->total_ttc,
                    'last_purchase_at' => now(),
                ]);
            }

            $sale->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($sale, 'confirmed', 'Vente confirmée — stock décrémenté.');

            return $sale;
        });
    }

    public function advanceStatus(Sale $sale, string $target): Sale
    {
        $allowed = [
            'confirmed' => ['preparing', 'delivered', 'completed'],
            'preparing' => ['delivered', 'completed'],
            'delivered' => ['completed'],
        ];

        if (! in_array($target, $allowed[$sale->status] ?? [], true)) {
            throw ValidationException::withMessages(['status' => 'Transition impossible.']);
        }

        $sale->update([
            'status' => $target,
            $target.'_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($sale, $target, 'Statut avancé vers '.Sale::STATUSES[$target].'.');

        return $sale;
    }

    public function cancel(Sale $sale): Sale
    {
        if (in_array($sale->status, ['cancelled', 'returned'], true)) {
            throw ValidationException::withMessages(['status' => "Impossible d'annuler."]);
        }

        return DB::transaction(function () use ($sale) {
            if (in_array($sale->status, ['confirmed', 'preparing', 'delivered', 'completed'], true)) {
                $sale->load('lines');
                foreach ($sale->lines as $line) {
                    $product = Product::query()->find($line->product_id);
                    if ($product?->track_stock) {
                        $this->stock->applyMovement([
                            'store_id' => $sale->store_id,
                            'product_id' => $product->id,
                            'type' => 'in',
                            'quantity' => $line->quantity,
                            'reference' => 'CANCEL-'.$sale->number,
                            'comment' => 'Annulation vente '.$sale->number,
                            'moved_at' => now(),
                        ]);
                    }
                }
            }

            $sale->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'updated_by' => Workspace::user()?->id,
            ]);
            $this->log($sale, 'cancelled', 'Vente annulée — stock restitué.');

            return $sale;
        });
    }

    public function processReturn(Sale $sale, array $returnLines, string $reason, ?string $notes = null, bool $restock = true): SaleReturn
    {
        if (! in_array($sale->status, ['confirmed', 'delivered', 'completed'], true)) {
            throw ValidationException::withMessages(['status' => 'Retour impossible pour ce statut.']);
        }

        $sale->load('lines');

        return DB::transaction(function () use ($sale, $returnLines, $reason, $notes, $restock) {
            $isTotal = true;
            $totalRefund = 0;
            $returnNumber = $this->nextReturnNumber($sale->company_id);

            $return = SaleReturn::query()->create([
                'sale_id' => $sale->id,
                'created_by' => Workspace::user()?->id,
                'number' => $returnNumber,
                'type' => 'partial',
                'reason' => $reason,
                'notes' => $notes,
                'restock' => $restock,
                'returned_at' => now(),
            ]);

            foreach ($returnLines as $rl) {
                $line = $sale->lines->firstWhere('id', $rl['sale_line_id']);
                if (! $line) {
                    continue;
                }

                $qty = min((float) ($rl['quantity'] ?? 0), $line->returnableQuantity());
                if ($qty <= 0) {
                    continue;
                }

                $unitRefund = (float) $line->line_total / max(1, (float) $line->quantity);
                $lineRefund = round($qty * $unitRefund, 2);
                $totalRefund += $lineRefund;

                SaleReturnLine::query()->create([
                    'sale_return_id' => $return->id,
                    'sale_line_id' => $line->id,
                    'product_id' => $line->product_id,
                    'quantity' => $qty,
                    'unit_refund' => round($unitRefund, 2),
                    'line_refund' => $lineRefund,
                ]);

                $line->update(['returned_quantity' => (float) $line->returned_quantity + $qty]);

                if ($restock && $line->product_id) {
                    $product = Product::query()->find($line->product_id);
                    if ($product?->track_stock) {
                        $this->stock->applyMovement([
                            'store_id' => $sale->store_id,
                            'product_id' => $product->id,
                            'type' => 'in',
                            'quantity' => $qty,
                            'reference' => $returnNumber,
                            'comment' => 'Retour vente '.$sale->number,
                            'moved_at' => now(),
                        ]);
                    }
                }

                if ($line->returnableQuantity() > 0.001) {
                    $isTotal = false;
                }
            }

            foreach ($sale->lines as $line) {
                if ($line->returnableQuantity() > 0.001) {
                    $isTotal = false;
                }
            }

            $return->update([
                'type' => $isTotal ? 'total' : 'partial',
                'total_returned' => round($totalRefund, 2),
            ]);

            $sale->update([
                'amount_returned' => round((float) $sale->amount_returned + $totalRefund, 2),
                'status' => $isTotal ? 'returned' : $sale->status,
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($sale, 'return', 'Retour '.$returnNumber.' ('.$return->type.') — '.number_format($totalRefund, 2, ',', ' ').' MAD.', [
                'return_id' => $return->id,
                'restock' => $restock,
            ]);

            return $return->fresh(['returnLines.saleLine', 'sale']);
        });
    }

    public function recordPayment(Sale $sale, array $data): SalePayment
    {
        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0 || $amount > $sale->balanceDue() + 0.01) {
            throw ValidationException::withMessages(['amount' => 'Montant invalide ou supérieur au solde.']);
        }

        return DB::transaction(function () use ($sale, $data, $amount) {
            $payment = SalePayment::query()->create([
                'sale_id' => $sale->id,
                'created_by' => Workspace::user()?->id,
                'method' => $data['method'] ?? 'cash',
                'amount' => $amount,
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $sale->update([
                'amount_paid' => round((float) $sale->amount_paid + $amount, 2),
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($sale, 'payment', 'Paiement '.number_format($amount, 2, ',', ' ').' MAD.', [
                'payment_id' => $payment->id,
                'method' => $payment->method,
            ]);

            return $payment;
        });
    }

    public function deleteDraft(Sale $sale): void
    {
        if ($sale->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons peuvent être supprimés.']);
        }
        $sale->delete();
    }

    protected function computeLines(int $companyId, array $items): array
    {
        $lines = [];
        $subtotal = $taxTotal = $discountTotal = 0;

        foreach ($items as $item) {
            $product = Product::query()->forCompany($companyId)->whereKey($item['product_id'])->first();
            if (! $product) {
                throw ValidationException::withMessages(['lines' => 'Produit introuvable.']);
            }
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) { continue; }
            $price = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->sale_price;
            $discount = (float) ($item['discount_percent'] ?? 0);
            $tax = isset($item['tax_rate']) ? (float) $item['tax_rate'] : (float) $product->tax_rate;

            $gross = $qty * $price;
            $discAmount = $gross * ($discount / 100);
            $net = $gross - $discAmount;
            $taxAmount = $net * ($tax / 100);

            $lines[] = [
                'product_id' => $product->id, 'product_name' => $product->name, 'sku' => $product->sku,
                'description' => $item['description'] ?? null, 'quantity' => $qty, 'unit_price' => $price,
                'discount_percent' => $discount, 'tax_rate' => $tax,
                'line_subtotal' => round($net, 2), 'line_tax' => round($taxAmount, 2), 'line_total' => round($net + $taxAmount, 2),
            ];
            $subtotal += $net; $taxTotal += $taxAmount; $discountTotal += $discAmount;
        }

        if (! $lines) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne.']);
        }

        return [
            'lines' => $lines,
            'subtotal_ht' => round($subtotal, 2), 'tax_total' => round($taxTotal, 2),
            'discount_total' => round($discountTotal, 2), 'total_ttc' => round($subtotal + $taxTotal, 2),
        ];
    }

    protected function log(Sale $sale, string $action, string $message, ?array $meta = null): void
    {
        SaleLog::query()->create([
            'sale_id' => $sale->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}

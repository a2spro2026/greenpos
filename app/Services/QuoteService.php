<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PosSale;
use App\Models\Product;
use App\Models\Quote;
use App\Models\QuoteLine;
use App\Models\QuoteLog;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteService
{
    public function __construct(
        private InvoiceService $invoices,
        private PosService $pos
    ) {
    }

    public function nextNumber(int $companyId): string
    {
        $seq = Quote::query()->forCompany($companyId)->count() + 1;

        return 'DEV-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $lines, bool $send = false): Quote
    {
        $company = Workspace::company();
        $this->assertCustomer($company->id, (int) $data['customer_id']);

        return DB::transaction(function () use ($company, $data, $lines, $send) {
            $quote = Quote::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'],
                'salesperson_id' => $data['salesperson_id'] ?? Workspace::user()?->id,
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'number' => $data['number'] ?? $this->nextNumber($company->id),
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? ($company->currency ?? 'MAD'),
                'quoted_at' => $data['quoted_at'] ?? now()->toDateString(),
                'valid_until' => $data['valid_until'] ?? null,
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $this->syncLines($quote, $lines);
            $this->recalculate($quote);
            $this->log($quote, 'created', 'Devis créé en brouillon.');

            if ($send) {
                return $this->send($quote);
            }

            return $quote->fresh(['lines.product', 'customer', 'store', 'salesperson']);
        });
    }

    public function update(Quote $quote, array $data, array $lines): Quote
    {
        if (! $quote->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Ce devis n’est plus modifiable.']);
        }

        $this->assertCustomer($quote->company_id, (int) $data['customer_id']);

        return DB::transaction(function () use ($quote, $data, $lines) {
            $quote->update([
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'],
                'salesperson_id' => $data['salesperson_id'] ?? $quote->salesperson_id,
                'updated_by' => Workspace::user()?->id,
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? $quote->currency,
                'quoted_at' => $data['quoted_at'] ?? $quote->quoted_at,
                'valid_until' => $data['valid_until'] ?? null,
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $quote->lines()->delete();
            $this->syncLines($quote, $lines);
            $this->recalculate($quote);
            $this->log($quote, 'updated', 'Devis modifié.');

            return $quote->fresh(['lines.product', 'customer', 'store', 'salesperson']);
        });
    }

    public function send(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['draft', 'pending'], true)) {
            throw ValidationException::withMessages(['status' => 'Ce devis ne peut pas être envoyé.']);
        }
        if ($quote->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne.']);
        }

        $quote->update([
            'status' => 'sent',
            'sent_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($quote, 'sent', 'Devis envoyé au client (e-mail à brancher).', [
            'email' => $quote->customer?->email,
        ]);

        return $quote;
    }

    public function markPending(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'draft'], true)) {
            throw ValidationException::withMessages(['status' => 'Statut non modifiable.']);
        }

        $quote->update(['status' => 'pending', 'updated_by' => Workspace::user()?->id]);
        $this->log($quote, 'pending', 'Devis marqué en attente de réponse.');

        return $quote;
    }

    public function accept(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'pending'], true)) {
            throw ValidationException::withMessages(['status' => 'Ce devis ne peut pas être accepté.']);
        }

        $quote->update([
            'status' => 'accepted',
            'accepted_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($quote, 'accepted', 'Devis accepté par le client.');

        return $quote;
    }

    public function refuse(Quote $quote): Quote
    {
        if (! in_array($quote->status, ['sent', 'pending', 'accepted'], true)) {
            throw ValidationException::withMessages(['status' => 'Ce devis ne peut pas être refusé.']);
        }

        $quote->update([
            'status' => 'refused',
            'refused_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($quote, 'refused', 'Devis refusé.');

        return $quote;
    }

    public function duplicate(Quote $quote): Quote
    {
        $quote->load('lines');

        return DB::transaction(function () use ($quote) {
            $copy = Quote::query()->create([
                'company_id' => $quote->company_id,
                'store_id' => $quote->store_id,
                'customer_id' => $quote->customer_id,
                'salesperson_id' => Workspace::user()?->id ?? $quote->salesperson_id,
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'number' => $this->nextNumber($quote->company_id),
                'status' => 'draft',
                'reference' => $quote->reference,
                'currency' => $quote->currency,
                'quoted_at' => now()->toDateString(),
                'valid_until' => now()->addDays(30)->toDateString(),
                'terms' => $quote->terms,
                'notes' => $quote->notes,
                'customer_notes' => $quote->customer_notes,
                'subtotal_ht' => $quote->subtotal_ht,
                'tax_total' => $quote->tax_total,
                'discount_total' => $quote->discount_total,
                'total_ttc' => $quote->total_ttc,
            ]);

            foreach ($quote->lines as $i => $line) {
                QuoteLine::query()->create([
                    'quote_id' => $copy->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'sku' => $line->sku,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_percent' => $line->discount_percent,
                    'tax_rate' => $line->tax_rate,
                    'line_subtotal' => $line->line_subtotal,
                    'line_tax' => $line->line_tax,
                    'line_total' => $line->line_total,
                    'sort_order' => $i,
                ]);
            }

            $this->log($copy, 'duplicated', 'Devis dupliqué depuis '.$quote->number.'.');

            return $copy->fresh(['lines', 'customer', 'store']);
        });
    }

    public function deleteDraft(Quote $quote): void
    {
        if ($quote->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons peuvent être supprimés.']);
        }

        $quote->delete();
    }

    public function convertToInvoice(Quote $quote): Invoice
    {
        if (! $quote->isConvertible()) {
            throw ValidationException::withMessages(['status' => 'Ce devis ne peut pas être converti en facture.']);
        }

        $quote->load('lines');

        return DB::transaction(function () use ($quote) {
            $invoice = $this->invoices->create([
                'store_id' => $quote->store_id,
                'customer_id' => $quote->customer_id,
                'invoiced_at' => now()->toDateString(),
                'due_at' => now()->addDays(30)->toDateString(),
                'payment_terms' => $quote->terms,
                'notes' => 'Converti depuis devis '.$quote->number.($quote->notes ? "\n".$quote->notes : ''),
                'customer_notes' => $quote->customer_notes,
                'currency' => $quote->currency,
                'reference' => $quote->reference,
            ], $quote->toLinePayload(), true);

            $quote->update([
                'status' => 'converted',
                'converted_invoice_id' => $invoice->id,
                'converted_at' => now(),
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($quote, 'converted_invoice', 'Converti en facture '.$invoice->number.'.', [
                'invoice_id' => $invoice->id,
            ]);

            return $invoice->fresh(['lines', 'customer']);
        });
    }

    public function convertToSale(Quote $quote): PosSale
    {
        if (! $quote->isConvertible()) {
            throw ValidationException::withMessages(['status' => 'Ce devis ne peut pas être converti en vente.']);
        }

        $quote->load('lines');
        $items = $quote->toLinePayload();
        $total = (float) $quote->total_ttc;

        return DB::transaction(function () use ($quote, $items, $total) {
            $sale = $this->pos->completeSale(
                $items,
                [['method' => 'cash', 'amount' => $total, 'tendered' => $total]],
                $quote->customer_id,
                'Converti depuis devis '.$quote->number
            );

            $quote->update([
                'status' => 'converted',
                'converted_pos_sale_id' => $sale->id,
                'converted_at' => now(),
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($quote, 'converted_sale', 'Converti en vente POS '.$sale->number.'.', [
                'pos_sale_id' => $sale->id,
            ]);

            return $sale->fresh(['lines', 'customer']);
        });
    }

    public function syncCompanyExpired(int $companyId): void
    {
        Quote::query()
            ->forCompany($companyId)
            ->whereIn('status', ['sent', 'pending'])
            ->whereDate('valid_until', '<', now()->toDateString())
            ->update(['status' => 'expired']);
    }

    public function conversionRate(int $companyId): float
    {
        $total = Quote::query()->forCompany($companyId)->whereNotIn('status', ['draft'])->count();
        if ($total === 0) {
            return 0;
        }

        $converted = Quote::query()->forCompany($companyId)->where('status', 'converted')->count();

        return round(($converted / $total) * 100, 1);
    }

    protected function syncLines(Quote $quote, array $lines): void
    {
        $computed = $this->computeLines($quote->company_id, $lines);

        foreach ($computed['lines'] as $i => $line) {
            QuoteLine::query()->create([
                'quote_id' => $quote->id,
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
    }

    protected function recalculate(Quote $quote): void
    {
        $quote->load('lines');
        $subtotal = (float) $quote->lines->sum('line_subtotal');
        $tax = (float) $quote->lines->sum('line_tax');
        $discount = (float) $quote->lines->sum(fn ($l) => ($l->quantity * $l->unit_price) * ($l->discount_percent / 100));

        $quote->update([
            'subtotal_ht' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'discount_total' => round($discount, 2),
            'total_ttc' => round($subtotal + $tax, 2),
        ]);
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
                throw ValidationException::withMessages(['lines' => 'Produit introuvable.']);
            }
            $qty = (float) ($item['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $price = isset($item['unit_price']) ? (float) $item['unit_price'] : (float) $product->sale_price;
            $discount = (float) ($item['discount_percent'] ?? 0);
            $tax = isset($item['tax_rate']) ? (float) $item['tax_rate'] : (float) $product->tax_rate;

            $gross = $qty * $price;
            $discAmount = $gross * ($discount / 100);
            $net = $gross - $discAmount;
            $taxAmount = $net * ($tax / 100);

            $lines[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'description' => $item['description'] ?? null,
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
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne valide.']);
        }

        return [
            'lines' => $lines,
            'subtotal_ht' => round($subtotal, 2),
            'tax_total' => round($taxTotal, 2),
            'discount_total' => round($discountTotal, 2),
            'total_ttc' => round($subtotal + $taxTotal, 2),
        ];
    }

    protected function assertCustomer(int $companyId, int $customerId): void
    {
        if (! Customer::query()->forCompany($companyId)->whereKey($customerId)->exists()) {
            throw ValidationException::withMessages(['customer_id' => 'Client invalide.']);
        }
    }

    protected function log(Quote $quote, string $action, string $message, ?array $meta = null): void
    {
        QuoteLog::query()->create([
            'quote_id' => $quote->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}

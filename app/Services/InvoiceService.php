<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\InvoiceLog;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function nextNumber(int $companyId, string $type = 'invoice'): string
    {
        $prefix = $type === 'credit_note' ? 'AV' : 'FAC';
        $seq = Invoice::query()->forCompany($companyId)->where('type', $type)->count() + 1;

        return $prefix.'-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $lines, bool $issue = false): Invoice
    {
        $company = Workspace::company();
        $this->assertCustomer($company->id, (int) $data['customer_id']);

        return DB::transaction(function () use ($company, $data, $lines, $issue) {
            $invoice = Invoice::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'],
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'pos_sale_id' => $data['pos_sale_id'] ?? null,
                'type' => 'invoice',
                'number' => $data['number'] ?? $this->nextNumber($company->id),
                'status' => 'draft',
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? ($company->currency ?? 'MAD'),
                'invoiced_at' => $data['invoiced_at'] ?? now()->toDateString(),
                'due_at' => $data['due_at'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $this->syncLines($invoice, $lines);
            $this->recalculate($invoice);
            $this->log($invoice, 'created', 'Facture créée en brouillon.');

            if ($issue) {
                return $this->issue($invoice);
            }

            return $invoice->fresh(['lines.product', 'customer', 'store']);
        });
    }

    public function update(Invoice $invoice, array $data, array $lines): Invoice
    {
        if (! $invoice->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons sont modifiables.']);
        }

        $this->assertCustomer($invoice->company_id, (int) $data['customer_id']);

        return DB::transaction(function () use ($invoice, $data, $lines) {
            $invoice->update([
                'store_id' => $data['store_id'],
                'customer_id' => $data['customer_id'],
                'updated_by' => Workspace::user()?->id,
                'reference' => $data['reference'] ?? null,
                'currency' => $data['currency'] ?? $invoice->currency,
                'invoiced_at' => $data['invoiced_at'] ?? $invoice->invoiced_at,
                'due_at' => $data['due_at'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'notes' => $data['notes'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            $invoice->lines()->delete();
            $this->syncLines($invoice, $lines);
            $this->recalculate($invoice);
            $this->log($invoice, 'updated', 'Facture modifiée.');

            return $invoice->fresh(['lines.product', 'customer', 'store']);
        });
    }

    public function issue(Invoice $invoice): Invoice
    {
        if ($invoice->status !== 'draft' || $invoice->isCreditNote()) {
            throw ValidationException::withMessages(['status' => 'Seule une facture brouillon peut être émise.']);
        }
        if ($invoice->lines()->count() === 0) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne.']);
        }

        $invoice->update([
            'status' => 'pending',
            'balance_due' => $invoice->total_ttc,
            'amount_paid' => 0,
            'updated_by' => Workspace::user()?->id,
        ]);

        $this->adjustCustomerBalance($invoice->customer_id, (float) $invoice->total_ttc);
        $this->syncOverdue($invoice);
        $this->log($invoice, 'issued', 'Facture émise — en attente de paiement.');

        return $invoice->fresh(['lines', 'customer', 'store']);
    }

    public function send(Invoice $invoice): Invoice
    {
        if (! in_array($invoice->status, ['pending', 'partial', 'paid', 'expired'], true)) {
            throw ValidationException::withMessages(['status' => 'Émettez la facture avant envoi.']);
        }

        $invoice->update([
            'sent_at' => now(),
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($invoice, 'sent', 'Facture marquée comme envoyée (e-mail à brancher).', [
            'email' => $invoice->customer?->email,
        ]);

        return $invoice;
    }

    public function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        if (! in_array($invoice->status, ['pending', 'partial', 'expired'], true)) {
            throw ValidationException::withMessages(['status' => 'Cette facture n’accepte pas de paiement.']);
        }

        $amount = (float) ($data['amount'] ?? 0);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Montant invalide.']);
        }
        if ($amount > (float) $invoice->balance_due + 0.001) {
            throw ValidationException::withMessages(['amount' => 'Montant supérieur au solde restant.']);
        }

        return DB::transaction(function () use ($invoice, $data, $amount) {
            $payment = InvoicePayment::query()->create([
                'invoice_id' => $invoice->id,
                'created_by' => Workspace::user()?->id,
                'method' => $data['method'] ?? 'bank_transfer',
                'amount' => $amount,
                'paid_at' => $data['paid_at'] ?? now()->toDateString(),
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->refreshPaymentState($invoice);
            $this->adjustCustomerBalance($invoice->customer_id, -$amount);

            $customer = Customer::query()->find($invoice->customer_id);
            if ($customer) {
                $customer->update([
                    'lifetime_revenue' => round((float) $customer->lifetime_revenue + $amount, 2),
                    'last_purchase_at' => now(),
                ]);
            }

            $this->log($invoice, 'payment', 'Paiement enregistré : '.number_format($amount, 2, ',', ' ').' MAD.', [
                'payment_id' => $payment->id,
                'method' => $payment->method,
            ]);

            return $payment;
        });
    }

    public function cancel(Invoice $invoice): Invoice
    {
        if (in_array($invoice->status, ['cancelled', 'paid'], true)) {
            throw ValidationException::withMessages(['status' => 'Cette facture ne peut plus être annulée.']);
        }
        if ((float) $invoice->amount_paid > 0) {
            throw ValidationException::withMessages(['status' => 'Annulez d’abord les paiements ou créez un avoir.']);
        }

        return DB::transaction(function () use ($invoice) {
            if (in_array($invoice->status, ['pending', 'partial', 'expired'], true)) {
                $this->adjustCustomerBalance($invoice->customer_id, -(float) $invoice->balance_due);
            }

            $invoice->update([
                'status' => 'cancelled',
                'balance_due' => 0,
                'cancelled_at' => now(),
                'updated_by' => Workspace::user()?->id,
            ]);

            $this->log($invoice, 'cancelled', 'Facture annulée.');

            return $invoice;
        });
    }

    public function deleteDraft(Invoice $invoice): void
    {
        if ($invoice->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Seuls les brouillons peuvent être supprimés.']);
        }

        $invoice->delete();
    }

    public function createCreditNote(Invoice $parent, ?string $notes = null): Invoice
    {
        if (! in_array($parent->status, ['paid', 'partial', 'pending', 'expired'], true)) {
            throw ValidationException::withMessages(['status' => 'La facture source doit être émise.']);
        }

        $parent->load('lines');

        return DB::transaction(function () use ($parent, $notes) {
            $credit = Invoice::query()->create([
                'company_id' => $parent->company_id,
                'store_id' => $parent->store_id,
                'customer_id' => $parent->customer_id,
                'parent_invoice_id' => $parent->id,
                'created_by' => Workspace::user()?->id,
                'updated_by' => Workspace::user()?->id,
                'type' => 'credit_note',
                'number' => $this->nextNumber($parent->company_id, 'credit_note'),
                'status' => 'pending',
                'currency' => $parent->currency,
                'invoiced_at' => now()->toDateString(),
                'due_at' => now()->toDateString(),
                'payment_terms' => $parent->payment_terms,
                'notes' => $notes,
                'subtotal_ht' => -abs((float) $parent->subtotal_ht),
                'tax_total' => -abs((float) $parent->tax_total),
                'discount_total' => -abs((float) $parent->discount_total),
                'total_ttc' => -abs((float) $parent->total_ttc),
                'amount_paid' => 0,
                'balance_due' => -abs((float) $parent->total_ttc),
            ]);

            foreach ($parent->lines as $i => $line) {
                InvoiceLine::query()->create([
                    'invoice_id' => $credit->id,
                    'product_id' => $line->product_id,
                    'product_name' => $line->product_name,
                    'sku' => $line->sku,
                    'description' => $line->description,
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'discount_percent' => $line->discount_percent,
                    'tax_rate' => $line->tax_rate,
                    'line_subtotal' => -abs((float) $line->line_subtotal),
                    'line_tax' => -abs((float) $line->line_tax),
                    'line_total' => -abs((float) $line->line_total),
                    'sort_order' => $i,
                ]);
            }

            $this->adjustCustomerBalance($credit->customer_id, (float) $credit->total_ttc);
            $this->log($credit, 'credit_note', 'Avoir créé depuis '.$parent->number.'.');
            $this->log($parent, 'credit_note', 'Avoir '.$credit->number.' généré.');

            return $credit->fresh(['lines', 'customer', 'parentInvoice']);
        });
    }

    public function syncOverdue(Invoice $invoice): Invoice
    {
        if ($invoice->isOverdue() && in_array($invoice->status, ['pending', 'partial'], true)) {
            $invoice->update(['status' => 'expired']);
        }

        return $invoice;
    }

    public function syncCompanyOverdue(int $companyId): void
    {
        Invoice::query()
            ->forCompany($companyId)
            ->invoices()
            ->whereIn('status', ['pending', 'partial'])
            ->whereDate('due_at', '<', now()->toDateString())
            ->where('balance_due', '>', 0)
            ->update(['status' => 'expired']);
    }

    protected function refreshPaymentState(Invoice $invoice): void
    {
        $invoice->refresh();
        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total_ttc;
        $balance = max(0, round($total - $paid, 2));

        $status = $invoice->status;
        if ($paid <= 0) {
            $status = $invoice->due_at && $invoice->due_at->isPast() ? 'expired' : 'pending';
        } elseif ($balance > 0.01) {
            $status = 'partial';
        } else {
            $status = 'paid';
        }

        $invoice->update([
            'amount_paid' => round($paid, 2),
            'balance_due' => $balance,
            'status' => $status,
            'paid_at' => $status === 'paid' ? now() : null,
            'updated_by' => Workspace::user()?->id,
        ]);

        if ($invoice->customer_id && $status === 'paid') {
            Customer::query()->whereKey($invoice->customer_id)->update([
                'last_purchase_at' => now(),
            ]);
        }
    }

    protected function syncLines(Invoice $invoice, array $lines): void
    {
        $computed = $this->computeLines($invoice->company_id, $lines);

        foreach ($computed['lines'] as $i => $line) {
            InvoiceLine::query()->create([
                'invoice_id' => $invoice->id,
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

    protected function recalculate(Invoice $invoice): void
    {
        $invoice->load('lines');
        $subtotal = (float) $invoice->lines->sum('line_subtotal');
        $tax = (float) $invoice->lines->sum('line_tax');
        $discount = (float) $invoice->lines->sum(fn ($l) => ($l->quantity * $l->unit_price) * ($l->discount_percent / 100));

        $invoice->update([
            'subtotal_ht' => round($subtotal, 2),
            'tax_total' => round($tax, 2),
            'discount_total' => round($discount, 2),
            'total_ttc' => round($subtotal + $tax, 2),
            'balance_due' => $invoice->status === 'draft' ? 0 : max(0, round($subtotal + $tax - (float) $invoice->amount_paid, 2)),
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

    protected function adjustCustomerBalance(?int $customerId, float $delta): void
    {
        if (! $customerId || abs($delta) < 0.0001) {
            return;
        }

        $customer = Customer::query()->whereKey($customerId)->first();
        if (! $customer) {
            return;
        }

        $customer->update([
            'balance' => max(0, round((float) $customer->balance + $delta, 2)),
        ]);
    }

    protected function assertCustomer(int $companyId, int $customerId): void
    {
        $exists = Customer::query()->forCompany($companyId)->whereKey($customerId)->exists();
        if (! $exists) {
            throw ValidationException::withMessages(['customer_id' => 'Client invalide.']);
        }
    }

    protected function log(Invoice $invoice, string $action, string $message, ?array $meta = null): void
    {
        InvoiceLog::query()->create([
            'invoice_id' => $invoice->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}

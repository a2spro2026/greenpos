<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Store;
use App\Models\Supplier;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Support\Facades\Schema;

class GlobalSearchService
{
    /**
     * Recherche transversale lecture seule — infrastructure UX.
     *
     * @return list<array{type: string, type_label: string, title: string, subtitle: string, url: string, icon: string}>
     */
    public function search(string $query, int $limitPerType = 5): array
    {
        $q = trim($query);
        if (mb_strlen($q) < 2) {
            return [];
        }

        $companyId = Workspace::company()?->id;
        if (! $companyId) {
            return [];
        }

        $like = '%'.$q.'%';
        $results = [];

        if (Workspace::can('products.view') && Schema::hasTable('products')) {
            Product::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('sku', 'like', $like)
                        ->orWhere('barcode', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitPerType)
                ->get(['id', 'name', 'sku', 'status'])
                ->each(function (Product $p) use (&$results) {
                    $results[] = [
                        'type' => 'product',
                        'type_label' => 'Produit',
                        'title' => $p->name,
                        'subtitle' => trim(($p->sku ?: '').' · '.($p->status ?: '')),
                        'url' => route('products.show', $p),
                        'icon' => 'P',
                    ];
                });
        }

        if (Workspace::can('customers.view') && Schema::hasTable('customers')) {
            Customer::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitPerType)
                ->get(['id', 'name', 'code', 'email'])
                ->each(function (Customer $c) use (&$results) {
                    $results[] = [
                        'type' => 'customer',
                        'type_label' => 'Client',
                        'title' => $c->name,
                        'subtitle' => trim(($c->code ?: '').' · '.($c->email ?: '')),
                        'url' => route('customers.show', $c),
                        'icon' => 'C',
                    ];
                });
        }

        if (Workspace::can('suppliers.view') && Schema::hasTable('suppliers')) {
            Supplier::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitPerType)
                ->get(['id', 'name', 'code', 'email'])
                ->each(function (Supplier $s) use (&$results) {
                    $results[] = [
                        'type' => 'supplier',
                        'type_label' => 'Fournisseur',
                        'title' => $s->name,
                        'subtitle' => trim(($s->code ?: '').' · '.($s->email ?: '')),
                        'url' => route('suppliers.show', $s),
                        'icon' => 'F',
                    ];
                });
        }

        if (Workspace::can('invoices.view') && Schema::hasTable('invoices')) {
            Invoice::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('number', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->latest('id')
                ->limit($limitPerType)
                ->get(['id', 'number', 'status', 'total_ttc'])
                ->each(function (Invoice $i) use (&$results) {
                    $results[] = [
                        'type' => 'invoice',
                        'type_label' => 'Facture',
                        'title' => $i->number,
                        'subtitle' => ($i->status ?? '').' · '.number_format((float) ($i->total_ttc ?? 0), 2, ',', ' '),
                        'url' => route('invoices.show', $i),
                        'icon' => '€',
                    ];
                });
        }

        if (Workspace::can('quotes.view') && Schema::hasTable('quotes')) {
            Quote::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('number', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->latest('id')
                ->limit($limitPerType)
                ->get(['id', 'number', 'status', 'total_ttc'])
                ->each(function (Quote $quote) use (&$results) {
                    $results[] = [
                        'type' => 'quote',
                        'type_label' => 'Devis',
                        'title' => $quote->number,
                        'subtitle' => ($quote->status ?? '').' · '.number_format((float) ($quote->total_ttc ?? 0), 2, ',', ' '),
                        'url' => route('quotes.show', $quote),
                        'icon' => 'D',
                    ];
                });
        }

        if (Workspace::can('sales.view') && Schema::hasTable('sales')) {
            Sale::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('number', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->latest('id')
                ->limit($limitPerType)
                ->get(['id', 'number', 'status', 'total_ttc'])
                ->each(function (Sale $sale) use (&$results) {
                    $results[] = [
                        'type' => 'sale',
                        'type_label' => 'Vente',
                        'title' => $sale->number,
                        'subtitle' => ($sale->status ?? '').' · '.number_format((float) ($sale->total_ttc ?? 0), 2, ',', ' '),
                        'url' => route('sales.show', $sale),
                        'icon' => 'V',
                    ];
                });
        }

        if ((Workspace::can('payments.view') || Workspace::can('reports.financial') || Workspace::can('sales.view'))
            && Schema::hasTable('sale_payments')) {
            SalePayment::query()
                ->whereHas('sale', fn ($q) => $q->where('company_id', $companyId))
                ->where(function ($builder) use ($like) {
                    $builder->where('reference', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                })
                ->with('sale:id,number')
                ->latest('id')
                ->limit($limitPerType)
                ->get()
                ->each(function (SalePayment $p) use (&$results) {
                    if (! $p->sale) {
                        return;
                    }
                    $results[] = [
                        'type' => 'payment',
                        'type_label' => 'Paiement',
                        'title' => number_format((float) $p->amount, 2, ',', ' ').' · '.($p->method ?? ''),
                        'subtitle' => 'Vente '.$p->sale->number.($p->reference ? ' · '.$p->reference : ''),
                        'url' => route('sales.show', $p->sale),
                        'icon' => '$',
                    ];
                });
        }

        if ((Workspace::can('payments.view') || Workspace::can('invoices.view'))
            && Schema::hasTable('invoice_payments')) {
            InvoicePayment::query()
                ->whereHas('invoice', fn ($q) => $q->where('company_id', $companyId))
                ->where(function ($builder) use ($like) {
                    $builder->where('reference', 'like', $like)
                        ->orWhere('notes', 'like', $like);
                })
                ->with('invoice:id,number')
                ->latest('id')
                ->limit($limitPerType)
                ->get()
                ->each(function (InvoicePayment $p) use (&$results) {
                    if (! $p->invoice) {
                        return;
                    }
                    $results[] = [
                        'type' => 'payment',
                        'type_label' => 'Paiement',
                        'title' => number_format((float) $p->amount, 2, ',', ' ').' · '.($p->method ?? ''),
                        'subtitle' => 'Facture '.$p->invoice->number.($p->reference ? ' · '.$p->reference : ''),
                        'url' => route('invoices.show', $p->invoice),
                        'icon' => '$',
                    ];
                });
        }

        if (Workspace::can('purchases.view') && Schema::hasTable('purchase_orders')) {
            PurchaseOrder::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('number', 'like', $like)
                        ->orWhere('reference', 'like', $like);
                })
                ->latest('id')
                ->limit($limitPerType)
                ->get(['id', 'number', 'status', 'total_ttc'])
                ->each(function (PurchaseOrder $order) use (&$results) {
                    $results[] = [
                        'type' => 'order',
                        'type_label' => 'Commande achat',
                        'title' => $order->number,
                        'subtitle' => ($order->status ?? '').' · '.number_format((float) ($order->total_ttc ?? 0), 2, ',', ' '),
                        'url' => route('purchases.orders.show', $order),
                        'icon' => 'A',
                    ];
                });
        }

        if (Workspace::can('users.view') && Schema::hasTable('users')) {
            User::query()
                ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitPerType)
                ->get(['id', 'name', 'email'])
                ->each(function (User $u) use (&$results) {
                    $results[] = [
                        'type' => 'user',
                        'type_label' => 'Utilisateur',
                        'title' => method_exists($u, 'displayName') ? $u->displayName() : $u->name,
                        'subtitle' => $u->email ?? '',
                        'url' => route('users.show', $u),
                        'icon' => 'U',
                    ];
                });
        }

        if (Workspace::can('documents.view') && Schema::hasTable('documents')) {
            Document::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('original_name', 'like', $like);
                })
                ->latest('id')
                ->limit($limitPerType)
                ->get(['id', 'name', 'original_name', 'extension'])
                ->each(function (Document $d) use (&$results) {
                    $results[] = [
                        'type' => 'document',
                        'type_label' => 'Document',
                        'title' => $d->name,
                        'subtitle' => strtoupper($d->extension ?? '').' · '.($d->original_name ?? ''),
                        'url' => route('documents.show', $d),
                        'icon' => '📄',
                    ];
                });
        }

        if (Workspace::can('stores.view') && Schema::hasTable('stores')) {
            Store::query()
                ->where('company_id', $companyId)
                ->where(function ($builder) use ($like) {
                    $builder->where('name', 'like', $like)
                        ->orWhere('code', 'like', $like)
                        ->orWhere('city', 'like', $like);
                })
                ->orderBy('name')
                ->limit($limitPerType)
                ->get(['id', 'name', 'code', 'city'])
                ->each(function (Store $s) use (&$results) {
                    $results[] = [
                        'type' => 'store',
                        'type_label' => 'Boutique',
                        'title' => $s->name,
                        'subtitle' => trim(($s->code ?: '').' · '.($s->city ?: '')),
                        'url' => route('stores.show', $s),
                        'icon' => 'B',
                    ];
                });
        }

        return $results;
    }
}

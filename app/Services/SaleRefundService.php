<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleLine;
use App\Models\SaleRefund;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Monetary ticket refunds (partial or full). Merchandise returns stay on SaleReturn.
 * Restock updates stock and returned quantities so a later SaleReturn cannot double-count the same units.
 */
class SaleRefundService
{
    public function __construct(
        private StockService $stock,
        private SalePaymentWorkflowService $workflow,
        private SaleService $sales,
    ) {
    }

    public function refundSale(Sale $sale, array $data): SaleRefund
    {
        return DB::transaction(function () use ($sale, $data) {
            $amount = round((float) $data['amount'], 2);
            $max = round((float) $sale->total_ttc - (float) $sale->amount_returned, 2);
            if ($amount <= 0 || $amount > $max + 0.009) {
                throw ValidationException::withMessages(['amount' => 'Montant de remboursement invalide (max '.number_format(max(0, $max), 2, ',', ' ').').']);
            }

            $restock = (bool) ($data['restock'] ?? false);
            $lines = $this->normalizeLines($data['lines'] ?? []);
            $type = $amount + 0.009 >= $max ? 'full' : 'partial';
            if ($restock) {
                $this->restockSaleLines($sale, $lines, $type);
            }

            $refund = $this->persist($sale->company_id, [
                'sale_id' => $sale->id,
                'amount' => $amount,
                'type' => $type,
                'method' => $data['method'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'restock' => $restock,
                'lines' => $lines,
            ]);

            $sale->update([
                'amount_returned' => round((float) $sale->amount_returned + $amount, 2),
                'updated_by' => Workspace::user()?->id,
            ]);
            $this->workflow->recalculateSale($sale->fresh());
            $this->sales->log($sale->fresh(), 'refund', 'Remboursement '.$refund->number.' — '.number_format($amount, 2, ',', ' ').' MAD ('.$refund->statusLabel().').', [
                'refund_id' => $refund->id,
                'restock' => $restock,
            ]);

            return $refund;
        });
    }

    public function refundPos(PosSale $sale, array $data): SaleRefund
    {
        if ($sale->status !== 'completed') {
            throw ValidationException::withMessages(['sale' => 'Seuls les tickets validés peuvent être remboursés.']);
        }

        return DB::transaction(function () use ($sale, $data) {
            $already = (float) $sale->amount_refunded;
            $max = round((float) $sale->total_ttc - $already, 2);
            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0 || $amount > $max + 0.009) {
                throw ValidationException::withMessages(['amount' => 'Montant de remboursement invalide.']);
            }

            $restock = (bool) ($data['restock'] ?? false);
            $lines = $this->normalizeLines($data['lines'] ?? []);
            $type = $amount + 0.009 >= $max ? 'full' : 'partial';
            if ($restock) {
                $this->restockPosLines($sale, $lines, $type);
            }

            $refund = $this->persist($sale->company_id, [
                'pos_sale_id' => $sale->id,
                'amount' => $amount,
                'type' => $type,
                'method' => $data['method'],
                'reason' => $data['reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'restock' => $restock,
                'lines' => $lines,
            ]);

            $sale->update([
                'amount_refunded' => round($already + $amount, 2),
            ]);
            $this->workflow->recalculatePos($sale->fresh());

            return $refund;
        });
    }

    /** @param  list<array{line_id:int, quantity:float}>  $lines */
    private function restockSaleLines(Sale $sale, array $lines, string $type): void
    {
        $sale->loadMissing('lines');
        $map = $lines === [] && $type === 'full'
            ? $sale->lines->map(fn (SaleLine $line) => ['line_id' => $line->id, 'quantity' => $line->returnableQuantity()])->all()
            : $lines;

        if ($map === []) {
            throw ValidationException::withMessages(['lines' => 'Indiquez les quantités à réintégrer, ou choisissez un remboursement total.']);
        }

        foreach ($map as $row) {
            $line = $sale->lines->firstWhere('id', (int) $row['line_id']);
            if (! $line) {
                throw ValidationException::withMessages(['lines' => 'Ligne de vente introuvable.']);
            }
            $qty = (float) $row['quantity'];
            if ($qty <= 0) {
                continue;
            }
            if ($qty > $line->returnableQuantity() + 0.0001) {
                throw ValidationException::withMessages(['lines' => 'Quantité supérieure au restant pour '.$line->product_name.'.']);
            }
            $line->update(['returned_quantity' => (float) $line->returned_quantity + $qty]);
            $this->restockProduct($sale->store_id, $line->product_id, $qty, $sale->number);
        }
    }

    /** @param  list<array{line_id:int, quantity:float}>  $lines */
    private function restockPosLines(PosSale $sale, array $lines, string $type): void
    {
        $sale->loadMissing('lines');
        $map = $lines === [] && $type === 'full'
            ? $sale->lines->map(fn (PosSaleLine $line) => [
                'line_id' => $line->id,
                'quantity' => max(0, (float) $line->quantity - (float) $line->returned_quantity),
            ])->all()
            : $lines;

        if ($map === []) {
            throw ValidationException::withMessages(['lines' => 'Indiquez les quantités à réintégrer, ou choisissez un remboursement total.']);
        }

        foreach ($map as $row) {
            $line = $sale->lines->firstWhere('id', (int) $row['line_id']);
            if (! $line) {
                throw ValidationException::withMessages(['lines' => 'Ligne de ticket introuvable.']);
            }
            $qty = (float) $row['quantity'];
            $remaining = max(0, (float) $line->quantity - (float) $line->returned_quantity);
            if ($qty <= 0) {
                continue;
            }
            if ($qty > $remaining + 0.0001) {
                throw ValidationException::withMessages(['lines' => 'Quantité supérieure au restant pour '.$line->product_name.'.']);
            }
            $line->update(['returned_quantity' => (float) $line->returned_quantity + $qty]);
            $this->restockProduct($sale->store_id, $line->product_id, $qty, $sale->number);
        }
    }

    private function restockProduct(int $storeId, ?int $productId, float $qty, string $reference): void
    {
        if (! $productId) {
            return;
        }
        $product = Product::query()->find($productId);
        if (! $product?->track_stock) {
            return;
        }
        $this->stock->applyMovement([
            'store_id' => $storeId,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => $qty,
            'reference' => 'RMB-'.$reference,
            'comment' => 'Remboursement '.$reference,
            'moved_at' => now(),
        ]);
    }

    private function persist(int $companyId, array $data): SaleRefund
    {
        $seq = SaleRefund::query()->where('company_id', $companyId)->count() + 1;

        return SaleRefund::query()->create([
            'company_id' => $companyId,
            'sale_id' => $data['sale_id'] ?? null,
            'pos_sale_id' => $data['pos_sale_id'] ?? null,
            'created_by' => Workspace::user()?->id,
            'number' => 'RMB-'.now()->format('Ymd').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
            'type' => $data['type'],
            'method' => $data['method'],
            'reason' => $data['reason'],
            'notes' => $data['notes'],
            'amount' => $data['amount'],
            'restock' => $data['restock'],
            'status' => $data['restock'] ? 'restocked' : 'completed',
            'lines' => $data['lines'],
            'refunded_at' => now(),
        ]);
    }

    /** @return list<array{line_id:int, quantity:float}> */
    private function normalizeLines(array $lines): array
    {
        $out = [];
        foreach ($lines as $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            if ($qty <= 0 || empty($line['line_id'])) {
                continue;
            }
            $out[] = ['line_id' => (int) $line['line_id'], 'quantity' => $qty];
        }

        return $out;
    }
}

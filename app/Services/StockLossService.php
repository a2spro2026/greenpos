<?php

namespace App\Services;

use App\Models\MaterialConsumption;
use App\Models\Product;
use App\Models\StockLoss;
use App\Models\StockLossLine;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockLossService
{
    public function __construct(private StockService $stock)
    {
    }

    public function nextNumber(int $companyId): string
    {
        $seq = StockLoss::query()->forCompany($companyId)->count() + 1;

        return 'PERTE-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array{store_id:int,loss_type:string,loss_date:string,employee_id?:int|null,responsible_name?:string|null,notes?:string|null,lines:array<int,array{product_id:int,quantity:float,unit_cost?:float,notes?:string}>}  $data
     */
    public function create(array $data): StockLoss
    {
        $company = Workspace::company();
        $lines = $data['lines'] ?? [];

        if (count($lines) === 0) {
            throw ValidationException::withMessages(['lines' => 'Ajoutez au moins une ligne produit.']);
        }

        return DB::transaction(function () use ($company, $data, $lines) {
            $loss = StockLoss::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'created_by' => Workspace::user()?->id,
                'employee_id' => $data['employee_id'] ?? null,
                'responsible_name' => $data['responsible_name'] ?? null,
                'number' => $this->nextNumber($company->id),
                'loss_type' => $data['loss_type'],
                'loss_date' => $data['loss_date'],
                'total_cost' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $total = 0.0;

            foreach ($lines as $row) {
                $product = Product::query()
                    ->forCompany($company->id)
                    ->whereKey($row['product_id'])
                    ->firstOrFail();

                $qty = abs((float) $row['quantity']);
                if ($qty <= 0) {
                    continue;
                }

                $unitCost = (float) ($row['unit_cost'] ?? $product->purchase_price ?? 0);
                $lineCost = round($qty * $unitCost, 2);
                $total += $lineCost;

                StockLossLine::query()->create([
                    'stock_loss_id' => $loss->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'notes' => $row['notes'] ?? null,
                ]);

                if ($product->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $loss->store_id,
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $qty,
                        'unit_cost' => $unitCost,
                        'reference' => $loss->number,
                        'comment' => 'Perte '.$loss->typeLabel().' '.$loss->number,
                        'moved_at' => $loss->loss_date,
                    ]);
                }

                MaterialConsumption::query()->create([
                    'company_id' => $company->id,
                    'store_id' => $loss->store_id,
                    'product_id' => $product->id,
                    'created_by' => Workspace::user()?->id,
                    'reason' => 'loss',
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'reference' => $loss->number,
                    'notes' => $row['notes'] ?? null,
                    'consumed_at' => $loss->loss_date->format('Y-m-d'),
                ]);
            }

            if ($total <= 0) {
                throw ValidationException::withMessages(['lines' => 'Aucune ligne valide.']);
            }

            $loss->update(['total_cost' => round($total, 2)]);

            return $loss->fresh(['lines.product', 'store', 'creator']);
        });
    }
}

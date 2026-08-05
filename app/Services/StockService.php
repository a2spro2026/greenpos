<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockInventory;
use App\Models\StockInventoryLine;
use App\Models\StockLevel;
use App\Models\StockMovement;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function ensureLevel(int $companyId, int $storeId, int $productId, float $min = 5, ?float $max = 100): StockLevel
    {
        return StockLevel::query()->firstOrCreate(
            [
                'product_id' => $productId,
                'store_id' => $storeId,
            ],
            [
                'company_id' => $companyId,
                'quantity' => 0,
                'min_quantity' => $min,
                'max_quantity' => $max,
                'reserved_quantity' => 0,
            ]
        );
    }

    public function updateThresholds(StockLevel $level, ?float $min, ?float $max): StockLevel
    {
        $level->update([
            'min_quantity' => $min ?? $level->min_quantity,
            'max_quantity' => $max,
        ]);

        return $level->fresh();
    }

    public function applyMovement(array $data): StockMovement
    {
        $company = Workspace::company();
        if (! $company) {
            throw ValidationException::withMessages(['store_id' => 'Espace de travail invalide.']);
        }

        $product = Product::query()
            ->forCompany($company->id)
            ->whereKey($data['product_id'])
            ->firstOrFail();

        if (! $product->track_stock) {
            throw ValidationException::withMessages(['product_id' => 'Ce produit ne suit pas le stock.']);
        }

        $type = $data['type'];
        if (! array_key_exists($type, StockMovement::TYPES)) {
            throw ValidationException::withMessages(['type' => 'Type de mouvement invalide.']);
        }

        if ($type === 'transfer') {
            throw ValidationException::withMessages(['type' => 'Les transferts seront disponibles en V2.']);
        }

        $qtyInput = (float) $data['quantity'];
        if ($type !== 'adjustment' && $qtyInput <= 0) {
            throw ValidationException::withMessages(['quantity' => 'La quantité doit être positive.']);
        }

        return DB::transaction(function () use ($company, $product, $data, $type, $qtyInput) {
            $level = StockLevel::query()
                ->where('product_id', $product->id)
                ->where('store_id', $data['store_id'])
                ->lockForUpdate()
                ->first();

            if (! $level) {
                $level = $this->ensureLevel($company->id, (int) $data['store_id'], $product->id);
                $level = StockLevel::query()->whereKey($level->id)->lockForUpdate()->first();
            }

            $before = (float) $level->quantity;
            $delta = match ($type) {
                'in' => abs($qtyInput),
                'out' => -abs($qtyInput),
                'adjustment' => $qtyInput - $before, // absolute target quantity
                default => 0,
            };

            if ($type === 'adjustment') {
                $after = $qtyInput;
                $recordedQty = $delta;
            } else {
                $after = $before + $delta;
                $recordedQty = abs($qtyInput);
            }

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuffisant (disponible : {$before}).",
                ]);
            }

            $level->update([
                'quantity' => $after,
                'last_movement_at' => now(),
            ]);

            return StockMovement::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'product_id' => $product->id,
                'product_variant_id' => $data['product_variant_id'] ?? null,
                'user_id' => Workspace::user()?->id,
                'inventory_id' => $data['inventory_id'] ?? null,
                'type' => $type,
                'quantity' => $recordedQty,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'unit_cost' => $data['unit_cost'] ?? $product->purchase_price,
                'reference' => $data['reference'] ?? null,
                'comment' => $data['comment'] ?? null,
                'related_store_id' => $data['related_store_id'] ?? null,
                'moved_at' => $data['moved_at'] ?? now(),
            ]);
        });
    }

    public function createInventory(array $data): StockInventory
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data) {
            $inventory = StockInventory::query()->create([
                'company_id' => $company->id,
                'store_id' => $data['store_id'],
                'created_by' => Workspace::user()?->id,
                'name' => $data['name'],
                'status' => 'in_progress',
                'notes' => $data['notes'] ?? null,
                'started_at' => now(),
            ]);

            $levels = StockLevel::query()
                ->forCompany($company->id)
                ->where('store_id', $data['store_id'])
                ->whereHas('product', fn ($q) => $q->where('track_stock', true)->whereNull('deleted_at'))
                ->get();

            foreach ($levels as $level) {
                StockInventoryLine::query()->create([
                    'inventory_id' => $inventory->id,
                    'product_id' => $level->product_id,
                    'expected_qty' => $level->quantity,
                    'counted_qty' => null,
                    'difference' => null,
                    'is_counted' => false,
                ]);
            }

            // Also include track_stock products without a level yet
            $existingIds = $levels->pluck('product_id')->all();
            $missing = Product::query()
                ->forCompany($company->id)
                ->where('track_stock', true)
                ->where('status', 'active')
                ->whereNotIn('id', $existingIds)
                ->get();

            foreach ($missing as $product) {
                $this->ensureLevel($company->id, (int) $data['store_id'], $product->id);
                StockInventoryLine::query()->create([
                    'inventory_id' => $inventory->id,
                    'product_id' => $product->id,
                    'expected_qty' => 0,
                    'counted_qty' => null,
                    'difference' => null,
                    'is_counted' => false,
                ]);
            }

            return $inventory->load('lines.product', 'store');
        });
    }

    public function countLine(StockInventoryLine $line, float $countedQty): StockInventoryLine
    {
        $inventory = $line->inventory;
        if (! in_array($inventory->status, ['draft', 'in_progress'], true)) {
            throw ValidationException::withMessages(['counted_qty' => 'Inventaire non modifiable.']);
        }

        $expected = (float) $line->expected_qty;
        $line->update([
            'counted_qty' => $countedQty,
            'difference' => $countedQty - $expected,
            'is_counted' => true,
        ]);

        if ($inventory->status === 'draft') {
            $inventory->update(['status' => 'in_progress']);
        }

        return $line->fresh('product');
    }

    public function validateInventory(StockInventory $inventory): StockInventory
    {
        if ($inventory->status === 'validated') {
            return $inventory;
        }

        if (! in_array($inventory->status, ['draft', 'in_progress'], true)) {
            throw ValidationException::withMessages(['status' => 'Inventaire non validable.']);
        }

        return DB::transaction(function () use ($inventory) {
            $inventory->load('lines');

            foreach ($inventory->lines as $line) {
                if (! $line->is_counted) {
                    continue;
                }

                $diff = (float) $line->difference;
                if ($diff === 0.0) {
                    continue;
                }

                $this->applyMovement([
                    'store_id' => $inventory->store_id,
                    'product_id' => $line->product_id,
                    'type' => 'adjustment',
                    'quantity' => (float) $line->counted_qty,
                    'reference' => 'INV-'.$inventory->id,
                    'comment' => 'Ajustement inventaire #'.$inventory->id,
                    'inventory_id' => $inventory->id,
                    'moved_at' => now(),
                ]);
            }

            $inventory->update([
                'status' => 'validated',
                'validated_by' => Workspace::user()?->id,
                'validated_at' => now(),
            ]);

            return $inventory->fresh(['lines.product', 'store', 'validator']);
        });
    }

    public function cancelInventory(StockInventory $inventory): StockInventory
    {
        if ($inventory->status === 'validated') {
            throw ValidationException::withMessages(['status' => 'Un inventaire validé ne peut pas être annulé.']);
        }

        $inventory->update(['status' => 'cancelled']);

        return $inventory;
    }
}

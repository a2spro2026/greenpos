<?php

namespace App\Services;

use App\Models\MaterialConsumption;
use App\Models\Product;
use App\Models\ProductBomComponent;
use App\Models\Production;
use App\Models\ProductionConsumption;
use App\Models\StockLevel;
use App\Support\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function __construct(private StockService $stock)
    {
    }

    public function nextNumber(int $companyId): string
    {
        $seq = Production::query()->forCompany($companyId)->count() + 1;

        return 'PROD-'.now()->format('Ym').'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function saveDraft(array $data, ?Production $production = null): Production
    {
        $company = Workspace::company();

        if ($production && ! $production->isEditable()) {
            throw ValidationException::withMessages(['status' => 'Production validée — non modifiable.']);
        }

        $payload = [
            'store_id' => $data['store_id'],
            'product_id' => $data['product_id'],
            'quantity' => $data['quantity'],
            'produced_at' => $data['produced_at'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
            'unit_cost' => $data['unit_cost'] ?? 0,
            'total_cost' => round((float) ($data['unit_cost'] ?? 0) * (float) $data['quantity'], 2),
        ];

        if ($production) {
            $production->update($payload);

            return $production->fresh(['product', 'store', 'consumptions.product']);
        }

        return Production::query()->create([
            ...$payload,
            'company_id' => $company->id,
            'created_by' => Workspace::user()?->id,
            'number' => $this->nextNumber($company->id),
            'status' => 'draft',
        ])->fresh(['product', 'store']);
    }

    public function validate(Production $production): Production
    {
        if ($production->status === 'validated') {
            return $production;
        }

        if ($production->status !== 'draft') {
            throw ValidationException::withMessages(['status' => 'Statut de production invalide.']);
        }

        $company = Workspace::company();
        $bom = ProductBomComponent::query()
            ->forCompany($company->id)
            ->where('product_id', $production->product_id)
            ->with('component')
            ->orderBy('sort_order')
            ->get();

        if ($bom->isEmpty()) {
            throw ValidationException::withMessages([
                'product_id' => 'Aucune nomenclature (BOM) définie pour ce produit fini.',
            ]);
        }

        $qtyProduced = (float) $production->quantity;

        return DB::transaction(function () use ($production, $bom, $qtyProduced, $company) {
            $totalCost = 0.0;
            $lines = [];

            foreach ($bom as $row) {
                $need = round((float) $row->quantity * $qtyProduced, 3);
                $component = $row->component;
                if (! $component) {
                    throw ValidationException::withMessages([
                        'product_id' => 'Composant BOM introuvable.',
                    ]);
                }

                $unitCost = (float) ($row->unit_cost ?? $component->purchase_price ?? 0);
                $lineCost = round($need * $unitCost, 2);

                if ($component->track_stock) {
                    $level = StockLevel::query()
                        ->where('product_id', $component->id)
                        ->where('store_id', $production->store_id)
                        ->first();
                    $available = $level ? (float) $level->quantity : 0.0;
                    if ($available < $need) {
                        throw ValidationException::withMessages([
                            'quantity' => "Stock insuffisant pour « {$component->name} » (besoin {$need}, dispo {$available}).",
                        ]);
                    }
                }

                $lines[] = compact('component', 'need', 'unitCost', 'lineCost');
                $totalCost += $lineCost;
            }

            foreach ($lines as $line) {
                /** @var Product $component */
                $component = $line['component'];
                $need = $line['need'];
                $unitCost = $line['unitCost'];
                $lineCost = $line['lineCost'];

                if ($component->track_stock) {
                    $this->stock->applyMovement([
                        'store_id' => $production->store_id,
                        'product_id' => $component->id,
                        'type' => 'out',
                        'quantity' => $need,
                        'unit_cost' => $unitCost,
                        'reference' => $production->number,
                        'comment' => 'Consommation production '.$production->number,
                        'moved_at' => now(),
                    ]);
                }

                ProductionConsumption::query()->create([
                    'production_id' => $production->id,
                    'product_id' => $component->id,
                    'quantity' => $need,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                ]);

                MaterialConsumption::query()->create([
                    'company_id' => $company->id,
                    'store_id' => $production->store_id,
                    'product_id' => $component->id,
                    'created_by' => Workspace::user()?->id,
                    'reason' => 'production',
                    'quantity' => $need,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineCost,
                    'reference' => $production->number,
                    'notes' => 'Production '.$production->number,
                    'consumed_at' => $production->produced_at?->format('Y-m-d') ?? now()->toDateString(),
                ]);
            }

            $unitCostFinished = $qtyProduced > 0 ? round($totalCost / $qtyProduced, 2) : 0;

            $finished = Product::query()->find($production->product_id);
            if ($finished?->track_stock) {
                $this->stock->applyMovement([
                    'store_id' => $production->store_id,
                    'product_id' => $production->product_id,
                    'type' => 'in',
                    'quantity' => $qtyProduced,
                    'unit_cost' => $unitCostFinished,
                    'reference' => $production->number,
                    'comment' => 'Production validée '.$production->number,
                    'moved_at' => now(),
                ]);

                if ($unitCostFinished > 0) {
                    $finished->update(['purchase_price' => $unitCostFinished]);
                }
            }

            $production->update([
                'status' => 'validated',
                'unit_cost' => $unitCostFinished,
                'total_cost' => round($totalCost, 2),
                'validated_by' => Workspace::user()?->id,
                'validated_at' => now(),
            ]);

            return $production->fresh(['product', 'store', 'consumptions.product', 'validator', 'creator']);
        });
    }

    public function syncBom(int $productId, array $components): void
    {
        $company = Workspace::company();

        DB::transaction(function () use ($company, $productId, $components) {
            ProductBomComponent::query()
                ->forCompany($company->id)
                ->where('product_id', $productId)
                ->delete();

            foreach (array_values($components) as $i => $row) {
                if (empty($row['component_product_id']) || empty($row['quantity'])) {
                    continue;
                }
                if ((int) $row['component_product_id'] === $productId) {
                    continue;
                }

                $component = Product::query()->forCompany($company->id)->whereKey($row['component_product_id'])->first();
                if (! $component) {
                    continue;
                }

                ProductBomComponent::query()->create([
                    'company_id' => $company->id,
                    'product_id' => $productId,
                    'component_product_id' => $component->id,
                    'quantity' => $row['quantity'],
                    'unit_cost' => $row['unit_cost'] ?? $component->purchase_price,
                    'sort_order' => $i,
                ]);
            }
        });
    }
}

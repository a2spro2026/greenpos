<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductChangeLog;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function create(array $data, ?UploadedFile $image = null, array $variants = [], array $storeIds = []): Product
    {
        return DB::transaction(function () use ($data, $image, $variants, $storeIds) {
            $company = Workspace::company();
            $data['company_id'] = $company->id;
            $data['slug'] = $this->uniqueSlug($company->id, $data['name']);
            $data['sku'] = $data['sku'] ?: $this->generateSku($company->id);
            $data['created_by'] = Workspace::user()?->id;
            $data['updated_by'] = Workspace::user()?->id;

            if ($image) {
                $data['image_path'] = $image->store('products/'.$company->id, 'public');
            }

            $product = Product::query()->create($data);
            $this->syncVariants($product, $variants);
            $this->syncStores($product, $storeIds);

            if (! empty($data['image_path'])) {
                ProductImage::query()->create([
                    'product_id' => $product->id,
                    'path' => $data['image_path'],
                    'is_primary' => true,
                    'sort_order' => 0,
                ]);
            }

            $this->log($product, 'created', null, $product->toArray(), 'Produit créé');

            return $product->fresh(['category', 'brand', 'supplier', 'variants', 'images']);
        });
    }

    public function update(Product $product, array $data, ?UploadedFile $image = null, array $variants = [], array $storeIds = []): Product
    {
        return DB::transaction(function () use ($product, $data, $image, $variants, $storeIds) {
            $before = $product->toArray();
            $data['updated_by'] = Workspace::user()?->id;

            if (isset($data['name']) && $data['name'] !== $product->name) {
                $data['slug'] = $this->uniqueSlug($product->company_id, $data['name'], $product->id);
            }

            if ($image) {
                if ($product->image_path) {
                    Storage::disk('public')->delete($product->image_path);
                }
                $data['image_path'] = $image->store('products/'.$product->company_id, 'public');
                ProductImage::query()->updateOrCreate(
                    ['product_id' => $product->id, 'is_primary' => true],
                    ['path' => $data['image_path'], 'sort_order' => 0]
                );
            }

            $product->update($data);
            $this->syncVariants($product, $variants);
            $this->syncStores($product, $storeIds);
            $this->log($product, 'updated', $before, $product->fresh()->toArray(), 'Produit modifié');

            return $product->fresh(['category', 'brand', 'supplier', 'variants', 'images']);
        });
    }

    public function softDelete(Product $product): void
    {
        $before = $product->toArray();
        $product->delete();
        $this->log($product, 'deleted', $before, null, 'Suppression logique');
    }

    public function archive(Product $product): Product
    {
        $before = $product->toArray();
        $product->update([
            'status' => 'archived',
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($product, 'archived', $before, $product->fresh()->toArray(), 'Produit archivé');

        return $product->fresh();
    }

    public function restoreStatus(Product $product, string $status = 'active'): Product
    {
        $before = $product->toArray();
        $product->update([
            'status' => $status,
            'updated_by' => Workspace::user()?->id,
        ]);
        $this->log($product, 'status_changed', $before, $product->fresh()->toArray(), 'Statut restauré');

        return $product->fresh();
    }

    public function duplicate(Product $product): Product
    {
        return DB::transaction(function () use ($product) {
            $copy = $product->replicate(['sku', 'slug', 'barcode', 'qr_code']);
            $copy->name = $product->name.' (copie)';
            $copy->sku = $this->generateSku($product->company_id);
            $copy->slug = $this->uniqueSlug($product->company_id, $copy->name);
            $copy->barcode = null;
            $copy->status = 'inactive';
            $copy->created_by = Workspace::user()?->id;
            $copy->updated_by = Workspace::user()?->id;
            $copy->save();

            foreach ($product->variants as $variant) {
                $v = $variant->replicate(['sku', 'barcode']);
                $v->product_id = $copy->id;
                $v->sku = $this->generateSku($product->company_id, 'VAR');
                $v->barcode = null;
                $v->save();
            }

            foreach ($product->stores as $store) {
                $copy->stores()->attach($store->id, [
                    'is_available' => $store->pivot->is_available,
                    'sale_price_override' => $store->pivot->sale_price_override,
                ]);
            }

            $this->log($copy, 'duplicated', null, $copy->toArray(), 'Dupliqué depuis #'.$product->id);

            return $copy->fresh(['category', 'brand', 'supplier', 'variants']);
        });
    }

    protected function syncVariants(Product $product, array $variants): void
    {
        $keepIds = [];

        foreach ($variants as $row) {
            if (! filled($row['name'] ?? null)) {
                continue;
            }

            $payload = [
                'name' => $row['name'],
                'sku' => ($row['sku'] ?? null) ?: $this->generateSku($product->company_id, 'VAR'),
                'barcode' => ($row['barcode'] ?? null) ?: null,
                'sale_price' => (($row['sale_price'] ?? '') !== '' && ($row['sale_price'] ?? null) !== null) ? $row['sale_price'] : null,
                'attributes' => array_filter([
                    'size' => $row['size'] ?? null,
                    'color' => $row['color'] ?? null,
                ]),
                'status' => $row['status'] ?? 'active',
            ];

            if (! empty($row['id'])) {
                $variant = $product->variants()->whereKey($row['id'])->first();
                if ($variant) {
                    $variant->update($payload);
                    $keepIds[] = $variant->id;
                    continue;
                }
            }

            $created = $product->variants()->create($payload);
            $keepIds[] = $created->id;
        }

        $product->variants()->whereNotIn('id', $keepIds)->each(function (ProductVariant $variant) {
            $variant->delete();
        });
    }

    protected function syncStores(Product $product, array $storeIds): void
    {
        $sync = [];
        foreach ($storeIds as $storeId) {
            $sync[(int) $storeId] = ['is_available' => true];
        }
        $product->stores()->sync($sync);
    }

    public function log(Product $product, string $action, ?array $before, ?array $after, ?string $note = null): void
    {
        ProductChangeLog::query()->create([
            'product_id' => $product->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'note' => $note,
        ]);
    }

    public function generateSku(int $companyId, string $prefix = 'PRD'): string
    {
        do {
            $sku = strtoupper($prefix).'-'.Str::upper(Str::random(6));
        } while (Product::query()->where('company_id', $companyId)->where('sku', $sku)->exists()
            || ProductVariant::query()->where('sku', $sku)->exists());

        return $sku;
    }

    public function uniqueSlug(int $companyId, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produit';
        $slug = $base;
        $i = 1;

        while (
            Product::query()
                ->where('company_id', $companyId)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}

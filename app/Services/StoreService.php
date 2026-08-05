<?php

namespace App\Services;

use App\Models\PosSale;
use App\Models\Sale;
use App\Models\StockLevel;
use App\Models\Store;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StoreService
{
    public function assertCompany(Store $store): void
    {
        $company = Workspace::company();
        if (! $company || $store->company_id !== $company->id) {
            abort(404);
        }
    }

    public function create(array $data, ?UploadedFile $logo = null, array $userIds = []): Store
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $logo, $userIds) {
            if (! empty($data['is_default'])) {
                Store::query()->where('company_id', $company->id)->update(['is_default' => false]);
            }

            $store = Store::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'postal_code' => $data['postal_code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'manager_user_id' => $data['manager_user_id'] ?? null,
                'opening_hours' => $this->normalizeHours($data),
                'local_settings' => $data['local_settings'] ?? [],
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);

            if ($logo) {
                $store->update(['logo_path' => $logo->store('stores/'.$store->id, 'public')]);
            }

            $this->syncUsers($store, $userIds, $data['manager_user_id'] ?? null);

            return $store->fresh(['manager', 'users']);
        });
    }

    public function update(Store $store, array $data, ?UploadedFile $logo = null, array $userIds = []): Store
    {
        $this->assertCompany($store);
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $store, $data, $logo, $userIds) {
            if (! empty($data['is_default'])) {
                Store::query()->where('company_id', $company->id)->where('id', '!=', $store->id)->update(['is_default' => false]);
            }

            $payload = [
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'postal_code' => $data['postal_code'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'manager_user_id' => $data['manager_user_id'] ?? null,
                'opening_hours' => $this->normalizeHours($data),
                'notes' => $data['notes'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ];

            if (array_key_exists('local_settings', $data)) {
                $payload['local_settings'] = $data['local_settings'];
            }

            if ($logo) {
                if ($store->logo_path) {
                    Storage::disk('public')->delete($store->logo_path);
                }
                $payload['logo_path'] = $logo->store('stores/'.$store->id, 'public');
            }

            $store->update($payload);
            $this->syncUsers($store, $userIds, $data['manager_user_id'] ?? null);

            return $store->fresh(['manager', 'users']);
        });
    }

    public function deactivate(Store $store): Store
    {
        $this->assertCompany($store);
        $store->update(['is_active' => false, 'is_default' => false]);

        return $store->fresh();
    }

    public function activate(Store $store): Store
    {
        $this->assertCompany($store);
        $store->update(['is_active' => true]);

        return $store->fresh();
    }

    public function delete(Store $store): void
    {
        $this->assertCompany($store);
        $company = Workspace::company();

        if (Store::query()->where('company_id', $company->id)->count() <= 1) {
            throw ValidationException::withMessages(['store' => 'Impossible de supprimer la dernière boutique.']);
        }

        if ($store->is_default) {
            throw ValidationException::withMessages(['store' => 'Désignez une autre boutique par défaut avant de supprimer celle-ci.']);
        }

        if ($store->logo_path) {
            Storage::disk('public')->delete($store->logo_path);
        }

        $store->users()->detach();
        $store->delete();
    }

    public function dashboardStats(): array
    {
        $company = Workspace::company();
        $stores = Workspace::accessibleStores();
        $storeIds = $stores->pluck('id');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $revenueByStore = $this->revenueByStore($storeIds);
        $stockByStore = StockLevel::query()
            ->whereIn('store_id', $storeIds)
            ->selectRaw('store_id, SUM(quantity) as qty')
            ->groupBy('store_id')
            ->pluck('qty', 'store_id');

        $salesTodayByStore = Sale::query()
            ->where('company_id', $company->id)
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw('store_id, COUNT(*) as cnt, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $posTodayByStore = PosSale::query()
            ->where('company_id', $company->id)
            ->whereIn('store_id', $storeIds)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->where('status', 'completed')
            ->selectRaw('store_id, COUNT(*) as cnt, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('store_id')
            ->get()
            ->keyBy('store_id');

        $cards = $stores->map(function (Store $store) use ($revenueByStore, $stockByStore, $salesTodayByStore, $posTodayByStore) {
            $sale = $salesTodayByStore->get($store->id);
            $pos = $posTodayByStore->get($store->id);

            return [
                'store' => $store,
                'revenue' => (float) ($revenueByStore[$store->id] ?? 0),
                'stock_qty' => (float) ($stockByStore[$store->id] ?? 0),
                'sales_today_count' => (int) (($sale->cnt ?? 0) + ($pos->cnt ?? 0)),
                'sales_today_total' => (float) (($sale->total ?? 0) + ($pos->total ?? 0)),
                'users_count' => $store->users_count ?? $store->users()->count(),
            ];
        });

        return [
            'total' => $stores->count(),
            'active' => $stores->where('is_active', true)->count(),
            'inactive' => $stores->where('is_active', false)->count(),
            'revenue_total' => $cards->sum('revenue'),
            'sales_today_total' => $cards->sum('sales_today_total'),
            'cards' => $cards,
            'chart_labels' => $stores->pluck('name')->values()->all(),
            'chart_revenue' => $stores->map(fn (Store $s) => round((float) ($revenueByStore[$s->id] ?? 0), 2))->values()->all(),
            'chart_sales_today' => $stores->map(function (Store $s) use ($salesTodayByStore, $posTodayByStore) {
                return round((float) (($salesTodayByStore->get($s->id)->total ?? 0) + ($posTodayByStore->get($s->id)->total ?? 0)), 2);
            })->values()->all(),
            'chart_stock' => $stores->map(fn (Store $s) => round((float) ($stockByStore[$s->id] ?? 0), 2))->values()->all(),
        ];
    }

    public function enrichMetrics(Collection $stores): Collection
    {
        $ids = $stores->pluck('id');
        $revenue = $this->revenueByStore($ids);
        $stock = StockLevel::query()
            ->whereIn('store_id', $ids)
            ->selectRaw('store_id, SUM(quantity) as qty')
            ->groupBy('store_id')
            ->pluck('qty', 'store_id');

        return $stores->map(function (Store $store) use ($revenue, $stock) {
            $store->setAttribute('metric_revenue', (float) ($revenue[$store->id] ?? 0));
            $store->setAttribute('metric_stock', (float) ($stock[$store->id] ?? 0));
            $store->setAttribute('metric_products', $store->products_count ?? $store->products()->count());

            return $store;
        });
    }

    protected function revenueByStore($storeIds): Collection
    {
        $companyId = Workspace::company()->id;
        $sales = Sale::query()
            ->where('company_id', $companyId)
            ->whereIn('store_id', $storeIds)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw('store_id, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        $pos = PosSale::query()
            ->where('company_id', $companyId)
            ->whereIn('store_id', $storeIds)
            ->where('status', 'completed')
            ->selectRaw('store_id, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('store_id')
            ->pluck('total', 'store_id');

        $merged = collect();
        foreach ($storeIds as $id) {
            $merged[$id] = (float) ($sales[$id] ?? 0) + (float) ($pos[$id] ?? 0);
        }

        return $merged;
    }

    protected function syncUsers(Store $store, array $userIds, ?int $managerId): void
    {
        $company = Workspace::company();
        $valid = User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id))
            ->whereIn('id', array_filter(array_merge($userIds, [$managerId])))
            ->pluck('id')
            ->all();

        $store->users()->sync($valid);
    }

    protected function normalizeHours(array $data): array
    {
        if (! empty($data['opening_hours']) && is_array($data['opening_hours'])) {
            return $data['opening_hours'];
        }

        return [
            'summary' => $data['opening_hours_summary'] ?? null,
        ];
    }
}

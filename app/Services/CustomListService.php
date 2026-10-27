<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CustomList;
use App\Models\DeliveryPlatform;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomListService
{
    public function ensureDefaults(Company|int $company): void
    {
        $companyId = $company instanceof Company ? $company->id : $company;
        if (CustomList::query()->forCompany($companyId)->exists()) {
            return;
        }

        $rows = [
            ['tickets_predefinis', 'Comptoir', 'comptoir', ['group' => 'salle']],
            ['tickets_predefinis', 'Table 1', 'table-1', ['group' => 'salle']],
            ['tickets_predefinis', 'Table 2', 'table-2', ['group' => 'salle']],
            ['tickets_predefinis', 'Emporter', 'emporter', ['group' => 'emporter']],
            ['mode_de_service', 'Sur place', 'dine-in', ['operational_mode' => 'dine_in', 'requires_delivery_agent' => false]],
            ['mode_de_service', 'À emporter', 'takeaway', ['operational_mode' => 'takeaway', 'requires_delivery_agent' => false]],
            ['mode_de_service', 'Livraison', 'delivery', ['operational_mode' => 'delivery', 'requires_delivery_agent' => true]],
            ['mode_de_paiement', 'Espèces', 'cash', ['method' => 'cash', 'timing' => 'immediate', 'required_fields' => []]],
            ['mode_de_paiement', 'Carte', 'card', ['method' => 'card', 'timing' => 'immediate', 'required_fields' => ['transaction_number']]],
            ['mode_de_paiement', 'Mobile', 'mobile', ['method' => 'mobile', 'timing' => 'immediate', 'required_fields' => ['transaction_number']]],
            ['mode_de_paiement', 'Virement', 'bank-transfer', ['method' => 'bank_transfer', 'timing' => 'deferred', 'required_fields' => ['bank_name', 'transaction_number', 'due_date']]],
            ['mode_de_paiement', 'Chèque', 'check', ['method' => 'check', 'timing' => 'deferred', 'required_fields' => ['piece_number', 'bank_name', 'issue_date', 'due_date']]],
            ['mode_de_paiement', 'Crédit', 'credit', ['method' => 'credit', 'timing' => 'deferred', 'required_fields' => ['due_date']]],
            ['taxes', 'TVA 20%', 'tva-20', ['rate' => 20]],
            ['taxes', 'TVA 10%', 'tva-10', ['rate' => 10]],
            ['taxes', 'TVA 0%', 'tva-0', ['rate' => 0]],
            ['remises', 'Remise 5%', 'remise-5', ['discount_type' => 'percent', 'value' => 5]],
            ['remises', 'Remise 10%', 'remise-10', ['discount_type' => 'percent', 'value' => 10]],
            ['remises', 'Fidélité 50 MAD', 'fidelite-50', ['discount_type' => 'amount', 'value' => 50]],
            ['depenses', 'Loyer', 'loyer', ['expense_kind' => 'fixed']],
            ['depenses', 'Fournitures', 'fournitures', ['expense_kind' => 'variable']],
            ['depenses', 'Transport', 'transport', ['expense_kind' => 'variable']],
        ];

        foreach ($rows as $i => [$type, $name, $code, $meta]) {
            CustomList::query()->create([
                'company_id' => $companyId,
                'type' => $type,
                'name' => $name,
                'code' => $code,
                'sort_order' => $i,
                'is_active' => true,
                'is_default' => $i === 0 || in_array($code, ['dine-in', 'cash', 'tva-20'], true),
                'metadata' => $meta,
            ]);
        }
    }

    public function forType(int $companyId, string $type, bool $activeOnly = true): Collection
    {
        return CustomList::query()
            ->forCompany($companyId)
            ->where('type', $type)
            ->when($activeOnly, fn ($q) => $q->where('is_active', true))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * Mirror an active delivery platform into a service-mode custom list.
     * Deactivating the platform deactivates the synced row. Rows are never deleted.
     */
    public function syncPlatformServiceMode(DeliveryPlatform $platform): CustomList
    {
        $code = 'platform-'.$platform->id;

        return CustomList::query()->updateOrCreate(
            [
                'company_id' => $platform->company_id,
                'type' => 'mode_de_service',
                'code' => $code,
            ],
            [
                'name' => $platform->name,
                'is_active' => (bool) $platform->is_active,
                'is_default' => false,
                'metadata' => [
                    'operational_mode' => 'delivery',
                    'requires_delivery_agent' => (bool) $platform->is_delivery_agent,
                    'platform_id' => $platform->id,
                    'platform_code' => $platform->code,
                    'synced_from_platform' => true,
                    'kind' => $platform->kind,
                    'commission_type' => $platform->commission_type,
                    'commission_value' => (float) $platform->commission_value,
                ],
            ]
        );
    }

    public function syncAllPlatforms(int $companyId): int
    {
        $count = 0;
        DeliveryPlatform::query()->forCompany($companyId)->each(function (DeliveryPlatform $platform) use (&$count) {
            $this->syncPlatformServiceMode($platform);
            $count++;
        });

        return $count;
    }

    public function uniqueCode(int $companyId, string $type, string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'liste';
        $code = $base;
        $i = 2;
        while (CustomList::query()
            ->forCompany($companyId)
            ->where('type', $type)
            ->where('code', $code)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists()) {
            $code = $base.'-'.$i;
            $i++;
        }

        return $code;
    }
}

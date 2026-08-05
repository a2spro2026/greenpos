<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Store;
use App\Support\SettingsDefaults;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SettingService
{
    public function getGroup(string $group, ?Company $company = null): array
    {
        $company = $company ?? Workspace::company();
        $defaults = SettingsDefaults::for($group);
        $row = CompanySetting::query()
            ->where('company_id', $company->id)
            ->where('group', $group)
            ->first();

        if (! $row) {
            return $defaults;
        }

        return array_replace_recursive($defaults, $row->payload ?? []);
    }

    public function saveGroup(string $group, array $payload, ?Company $company = null): CompanySetting
    {
        $company = $company ?? Workspace::company();
        if (! array_key_exists($group, CompanySetting::GROUPS)) {
            throw ValidationException::withMessages(['group' => 'Section invalide.']);
        }

        $merged = array_replace_recursive(SettingsDefaults::for($group), $payload);

        return CompanySetting::query()->updateOrCreate(
            ['company_id' => $company->id, 'group' => $group],
            ['payload' => $merged]
        );
    }

    public function updateCompany(array $data, ?UploadedFile $logo = null): Company
    {
        $company = Workspace::company();

        $payload = [
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'activity' => $data['activity'] ?? $company->activity,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? 'Maroc',
            'ice' => $data['ice'] ?? null,
            'if_number' => $data['if_number'] ?? null,
            'rc' => $data['rc'] ?? null,
            'patente' => $data['patente'] ?? null,
            'tax_id' => $data['tax_id'] ?? null,
            'cnss' => $data['cnss'] ?? null,
            'currency' => $data['currency'] ?? $company->currency,
            'timezone' => $data['timezone'] ?? $company->timezone,
        ];

        if ($logo) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }
            $payload['logo_path'] = $logo->store('companies/'.$company->id, 'public');
        }

        $company->update($payload);

        return $company->fresh();
    }

    public function createStore(array $data): Store
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data) {
            if (! empty($data['is_default'])) {
                Store::query()->where('company_id', $company->id)->update(['is_default' => false]);
            }

            return Store::query()->create([
                'company_id' => $company->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);
        });
    }

    public function updateStore(Store $store, array $data): Store
    {
        $company = Workspace::company();
        if ($store->company_id !== $company->id) {
            abort(404);
        }

        return DB::transaction(function () use ($company, $store, $data) {
            if (! empty($data['is_default'])) {
                Store::query()->where('company_id', $company->id)->where('id', '!=', $store->id)->update(['is_default' => false]);
            }

            $store->update([
                'name' => $data['name'],
                'code' => $data['code'] ?? null,
                'city' => $data['city'] ?? null,
                'address' => $data['address'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'is_default' => (bool) ($data['is_default'] ?? false),
            ]);

            return $store->fresh();
        });
    }

    public function deleteStore(Store $store): void
    {
        $company = Workspace::company();
        if ($store->company_id !== $company->id) {
            abort(404);
        }

        if (Store::query()->where('company_id', $company->id)->count() <= 1) {
            throw ValidationException::withMessages(['store' => 'Impossible de supprimer la dernière boutique.']);
        }

        $store->delete();
    }
}

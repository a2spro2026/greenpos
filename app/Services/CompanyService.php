<?php

namespace App\Services;

use App\Models\Company;
use App\Models\PosSale;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Support\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompanyService
{
    public function assertAccessible(Company $company): void
    {
        if (! Workspace::canAccessCompany($company)) {
            abort(404);
        }
    }

    public function create(array $data, ?UploadedFile $logo = null): Company
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        return DB::transaction(function () use ($data, $logo, $user) {
            $company = Company::query()->create([
                'name' => $data['name'],
                'legal_name' => $data['legal_name'] ?? null,
                'activity' => $data['activity'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'website' => $data['website'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'region' => $data['region'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'currency' => strtoupper($data['currency'] ?? 'MAD'),
                'timezone' => $data['timezone'] ?? 'Africa/Casablanca',
                'locale' => $data['locale'] ?? 'fr',
                'status' => 'active',
            ]);

            if ($logo) {
                $company->update(['logo_path' => $logo->store('companies/'.$company->id, 'public')]);
            }

            $isPrimary = $user->companies()->count() === 0;

            $user->companies()->attach($company->id, [
                'role' => 'owner',
                'status' => 'active',
                'is_primary' => $isPrimary,
            ]);

            $store = Store::query()->create([
                'company_id' => $company->id,
                'name' => 'Boutique principale',
                'code' => 'MAIN',
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'Maroc',
                'is_active' => true,
                'is_default' => true,
            ]);

            $user->stores()->syncWithoutDetaching([$store->id]);

            Workspace::set($company, $store);

            return $company->fresh(['stores', 'users']);
        });
    }

    public function update(Company $company, array $data, ?UploadedFile $logo = null): Company
    {
        $this->assertAccessible($company);
        $this->assertOwnerOrAdmin($company);

        $payload = [
            'name' => $data['name'],
            'legal_name' => $data['legal_name'] ?? null,
            'activity' => $data['activity'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'website' => $data['website'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'region' => $data['region'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'country' => $data['country'] ?? 'Maroc',
            'currency' => strtoupper($data['currency'] ?? $company->currency ?? 'MAD'),
            'timezone' => $data['timezone'] ?? $company->timezone,
            'locale' => $data['locale'] ?? $company->locale ?? 'fr',
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

    public function deactivate(Company $company): Company
    {
        $this->assertAccessible($company);
        $this->assertOwnerOrAdmin($company);

        if (Workspace::company()?->id === $company->id) {
            throw ValidationException::withMessages([
                'company' => 'Impossible de désactiver l\'entreprise active. Changez d\'abord de contexte.',
            ]);
        }

        $company->update(['status' => 'inactive', 'archived_at' => null]);

        return $company->fresh();
    }

    public function activate(Company $company): Company
    {
        $this->assertAccessible($company);
        $this->assertOwnerOrAdmin($company);
        $company->update(['status' => 'active', 'archived_at' => null]);

        return $company->fresh();
    }

    public function archive(Company $company): Company
    {
        $this->assertAccessible($company);
        $this->assertOwnerOrAdmin($company);

        if (Workspace::company()?->id === $company->id) {
            throw ValidationException::withMessages([
                'company' => 'Impossible d\'archiver l\'entreprise active. Changez d\'abord de contexte.',
            ]);
        }

        $company->update(['status' => 'archived', 'archived_at' => now()]);

        return $company->fresh();
    }

    public function delete(Company $company): void
    {
        $this->assertAccessible($company);
        $this->assertOwner($company);

        if (Workspace::company()?->id === $company->id) {
            throw ValidationException::withMessages([
                'company' => 'Impossible de supprimer l\'entreprise active.',
            ]);
        }

        if (Workspace::accessibleCompanies()->count() <= 1) {
            throw ValidationException::withMessages([
                'company' => 'Impossible de supprimer votre dernière entreprise.',
            ]);
        }

        if ($company->logo_path) {
            Storage::disk('public')->delete($company->logo_path);
        }

        $company->delete(); // soft delete
    }

    public function setPrimary(Company $company): void
    {
        $this->assertAccessible($company);
        $user = Auth::user();

        DB::transaction(function () use ($user, $company) {
            $user->companies()->newPivotStatement()
                ->where('user_id', $user->id)
                ->update(['is_primary' => false]);

            $user->companies()->updateExistingPivot($company->id, ['is_primary' => true]);
        });
    }

    public function dashboardStats(): array
    {
        $companies = Workspace::accessibleCompanies();
        $ids = $companies->pluck('id');

        $storesCount = Store::query()->whereIn('company_id', $ids)->count();
        $usersCount = DB::table('company_user')
            ->whereIn('company_id', $ids)
            ->distinct('user_id')
            ->count('user_id');

        $revenue = $this->revenueByCompany($ids);

        $cards = $companies->map(function (Company $company) use ($revenue) {
            $company->loadCount(['stores', 'users']);

            return [
                'company' => $company,
                'revenue' => (float) ($revenue[$company->id] ?? 0),
                'stores_count' => $company->stores_count,
                'users_count' => $company->users_count,
            ];
        });

        return [
            'total' => $companies->count(),
            'active' => $companies->where('status', 'active')->count(),
            'archived' => $companies->where('status', 'archived')->count(),
            'inactive' => $companies->where('status', 'inactive')->count(),
            'stores' => $storesCount,
            'users' => $usersCount,
            'revenue_total' => $cards->sum('revenue'),
            'cards' => $cards,
            'chart_labels' => $companies->pluck('name')->values()->all(),
            'chart_revenue' => $companies->map(fn (Company $c) => round((float) ($revenue[$c->id] ?? 0), 2))->values()->all(),
            'chart_stores' => $companies->map(fn (Company $c) => (int) Store::query()->where('company_id', $c->id)->count())->values()->all(),
        ];
    }

    public function enrichMetrics(Collection $companies): Collection
    {
        $ids = $companies->pluck('id');
        $revenue = $this->revenueByCompany($ids);

        return $companies->map(function (Company $company) use ($revenue) {
            $company->setAttribute('metric_revenue', (float) ($revenue[$company->id] ?? 0));

            return $company;
        });
    }

    protected function revenueByCompany($companyIds): Collection
    {
        $sales = Sale::query()
            ->whereIn('company_id', $companyIds)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->selectRaw('company_id, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $pos = PosSale::query()
            ->whereIn('company_id', $companyIds)
            ->where('status', 'completed')
            ->selectRaw('company_id, COALESCE(SUM(total_ttc),0) as total')
            ->groupBy('company_id')
            ->pluck('total', 'company_id');

        $merged = collect();
        foreach ($companyIds as $id) {
            $merged[$id] = (float) ($sales[$id] ?? 0) + (float) ($pos[$id] ?? 0);
        }

        return $merged;
    }

    protected function assertOwnerOrAdmin(Company $company): void
    {
        $role = Auth::user()?->roleIn($company);
        if (! in_array($role, ['owner', 'admin', 'super_admin'], true) && ! Workspace::can('companies.update')) {
            abort(403);
        }
    }

    protected function assertOwner(Company $company): void
    {
        $role = Auth::user()?->roleIn($company);
        if (! in_array($role, ['owner', 'super_admin'], true) && ! Workspace::can('companies.delete')) {
            abort(403);
        }
    }
}

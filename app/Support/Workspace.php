<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class Workspace
{
    protected static ?array $permissionCache = null;

    public static function company(): ?Company
    {
        $user = Auth::user();
        if (! $user) {
            return null;
        }

        $accessible = self::accessibleCompanies();
        $id = session('workspace_company_id');

        $company = $id
            ? $accessible->firstWhere('id', (int) $id)
            : null;

        if (! $company) {
            $primaryId = $user->companies()
                ->wherePivot('is_primary', true)
                ->wherePivot('status', 'active')
                ->value('companies.id');

            $company = $primaryId
                ? $accessible->firstWhere('id', (int) $primaryId)
                : ($accessible->first(fn (Company $c) => $c->status === 'active') ?? $accessible->first());
        }

        if ($company && (int) session('workspace_company_id') !== (int) $company->id) {
            session(['workspace_company_id' => $company->id]);
        }

        return $company;
    }

    /**
     * Entreprises accessibles pour l'utilisateur courant (membership active).
     *
     * @return \Illuminate\Support\Collection<int, Company>
     */
    public static function accessibleCompanies()
    {
        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        return $user->companies()
            ->wherePivot('status', 'active')
            ->orderBy('companies.name')
            ->get();
    }

    /**
     * Entreprises opérationnelles (actives) pour le sélecteur de contexte.
     */
    public static function switchableCompanies()
    {
        return self::accessibleCompanies()->where('status', 'active')->values();
    }

    public static function canAccessCompany(Company|int $company): bool
    {
        $id = $company instanceof Company ? $company->id : $company;

        return self::accessibleCompanies()->contains('id', $id);
    }

    public static function canAccessMultipleCompanies(): bool
    {
        if (self::accessibleCompanies()->count() > 1) {
            return true;
        }

        return self::can('scope.multi_company') || self::can('companies.create');
    }

    public static function switchCompany(Company $company, ?Store $store = null): void
    {
        if (! self::canAccessCompany($company)) {
            abort(403, 'Entreprise non autorisée.');
        }

        if ($company->status === 'archived') {
            abort(403, 'Cette entreprise est archivée.');
        }

        self::set($company, $store);
    }

    public static function store(): ?Store
    {
        $company = self::company();
        if (! $company) {
            return null;
        }

        $accessible = self::accessibleStores();
        $id = session('workspace_store_id');

        $store = $id
            ? $accessible->firstWhere('id', (int) $id)
            : null;

        if (! $store) {
            $store = $accessible->firstWhere('is_default', true) ?? $accessible->first();
        }

        if ($store && (int) session('workspace_store_id') !== (int) $store->id) {
            session(['workspace_store_id' => $store->id]);
        }

        return $store;
    }

    /**
     * Boutiques accessibles pour l'utilisateur courant (selon scope + assignations).
     *
     * @return \Illuminate\Support\Collection<int, Store>
     */
    public static function accessibleStores()
    {
        $company = self::company();
        if (! $company) {
            return collect();
        }

        $query = Store::query()->forCompany($company->id)->orderByDesc('is_default')->orderBy('name');

        if (self::canAccessAllStores()) {
            return $query->get();
        }

        $user = Auth::user();
        if (! $user) {
            return collect();
        }

        $assignedIds = $user->stores()->where('company_id', $company->id)->pluck('stores.id');
        if ($assignedIds->isEmpty()) {
            // Fallback: première boutique active si aucune assignation
            return $query->where('is_active', true)->limit(1)->get();
        }

        return $query->whereIn('id', $assignedIds)->get();
    }

    public static function canAccessAllStores(): bool
    {
        $role = self::role();
        if (in_array($role, ['owner', 'super_admin', 'admin'], true)) {
            return true;
        }

        return self::can('scope.all_stores');
    }

    public static function canAccessStore(Store|int $store): bool
    {
        $id = $store instanceof Store ? $store->id : $store;

        return self::accessibleStores()->contains('id', $id);
    }

    public static function switchStore(Store $store): void
    {
        $company = self::company();
        if (! $company || $store->company_id !== $company->id || ! self::canAccessStore($store)) {
            abort(403, 'Boutique non autorisée.');
        }

        session(['workspace_store_id' => $store->id]);
        // Mode filtre: boutique active (pas "toutes")
        session(['workspace_store_filter' => 'store']);
    }

    /**
     * Filtre effectif pour les listes (null = toutes les boutiques accessibles).
     */
    public static function storeFilterId(): ?int
    {
        if (session('workspace_store_filter') === 'all' && self::canAccessAllStores()) {
            return null;
        }

        return self::store()?->id;
    }

    /**
     * IDs de boutiques pour les requêtes (une boutique ou toutes les accessibles).
     *
     * @return array<int>
     */
    public static function storeScopeIds(): array
    {
        $filterId = self::storeFilterId();
        if ($filterId) {
            return [$filterId];
        }

        return self::accessibleStores()->pluck('id')->all();
    }

    public static function setStoreFilterAll(): void
    {
        if (! self::canAccessAllStores()) {
            abort(403);
        }
        session(['workspace_store_filter' => 'all']);
    }

    public static function role(): string
    {
        $user = Auth::user();
        $company = self::company();

        return $user?->roleIn($company) ?? 'owner';
    }

    public static function set(Company $company, ?Store $store = null): void
    {
        session([
            'workspace_company_id' => $company->id,
            'workspace_store_id' => $store?->id ?? $company->stores()->value('id'),
            'workspace_store_filter' => 'store',
        ]);
        self::$permissionCache = null;
    }

    /** Clear ERP workspace (used when leaving Super Admin impersonation). */
    public static function clear(): void
    {
        session()->forget([
            'workspace_company_id',
            'workspace_store_id',
            'workspace_store_filter',
        ]);
        self::$permissionCache = null;
    }

    public static function can(string $ability): bool
    {
        try {
            if (! app(\App\Services\ModuleManagerService::class)->allowsAbility($ability)) {
                return false;
            }
        } catch (\Throwable) {
            // Module manager unavailable — fall through to RBAC
        }

        return self::canIgnoringModules($ability);
    }

    /**
     * RBAC / role check without SaaS module gate (avoids recursion).
     */
    public static function canIgnoringModules(string $ability): bool
    {
        $role = self::role();

        // Owner always has full company access
        if ($role === 'owner' || $role === 'super_admin') {
            return true;
        }

        // DB-driven RBAC when tables are ready
        if (self::rbacReady()) {
            $keys = self::cachedPermissionKeys($role);
            if ($keys !== null) {
                if (in_array($ability, $keys, true)) {
                    return true;
                }
                // Soft aliases for matrix actions vs legacy controller keys
                $aliases = self::abilityAliases($ability);
                foreach ($aliases as $alias) {
                    if (in_array($alias, $keys, true)) {
                        return true;
                    }
                }

                return false;
            }
        }

        return self::legacyCan($role, $ability);
    }

    public static function user(): ?User
    {
        return Auth::user();
    }

    public static function clearPermissionCache(): void
    {
        self::$permissionCache = null;
    }

    protected static function rbacReady(): bool
    {
        try {
            return Schema::hasTable('permissions') && Schema::hasTable('roles') && Permission::query()->exists();
        } catch (\Throwable) {
            return false;
        }
    }

    protected static function cachedPermissionKeys(string $slug): ?array
    {
        $companyId = self::company()?->id;
        if (! $companyId) {
            return null;
        }

        $cacheKey = $companyId.':'.$slug;
        if (self::$permissionCache !== null && array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        try {
            $keys = app(RoleService::class)->permissionKeysForSlug($slug, $companyId);
            self::$permissionCache[$cacheKey] = $keys;

            return $keys;
        } catch (\Throwable) {
            return null;
        }
    }

    protected static function abilityAliases(string $ability): array
    {
        // Map some controller abilities to matrix equivalents if needed
        return match ($ability) {
            'stock.move', 'stock.adjust', 'stock.inventory' => ['stock.update', 'stock.create', $ability],
            'pos.sell' => ['pos.create', 'pos.sell'],
            'purchases.receive' => ['purchases.validate', 'purchases.receive'],
            'sales.return' => ['sales.cancel', 'sales.return'],
            default => [$ability],
        };
    }

    protected static function legacyCan(string $role, string $ability): bool
    {
        $matrix = [
            'products.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'accountant', 'sales'],
            'products.create' => ['owner', 'admin', 'manager'],
            'products.update' => ['owner', 'admin', 'manager'],
            'products.delete' => ['owner', 'admin', 'manager'],
            'products.archive' => ['owner', 'admin', 'manager'],
            'products.duplicate' => ['owner', 'admin', 'manager'],
            'products.import' => ['owner', 'admin', 'manager'],
            'products.export' => ['owner', 'admin', 'manager', 'accountant'],
            'products.view_purchase_price' => ['owner', 'admin', 'manager', 'accountant', 'storekeeper'],
            'products.manage_images' => ['owner', 'admin', 'manager'],

            'stock.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales'],
            'stock.move' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.adjust' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.inventory' => ['owner', 'admin', 'manager', 'storekeeper'],
            'stock.export' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant'],
            'stock.valuation' => ['owner', 'admin', 'manager', 'accountant', 'storekeeper'],

            'purchases.view' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant'],
            'purchases.create' => ['owner', 'admin', 'manager'],
            'purchases.update' => ['owner', 'admin', 'manager'],
            'purchases.cancel' => ['owner', 'admin', 'manager'],
            'purchases.receive' => ['owner', 'admin', 'manager', 'storekeeper'],
            'purchases.export' => ['owner', 'admin', 'manager', 'accountant', 'storekeeper'],
            'purchases.print' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant'],

            'suppliers.view' => ['owner', 'admin', 'manager', 'storekeeper', 'accountant', 'sales'],
            'suppliers.create' => ['owner', 'admin', 'manager'],
            'suppliers.update' => ['owner', 'admin', 'manager'],
            'suppliers.delete' => ['owner', 'admin', 'manager'],
            'suppliers.export' => ['owner', 'admin', 'manager', 'accountant'],
            'suppliers.print' => ['owner', 'admin', 'manager', 'accountant', 'storekeeper'],
            'suppliers.stats' => ['owner', 'admin', 'manager', 'accountant'],

            'customers.view' => ['owner', 'admin', 'manager', 'cashier', 'sales', 'accountant'],
            'customers.create' => ['owner', 'admin', 'manager', 'sales'],
            'customers.update' => ['owner', 'admin', 'manager', 'sales'],
            'customers.delete' => ['owner', 'admin', 'manager'],
            'customers.export' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'customers.print' => ['owner', 'admin', 'manager', 'sales', 'accountant'],
            'customers.stats' => ['owner', 'admin', 'manager', 'accountant', 'sales'],

            'pos.view' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.sell' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.open' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.close' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.hold' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.cancel' => ['owner', 'admin', 'manager'],
            'pos.reprint' => ['owner', 'admin', 'manager', 'cashier'],
            'pos.history' => ['owner', 'admin', 'manager', 'cashier', 'accountant'],

            'invoices.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.create' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.update' => ['owner', 'admin', 'manager', 'accountant'],
            'invoices.delete' => ['owner', 'admin', 'manager'],
            'invoices.cancel' => ['owner', 'admin', 'manager', 'accountant'],
            'invoices.export' => ['owner', 'admin', 'manager', 'accountant'],
            'invoices.print' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.pdf' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'invoices.send' => ['owner', 'admin', 'manager', 'accountant', 'sales'],

            'quotes.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'quotes.create' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.update' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.delete' => ['owner', 'admin', 'manager'],
            'quotes.export' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'quotes.print' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'quotes.convert' => ['owner', 'admin', 'manager', 'sales'],
            'quotes.send' => ['owner', 'admin', 'manager', 'sales'],

            'sales.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'sales.create' => ['owner', 'admin', 'manager', 'sales'],
            'sales.update' => ['owner', 'admin', 'manager', 'sales'],
            'sales.cancel' => ['owner', 'admin', 'manager'],
            'sales.return' => ['owner', 'admin', 'manager'],
            'sales.export' => ['owner', 'admin', 'manager', 'accountant'],
            'sales.print' => ['owner', 'admin', 'manager', 'accountant', 'sales'],

            'reports.view' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'reports.export' => ['owner', 'admin', 'manager', 'accountant'],
            'reports.print' => ['owner', 'admin', 'manager', 'accountant', 'sales'],
            'reports.financial' => ['owner', 'admin', 'accountant'],
            'reports.advanced' => ['owner', 'admin', 'manager'],

            'users.view' => ['owner', 'admin', 'manager'],
            'users.create' => ['owner', 'admin'],
            'users.update' => ['owner', 'admin'],
            'users.delete' => ['owner', 'admin'],
            'users.reset' => ['owner', 'admin'],
            'users.invite' => ['owner', 'admin'],
            'users.export' => ['owner', 'admin', 'manager'],
            'users.print' => ['owner', 'admin', 'manager'],

            'roles.view' => ['owner', 'admin'],
            'roles.create' => ['owner', 'admin'],
            'roles.update' => ['owner', 'admin'],
            'roles.delete' => ['owner', 'admin'],
            'roles.export' => ['owner', 'admin'],
            'roles.print' => ['owner', 'admin'],

            'settings.view' => ['owner', 'admin'],
            'settings.update' => ['owner', 'admin'],

            'stores.view' => ['owner', 'admin', 'manager'],
            'stores.create' => ['owner', 'admin'],
            'stores.update' => ['owner', 'admin', 'manager'],
            'stores.delete' => ['owner', 'admin'],
            'stores.export' => ['owner', 'admin', 'manager'],
            'stores.print' => ['owner', 'admin', 'manager'],
            'stores.switch' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant'],

            'companies.view' => ['owner', 'admin', 'manager'],
            'companies.create' => ['owner', 'admin'],
            'companies.update' => ['owner', 'admin'],
            'companies.delete' => ['owner'],
            'companies.export' => ['owner', 'admin', 'manager'],
            'companies.print' => ['owner', 'admin', 'manager'],
            'companies.switch' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant'],
            'companies.archive' => ['owner', 'admin'],

            'notifications.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant', 'employee'],
            'notifications.create' => ['owner', 'admin', 'manager'],
            'notifications.update' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant', 'employee'],
            'notifications.delete' => ['owner', 'admin', 'manager'],
            'notifications.archive' => ['owner', 'admin', 'manager', 'accountant'],
            'notifications.preferences' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant', 'employee'],

            'documents.view' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant', 'employee'],
            'documents.create' => ['owner', 'admin', 'manager', 'sales', 'storekeeper', 'accountant'],
            'documents.update' => ['owner', 'admin', 'manager'],
            'documents.delete' => ['owner', 'admin', 'manager'],
            'documents.export' => ['owner', 'admin', 'manager', 'accountant'],
            'documents.download' => ['owner', 'admin', 'manager', 'cashier', 'storekeeper', 'sales', 'accountant', 'employee'],
            'documents.archive' => ['owner', 'admin', 'manager'],
            'documents.folders' => ['owner', 'admin', 'manager'],
        ];

        return in_array($role, $matrix[$ability] ?? [], true);
    }
}

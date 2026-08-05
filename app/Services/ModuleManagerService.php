<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\SaasPlan;
use App\Models\SaasTenant;
use App\Support\ModuleCatalog;
use App\Support\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class ModuleManagerService
{
    /** @var array<int, array<string, bool>> */
    protected static array $cache = [];

    public function bootstrapPlans(): void
    {
        app(SaasService::class)->ensurePlans();

        foreach (SaasPlan::query()->get() as $plan) {
            $defaults = ModuleCatalog::defaultModulesForPlan($plan->code);
            $current = $plan->modules ?? [];
            // Merge missing catalog keys defaults when plan modules look outdated
            if (count($current) < 5 || ! in_array('dashboard', $current, true)) {
                $plan->update(['modules' => $defaults]);
            }
        }
    }

    public function syncCompanyFromPlan(Company $company, ?SaasPlan $plan = null): void
    {
        if (! Schema::hasTable('company_modules')) {
            return;
        }

        $plan ??= $this->planForCompany($company);
        $allowed = $plan
            ? array_values(array_unique(array_merge(
                ModuleCatalog::ALWAYS_ON,
                $plan->modules ?? ModuleCatalog::defaultModulesForPlan($plan->code)
            )))
            : array_merge(ModuleCatalog::ALWAYS_ON, ModuleCatalog::keys()); // demo: all

        // Always include always-on
        foreach (ModuleCatalog::ALWAYS_ON as $key) {
            if (! in_array($key, $allowed, true)) {
                $allowed[] = $key;
            }
        }

        foreach (ModuleCatalog::keys() as $key) {
            $enabled = in_array($key, $allowed, true);
            CompanyModule::query()->updateOrCreate(
                ['company_id' => $company->id, 'module_key' => $key],
                [
                    'is_enabled' => $enabled,
                    'source' => 'plan',
                    'enabled_at' => $enabled ? now() : null,
                ]
            );
        }

        unset(self::$cache[$company->id]);
    }

    public function ensureSynced(?Company $company = null): void
    {
        $company ??= Workspace::company();
        if (! $company || ! Schema::hasTable('company_modules')) {
            return;
        }

        $count = CompanyModule::query()->where('company_id', $company->id)->count();
        if ($count === 0) {
            $this->syncCompanyFromPlan($company);

            return;
        }

        // Register newly catalogued modules without altering existing toggles
        $existing = CompanyModule::query()
            ->where('company_id', $company->id)
            ->pluck('module_key')
            ->all();
        $missing = array_diff(ModuleCatalog::keys(), $existing);
        if ($missing === []) {
            return;
        }

        $plan = $this->planForCompany($company);
        $planDefaults = ModuleCatalog::defaultModulesForPlan($plan?->code ?? 'default');
        $allowed = array_values(array_unique(array_merge(
            ModuleCatalog::ALWAYS_ON,
            $plan?->modules ?? [],
            $planDefaults
        )));

        foreach ($missing as $key) {
            $enabled = in_array($key, $allowed, true);
            CompanyModule::query()->create([
                'company_id' => $company->id,
                'module_key' => $key,
                'is_enabled' => $enabled,
                'source' => 'plan',
                'enabled_at' => $enabled ? now() : null,
            ]);
        }

        unset(self::$cache[$company->id]);
    }

    public function planForCompany(?Company $company = null): ?SaasPlan
    {
        $company ??= Workspace::company();
        if (! $company || ! Schema::hasTable('saas_tenants')) {
            return null;
        }

        $tenant = SaasTenant::query()
            ->where('company_id', $company->id)
            ->whereNull('archived_at')
            ->latest('id')
            ->first();

        if (! $tenant) {
            return null;
        }

        $sub = $tenant->subscriptions()
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->latest()
            ->with('plan')
            ->first();

        return $sub?->plan;
    }

    public function isEnabled(string $moduleKey, ?Company $company = null): bool
    {
        if (in_array($moduleKey, ModuleCatalog::ALWAYS_ON, true)) {
            return true;
        }

        $company ??= Workspace::company();
        if (! $company) {
            return false;
        }

        if (! Schema::hasTable('company_modules')) {
            return true;
        }

        $this->ensureSynced($company);
        $map = $this->enabledMap($company);

        // No SaaS tenant → open (demo workspace)
        if ($this->planForCompany($company) === null && empty($map)) {
            return true;
        }

        return (bool) ($map[$moduleKey] ?? false);
    }

    /** @return array<string, bool> */
    public function enabledMap(?Company $company = null): array
    {
        $company ??= Workspace::company();
        if (! $company || ! Schema::hasTable('company_modules')) {
            return [];
        }

        if (isset(self::$cache[$company->id])) {
            return self::$cache[$company->id];
        }

        $this->ensureSynced($company);

        $map = CompanyModule::query()
            ->where('company_id', $company->id)
            ->pluck('is_enabled', 'module_key')
            ->map(fn ($v) => (bool) $v)
            ->all();

        self::$cache[$company->id] = $map;

        return $map;
    }

    public function allowsAbility(string $ability): bool
    {
        $module = ModuleCatalog::moduleForAbility($ability);
        if (! $module) {
            return true;
        }

        return $this->isEnabled($module);
    }

    /**
     * Catalog cards for Module Store UI.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function catalogForCompany(?Company $company = null, ?string $search = null, ?string $category = null, ?string $status = null): Collection
    {
        $company ??= Workspace::company();
        $this->ensureSynced($company);
        $map = $this->enabledMap($company);
        $plan = $this->planForCompany($company);
        $planModules = $plan?->modules ?? ModuleCatalog::keys();
        $openPlan = $plan === null;

        return collect(ModuleCatalog::all())
            ->map(fn (array $meta, string $key) => $this->presentModule($key, $meta, $map, $planModules, $openPlan))
            ->when($search, function (Collection $c) use ($search) {
                $q = mb_strtolower($search);

                return $c->filter(fn ($m) => str_contains(
                    mb_strtolower($m['name'].' '.$m['description'].' '.$m['category'].' '.$m['key']),
                    $q
                ));
            })
            ->when($category && $category !== 'Tous', fn (Collection $c) => $c->where('category', $category))
            ->when($status === 'active', fn (Collection $c) => $c->where('is_enabled', true))
            ->when($status === 'inactive', fn (Collection $c) => $c->where('is_enabled', false))
            ->when($status === 'premium', fn (Collection $c) => $c->filter(fn ($m) => $m['is_premium'] || ! $m['in_plan']))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function detailForCompany(string $key, ?Company $company = null): ?array
    {
        $meta = ModuleCatalog::get($key);
        if (! $meta) {
            return null;
        }

        $company ??= Workspace::company();
        $this->ensureSynced($company);
        $map = $this->enabledMap($company);
        $plan = $this->planForCompany($company);
        $planModules = $plan?->modules ?? ModuleCatalog::keys();
        $presented = $this->presentModule($key, $meta, $map, $planModules, $plan === null);

        $related = collect($presented['related'] ?? [])
            ->map(fn ($rel) => ModuleCatalog::get($rel))
            ->filter()
            ->map(function (array $rel) use ($map, $planModules, $plan) {
                return $this->presentModule($rel['key'], $rel, $map, $planModules, $plan === null);
            })
            ->values()
            ->all();

        $prereqs = collect($presented['prerequisites'] ?? [])
            ->map(fn ($p) => ModuleCatalog::get($p))
            ->filter()
            ->values()
            ->all();

        return array_merge($presented, [
            'related_modules' => $related,
            'prerequisite_modules' => $prereqs,
            'icon_path' => ModuleCatalog::iconPaths()[$presented['icon'] ?? 'modules'] ?? ModuleCatalog::iconPaths()['modules'],
        ]);
    }

    /**
     * @return array{installed: int, available: int, premium: int, updated: int, total: int}
     */
    public function storeStats(?Company $company = null): array
    {
        $all = $this->catalogForCompany($company);

        return [
            'total' => $all->count(),
            'installed' => $all->where('is_enabled', true)->count(),
            'available' => $all->where('is_enabled', false)->where('coming_soon', false)->count(),
            'premium' => $all->filter(fn ($m) => $m['is_premium'] || ! $m['in_plan'])->count(),
            'updated' => $all->filter(fn ($m) => in_array('updated', $m['badges'] ?? [], true) || in_array('nouveau', $m['badges'] ?? [], true))->count(),
        ];
    }

    /**
     * Toggle module for company (within plan). Always-on modules cannot be disabled.
     */
    public function toggleModule(string $key, Company $company, bool $enable): CompanyModule
    {
        if (! ModuleCatalog::get($key)) {
            abort(404, 'Module introuvable.');
        }

        if (in_array($key, ModuleCatalog::ALWAYS_ON, true) && ! $enable) {
            abort(422, 'Ce module système ne peut pas être désactivé.');
        }

        $this->ensureSynced($company);
        $plan = $this->planForCompany($company);
        $planModules = $plan?->modules ?? ModuleCatalog::keys();
        $inPlan = in_array($key, ModuleCatalog::ALWAYS_ON, true)
            || in_array($key, $planModules, true)
            || $plan === null;

        if ($enable && ! $inPlan) {
            abort(403, 'Ce module nécessite une offre supérieure.');
        }

        $meta = ModuleCatalog::get($key);
        if ($enable && ($meta['coming_soon'] ?? false)) {
            abort(422, 'Ce module sera bientôt disponible.');
        }

        $row = CompanyModule::query()->updateOrCreate(
            ['company_id' => $company->id, 'module_key' => $key],
            [
                'is_enabled' => $enable,
                'source' => 'manual',
                'enabled_at' => $enable ? now() : null,
            ]
        );

        unset(self::$cache[$company->id]);

        return $row;
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  array<string, bool>  $map
     * @param  list<string>  $planModules
     * @return array<string, mixed>
     */
    protected function presentModule(string $key, array $meta, array $map, array $planModules, bool $openPlan): array
    {
        $enabled = (bool) ($map[$key] ?? in_array($key, ModuleCatalog::ALWAYS_ON, true));
        $inPlan = in_array($key, ModuleCatalog::ALWAYS_ON, true)
            || in_array($key, $planModules, true)
            || $openPlan;
        $comingSoon = (bool) ($meta['coming_soon'] ?? false);
        $premiumFlag = (bool) ($meta['premium'] ?? false);
        $isPremium = $premiumFlag || ! $inPlan;
        $alwaysOn = in_array($key, ModuleCatalog::ALWAYS_ON, true);

        $badges = [];
        if ($enabled) {
            $badges[] = 'installe';
        } elseif ($comingSoon) {
            $badges[] = 'bientot';
        } elseif (! $inPlan || $premiumFlag) {
            $badges[] = 'premium';
        } else {
            $badges[] = 'disponible';
        }
        foreach ($meta['badges'] ?? [] as $b) {
            if (! in_array($b, $badges, true)) {
                $badges[] = $b;
            }
        }

        $action = 'activate';
        if ($comingSoon) {
            $action = 'soon';
        } elseif (! $inPlan) {
            $action = 'upgrade';
        } elseif ($enabled) {
            $action = $alwaysOn ? 'locked' : 'deactivate';
        }

        return array_merge($meta, [
            'key' => $key,
            'is_enabled' => $enabled,
            'in_plan' => $inPlan,
            'is_premium' => $isPremium,
            'always_on' => $alwaysOn,
            'store_badges' => $badges,
            'action' => $action,
            'status_label' => $comingSoon
                ? 'Bientôt'
                : ($enabled ? 'Installé' : ($inPlan ? 'Disponible' : 'Premium')),
            'icon_path' => ModuleCatalog::iconPaths()[$meta['icon'] ?? 'modules'] ?? ModuleCatalog::iconPaths()['modules'],
            'developer' => $meta['developer'] ?? 'GreenPOS',
            'rating' => (float) ($meta['rating'] ?? 4.5),
            'installs' => (int) ($meta['installs'] ?? 0),
            'compatibility' => $meta['compatibility'] ?? ['Starter', 'Business', 'Enterprise'],
        ]);
    }

    /**
     * Sidebar nav groups filtered by plan + permission.
     *
     * @return list<array{label: string, items: list<array<string, mixed>>}>
     */
    public function sidebarNav(): array
    {
        $groups = [];
        $order = ['Pilotage', 'Ventes', 'Catalogue', 'Relation Client', 'Finance', 'Administration'];

        foreach (ModuleCatalog::all() as $key => $meta) {
            if (empty($meta['nav_group']) || empty($meta['route']) || empty($meta['nav_label'])) {
                continue;
            }
            if (! empty($meta['coming_soon'])) {
                continue;
            }
            if (! $this->isEnabled($key)) {
                continue;
            }
            if (! empty($meta['permission']) && ! $this->roleAllows($meta['permission'])) {
                continue;
            }

            if (! Route::has($meta['route'])) {
                continue;
            }

            $group = $meta['nav_group'];
            $groups[$group][] = [
                'key' => $key,
                'label' => $meta['nav_label'],
                'route' => $meta['route'],
                'route_is' => $meta['route_is'] ?? $meta['route'],
                'icon' => $meta['icon'],
            ];
        }

        $result = [];
        foreach ($order as $label) {
            if (! empty($groups[$label])) {
                $result[] = ['label' => $label, 'items' => $groups[$label]];
                unset($groups[$label]);
            }
        }
        foreach ($groups as $label => $items) {
            $result[] = ['label' => $label, 'items' => $items];
        }

        return $result;
    }

    /**
     * Role permission check without module gate (prevents recursion).
     */
    public function roleAllows(string $ability): bool
    {
        $role = Workspace::role();
        if ($role === 'owner' || $role === 'super_admin') {
            return true;
        }

        // Temporarily check via reflection of legacy — call Workspace internal by duplicating owner check
        // Use Gate without module: peek legacy through a flag
        return Workspace::canIgnoringModules($ability);
    }

    public function updatePlanModules(SaasPlan $plan, array $modules): SaasPlan
    {
        $valid = array_values(array_intersect($modules, ModuleCatalog::keys()));
        foreach (ModuleCatalog::ALWAYS_ON as $key) {
            if (! in_array($key, $valid, true)) {
                $valid[] = $key;
            }
        }
        $plan->update(['modules' => $valid]);

        // Re-sync all companies on this plan
        $tenantIds = $plan->subscriptions()
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->pluck('saas_tenant_id');

        SaasTenant::query()->whereIn('id', $tenantIds)->whereNotNull('company_id')->get()
            ->each(function (SaasTenant $t) use ($plan) {
                if ($t->company) {
                    $this->syncCompanyFromPlan($t->company, $plan);
                }
            });

        return $plan->fresh();
    }

    public static function clearCache(?int $companyId = null): void
    {
        if ($companyId) {
            unset(self::$cache[$companyId]);
        } else {
            self::$cache = [];
        }
    }
}

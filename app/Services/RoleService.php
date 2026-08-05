<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleLog;
use App\Models\User;
use App\Support\PermissionCatalog;
use App\Support\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RoleService
{
    public function syncCatalog(): void
    {
        foreach (PermissionCatalog::allDefinitions() as $def) {
            Permission::query()->updateOrCreate(
                ['key' => $def['key']],
                [
                    'module' => $def['module'],
                    'action' => $def['action'],
                    'label' => $def['label'],
                    'description' => $def['description'] ?? null,
                    'group' => $def['group'] ?? 'modules',
                    'sort_order' => $def['sort_order'] ?? 0,
                ]
            );
        }
    }

    public function ensureSystemRoles(): void
    {
        $this->syncCatalog();
        $permissionIds = Permission::query()->pluck('id', 'key');
        $defaults = PermissionCatalog::defaultRolePermissions();

        foreach (PermissionCatalog::defaultRoles() as $roleDef) {
            $role = Role::query()->updateOrCreate(
                ['company_id' => null, 'slug' => $roleDef['slug']],
                [
                    'name' => $roleDef['name'],
                    'description' => $roleDef['description'],
                    'color' => $roleDef['color'],
                    'is_system' => $roleDef['is_system'],
                    'is_super' => $roleDef['is_super'],
                    'is_default' => $roleDef['is_default'],
                ]
            );

            $keys = $defaults[$roleDef['slug']] ?? [];
            $ids = collect($keys)->map(fn ($k) => $permissionIds[$k] ?? null)->filter()->values()->all();
            $role->permissions()->sync($ids);
        }

        $this->forgetPermissionCache();
    }

    public function create(array $data, array $permissionKeys = []): Role
    {
        $company = Workspace::company();

        return DB::transaction(function () use ($company, $data, $permissionKeys) {
            $slug = $data['slug'] ?? Str::slug($data['name']);
            $slug = $this->uniqueSlug($company->id, $slug);

            $role = Role::query()->create([
                'company_id' => $company->id,
                'slug' => $slug,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? 'slate',
                'is_system' => false,
                'is_super' => false,
                'is_default' => false,
            ]);

            $this->syncPermissions($role, $permissionKeys);
            $this->log($role, 'created', 'Rôle créé.');
            $this->forgetPermissionCache($company->id);

            return $role->fresh('permissions');
        });
    }

    public function update(Role $role, array $data, ?array $permissionKeys = null): Role
    {
        $company = Workspace::company();
        $this->ensureAccessible($role, $company);

        if ($role->is_super) {
            throw ValidationException::withMessages(['role' => 'Le Super Administrateur ne peut pas être modifié.']);
        }

        return DB::transaction(function () use ($role, $data, $permissionKeys, $company) {
            // System roles: clone to company-level override if editing a system template
            if ($role->is_system && $role->company_id === null) {
                $role = $this->ensureCompanyOverride($role, $company);
            }

            $role->update([
                'name' => $data['name'] ?? $role->name,
                'description' => $data['description'] ?? $role->description,
                'color' => $data['color'] ?? $role->color,
            ]);

            if ($permissionKeys !== null) {
                $this->syncPermissions($role, $permissionKeys);
            }

            $this->log($role, 'updated', 'Rôle mis à jour.');
            $this->forgetPermissionCache($company->id);

            return $role->fresh('permissions');
        });
    }

    public function duplicate(Role $role, ?string $name = null): Role
    {
        $company = Workspace::company();
        $this->ensureAccessible($role, $company);

        $source = $this->resolveForCompany($role->slug, $company->id) ?? $role;
        $source->load('permissions');

        return $this->create([
            'name' => $name ?: ($source->name.' (copie)'),
            'description' => $source->description,
            'color' => $source->color,
        ], $source->permissions->pluck('key')->all());
    }

    public function delete(Role $role): void
    {
        $company = Workspace::company();
        $this->ensureAccessible($role, $company);

        if (! $role->isDeletable()) {
            throw ValidationException::withMessages(['role' => 'Ce rôle système ne peut pas être supprimé.']);
        }

        $usersCount = $this->usersWithRole($role, $company)->count();
        if ($usersCount > 0) {
            throw ValidationException::withMessages(['role' => "Impossible de supprimer : {$usersCount} utilisateur(s) assigné(s)."]);
        }

        $this->log($role, 'deleted', 'Rôle supprimé.');
        $role->delete();
        $this->forgetPermissionCache($company->id);
    }

    public function syncPermissions(Role $role, array $permissionKeys): void
    {
        $ids = Permission::query()->whereIn('key', $permissionKeys)->pluck('id')->all();
        $role->permissions()->sync($ids);
    }

    public function assignUsers(Role $role, array $userIds): void
    {
        $company = Workspace::company();
        $this->ensureAccessible($role, $company);

        if ($role->is_super) {
            throw ValidationException::withMessages(['role' => 'Le rôle Super Administrateur ne s\'assigne pas ici.']);
        }

        $effective = $this->resolveForCompany($role->slug, $company->id) ?? $role;
        $slug = $effective->slug;

        $validIds = User::query()
            ->forCompany($company->id)
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->all();

        foreach ($validIds as $userId) {
            $company->users()->updateExistingPivot($userId, ['role' => $slug]);
        }

        $this->refreshUsersCount($effective, $company);
        $this->log($effective, 'users_assigned', count($validIds).' utilisateur(s) assigné(s).', ['user_ids' => $validIds]);
        $this->forgetPermissionCache($company->id);
    }

    public function resolveForCompany(string $slug, int $companyId): ?Role
    {
        return Role::query()
            ->where('slug', $slug)
            ->where('company_id', $companyId)
            ->first()
            ?: Role::query()->system()->where('slug', $slug)->first();
    }

    public function permissionKeysForSlug(string $slug, ?int $companyId = null): array
    {
        $companyId = $companyId ?? Workspace::company()?->id;
        $cacheKey = "rbac.perms.{$companyId}.{$slug}";

        return Cache::remember($cacheKey, 300, function () use ($slug, $companyId) {
            $role = $this->resolveForCompany($slug, (int) $companyId);
            if (! $role) {
                return [];
            }
            if ($role->is_super || $slug === 'owner') {
                return Permission::query()->pluck('key')->all();
            }

            return $role->permissionKeys();
        });
    }

    public function usersWithRole(Role $role, ?Company $company = null)
    {
        $company = $company ?? Workspace::company();
        $slug = $role->slug;

        return User::query()
            ->forCompany($company->id)
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $company->id)->where('company_user.role', $slug));
    }

    public function refreshUsersCount(Role $role, ?Company $company = null): void
    {
        $company = $company ?? Workspace::company();
        if ($role->company_id === null) {
            // System role: count across current company only for display
            $count = $this->usersWithRole($role, $company)->count();
            // Don't overwrite system cache globally; skip or store per-request in views
            return;
        }

        $role->update(['users_count_cache' => $this->usersWithRole($role, $company)->count()]);
    }

    public function forgetPermissionCache(?int $companyId = null): void
    {
        $companyId = $companyId ?? Workspace::company()?->id;
        if (! $companyId) {
            return;
        }

        foreach (PermissionCatalog::defaultRoles() as $r) {
            Cache::forget("rbac.perms.{$companyId}.{$r['slug']}");
        }

        Role::query()->where('company_id', $companyId)->pluck('slug')->each(function ($slug) use ($companyId) {
            Cache::forget("rbac.perms.{$companyId}.{$slug}");
        });
    }

    protected function ensureCompanyOverride(Role $systemRole, Company $company): Role
    {
        $existing = Role::query()->where('company_id', $company->id)->where('slug', $systemRole->slug)->first();
        if ($existing) {
            return $existing;
        }

        $clone = Role::query()->create([
            'company_id' => $company->id,
            'slug' => $systemRole->slug,
            'name' => $systemRole->name,
            'description' => $systemRole->description,
            'color' => $systemRole->color,
            'is_system' => true,
            'is_super' => false,
            'is_default' => true,
        ]);

        $clone->permissions()->sync($systemRole->permissions()->pluck('permissions.id'));
        $this->log($clone, 'cloned', 'Override entreprise créé depuis le rôle système.');

        return $clone;
    }

    protected function uniqueSlug(int $companyId, string $slug): string
    {
        $base = Str::slug($slug) ?: 'role';
        $candidate = $base;
        $i = 2;
        while (Role::query()->where('company_id', $companyId)->where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.$i;
            $i++;
        }

        return $candidate;
    }

    protected function ensureAccessible(Role $role, ?Company $company): void
    {
        if (! $company) {
            abort(404);
        }
        if ($role->company_id !== null && $role->company_id !== $company->id) {
            abort(404);
        }
    }

    protected function log(Role $role, string $action, string $message, ?array $meta = null): void
    {
        RoleLog::query()->create([
            'company_id' => $role->company_id ?? Workspace::company()?->id,
            'role_id' => $role->id,
            'user_id' => Workspace::user()?->id,
            'action' => $action,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}

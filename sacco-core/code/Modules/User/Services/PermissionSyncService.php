<?php

namespace Modules\User\Services;

use Illuminate\Support\Facades\Cache;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;
use Nwidart\Modules\Facades\Module;

class PermissionSyncService
{
    public function syncAll(): int
    {
        $synced = 0;
        $modules = Module::getOrdered();

        foreach ($modules as $module) {
            $permissions = config($module->getLowerName() . '.permissions');
            if (!$permissions) {
                continue;
            }
            foreach ($permissions as $definition) {
                if ($this->syncPermissionDefinition($definition)) {
                    $synced++;
                }
            }
        }

        $this->resolveParentIds();
        $this->ensureModuleRoots();
        $this->resolveParentIds();
        $this->syncAdminPermissions();
        $this->clearCache();

        return $synced;
    }

    public function syncModule(string $moduleName): int
    {
        $synced = 0;
        $permissions = config(strtolower($moduleName) . '.permissions');

        if (!$permissions) {
            return 0;
        }

        foreach ($permissions as $definition) {
            if ($this->syncPermissionDefinition($definition)) {
                $synced++;
            }
        }

        $this->resolveParentIds();
        $this->ensureModuleRoots();
        $this->resolveParentIds();
        $this->syncAdminPermissions();
        $this->clearCache();

        return $synced;
    }

    protected function syncPermissionDefinition(array $definition): bool
    {
        $attributes = [
            'display_name' => $definition['display_name'] ?? $definition['name'],
            'module' => $definition['module'] ?? null,
            'description' => $definition['description'] ?? null,
            'guard_name' => $definition['guard_name'] ?? 'web',
        ];

        $permission = Permission::firstOrCreate(
            ['name' => $definition['name'], 'guard_name' => $attributes['guard_name']],
            $attributes
        );

        $updated = $permission->fill($attributes)->isDirty();
        if ($updated) {
            $permission->save();
        }

        return $permission->wasRecentlyCreated || $updated;
    }

    public function resolveParentIds(): void
    {
        $permissions = Permission::all()->keyBy('name');

        foreach ($permissions as $permission) {
            $definition = $this->findDefinitionByName($permission->name);
            $parentName = $definition['parent'] ?? null;

            if (!$parentName) {
                $parentName = $this->deriveParentName($permission->name, $permissions);
            }

            $parentId = null;
            if ($parentName && $permissions->has($parentName)) {
                $parentId = $permissions->get($parentName)->id;
            }

            if ($permission->parent_id !== $parentId) {
                $permission->parent_id = $parentId;
                $permission->save();
            }
        }
    }

    protected function deriveParentName(string $name, $permissions): ?string
    {
        $parts = explode('.', $name);

        while (count($parts) > 1) {
            array_pop($parts);
            $candidate = implode('.', $parts);
            if ($permissions->has($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function ensureModuleRoots(): void
    {
        foreach (Module::getOrdered() as $module) {
            $moduleName = $module->getName();
            $rootName = strtolower($moduleName);
            Permission::firstOrCreate(
                ['name' => $rootName, 'guard_name' => 'web'],
                ['display_name' => $moduleName, 'module' => $moduleName]
            );
        }
    }

    protected function findDefinitionByName(string $name): array
    {
        foreach (Module::getOrdered() as $module) {
            $permissions = config($module->getLowerName() . '.permissions') ?? [];
            foreach ($permissions as $definition) {
                if (($definition['name'] ?? null) === $name) {
                    return $definition;
                }
            }
        }

        return [];
    }

    public function syncAdminPermissions(): void
    {
        if (!Role::where('name', 'admin')->exists()) {
            return;
        }

        $admin = Role::findByName('admin');
        $admin->syncPermissions(Permission::all());
    }

    public function assignPortalPermissionsToClient(array $permissionNames): void
    {
        if (!Role::where('name', 'client')->exists()) {
            return;
        }

        $client = Role::findByName('client');
        $permissions = Permission::whereIn('name', $permissionNames)->get();
        foreach ($permissions as $permission) {
            if (!$client->hasPermissionTo($permission)) {
                $client->givePermissionTo($permission);
            }
        }
    }

    public function clearCache(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Cache::forget(config('permission.cache.key'));
    }
}

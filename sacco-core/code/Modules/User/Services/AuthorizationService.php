<?php

namespace Modules\User\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;
use Modules\User\Entities\User;

class AuthorizationService
{
    protected array $requestPermissionCache = [];

    public function checkPermission(User $user, $permission, $guardName = null): bool
    {
        $permissionName = $this->resolvePermissionName($permission, $guardName);
        if (!$permissionName) {
            return false;
        }

        $cacheKey = $user->id . ':' . $permissionName;
        if (array_key_exists($cacheKey, $this->requestPermissionCache)) {
            return $this->requestPermissionCache[$cacheKey];
        }

        $held = $this->getEffectivePermissionNames($user);
        $allowed = in_array($permissionName, $held, true)
            || $this->hasPermissionViaHierarchy($permissionName, $held);

        $this->requestPermissionCache[$cacheKey] = $allowed;

        return $allowed;
    }

    public function can(User $user, string $permission, $model = null): bool
    {
        if (!$this->checkPermission($user, $permission)) {
            return false;
        }

        if ($model === null) {
            return true;
        }

        return $this->checkModelScope($user, $permission, $model);
    }

    public function bypassesScoping(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function usesOwnScopeOnly(User $user, string $basePermission): bool
    {
        if ($this->bypassesScoping($user)) {
            return false;
        }

        $fullPermission = str_replace('_own', '', $basePermission);
        if (Str::endsWith($basePermission, '_own')) {
            return true;
        }

        $ownPermission = $fullPermission . '_own';
        $hasFull = $this->checkPermission($user, $fullPermission);
        $hasOwn = $this->checkPermission($user, $ownPermission);

        return $hasOwn && !$hasFull;
    }

    public function applyBranchScope(Builder $query, User $user, string $branchColumn = 'branch_id'): Builder
    {
        if ($this->bypassesScoping($user)) {
            return $query;
        }

        if ($this->checkPermission($user, 'branch.branches.all_branches')) {
            return $query;
        }

        $branchIds = $this->getUserBranchIds($user);
        if (empty($branchIds)) {
            return $query;
        }

        return $query->whereIn($branchColumn, $branchIds);
    }

    public function applyOwnRecordScope(Builder $query, User $user, string $permission, string $ownerColumn = 'loan_officer_id'): Builder
    {
        if (!$this->usesOwnScopeOnly($user, $permission)) {
            return $query;
        }

        return $query->where($ownerColumn, $user->id);
    }

    public function applyAuthorizationScopes(Builder $query, User $user, string $permission, string $branchColumn = 'branch_id', string $ownerColumn = 'loan_officer_id'): Builder
    {
        $query = $this->applyBranchScope($query, $user, $branchColumn);
        return $this->applyOwnRecordScope($query, $user, $permission, $ownerColumn);
    }

    public function checkModelScope(User $user, string $permission, $model): bool
    {
        if ($this->bypassesScoping($user)) {
            return true;
        }

        if ($this->usesOwnScopeOnly($user, $permission)) {
            $ownerId = $model->loan_officer_id ?? null;
            if ($ownerId !== null && (int) $ownerId !== (int) $user->id) {
                return false;
            }
        }

        if (!$this->checkPermission($user, 'branch.branches.all_branches')) {
            $branchId = $model->branch_id ?? null;
            $branchIds = $this->getUserBranchIds($user);
            if ($branchId !== null && !empty($branchIds) && !in_array((int) $branchId, $branchIds, true)) {
                return false;
            }
        }

        return true;
    }

    public function getEffectivePermissionNames(User $user): array
    {
        return Cache::remember(
            'rbac.user.' . $user->id . '.permissions',
            now()->addMinutes(5),
            function () use ($user) {
                $names = collect();

                foreach ($user->permissions as $permission) {
                    $names->push($permission->name);
                }

                foreach ($user->roles as $role) {
                    /** @var Role $role */
                    foreach ($role->getAllPermissionsIncludingInherited() as $permission) {
                        $names->push($permission->name);
                    }
                }

                return $names->unique()->values()->all();
            }
        );
    }

    public function getUserBranchIds(User $user): array
    {
        $ids = [];

        if ($user->branch_id) {
            $ids[] = (int) $user->branch_id;
        }

        $pivotIds = \DB::table('branch_users')
            ->where('user_id', $user->id)
            ->pluck('branch_id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        return array_values(array_unique(array_merge($ids, $pivotIds)));
    }

    public function clearUserCache(User $user): void
    {
        Cache::forget('rbac.user.' . $user->id . '.permissions');
        $this->requestPermissionCache = [];
    }

    protected function resolvePermissionName($permission, $guardName = null): ?string
    {
        if (is_string($permission)) {
            return $permission;
        }

        if ($permission instanceof Permission) {
            return $permission->name;
        }

        try {
            return Permission::findByName((string) $permission, $guardName ?? 'web')->name;
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function hasPermissionViaHierarchy(string $requested, array $heldNames): bool
    {
        static $ancestorMap = null;

        if ($ancestorMap === null) {
            $ancestorMap = Permission::all()->mapWithKeys(function (Permission $permission) {
                return [$permission->name => $permission->getAncestorNames()];
            })->all();
        }

        if (!isset($ancestorMap[$requested])) {
            return false;
        }

        foreach ($ancestorMap[$requested] as $ancestor) {
            if (in_array($ancestor, $heldNames, true)) {
                return true;
            }
        }

        return false;
    }
}

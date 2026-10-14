<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'is_system',
        'parent_role_id',
        'description',
    ];

    public function parentRole(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_role_id');
    }

    public function childRoles()
    {
        return $this->hasMany(static::class, 'parent_role_id');
    }

    public function getInheritedPermissions(): Collection
    {
        $permissions = collect();

        if ($this->parent_role_id && $this->parentRole) {
            $permissions = $permissions
                ->merge($this->parentRole->permissions)
                ->merge($this->parentRole->getInheritedPermissions());
        }

        return $permissions->unique('id');
    }

    public function getAllPermissionsIncludingInherited(): Collection
    {
        return $this->permissions
            ->merge($this->getInheritedPermissions())
            ->unique('id');
    }

    public function hasPermissionTo($permission): bool
    {
        if (config('permission.enable_wildcard_permission', false)) {
            return $this->hasWildcardPermission($permission, $this->getDefaultGuardName());
        }

        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            try {
                $permission = $permissionClass->findByName($permission, $this->getDefaultGuardName());
            } catch (\Exception $e) {
                return false;
            }
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission, $this->getDefaultGuardName());
        }

        return false;
    }

    public static function wouldCreateCircularInheritance(int $roleId, ?int $parentRoleId): bool
    {
        if (!$parentRoleId) {
            return false;
        }
        if ($roleId === 0) {
            return false;
        }
        if ($roleId === $parentRoleId) {
            return true;
        }

        $visited = [];
        $current = static::find($parentRoleId);

        while ($current) {
            if ($current->id === $roleId) {
                return true;
            }
            if (in_array($current->id, $visited, true)) {
                return true;
            }
            $visited[] = $current->id;
            $current = $current->parentRole;
        }

        return false;
    }
}

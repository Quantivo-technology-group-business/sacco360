<?php

namespace Modules\User\Entities;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
        'display_name',
        'module',
        'description',
        'parent_id',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function getAncestorNames(): array
    {
        $names = [];
        $current = $this->parent;

        while ($current) {
            $names[] = $current->name;
            $current = $current->parent;
        }

        return $names;
    }

    public function getDescendantNames(): array
    {
        $names = [];

        foreach ($this->children as $child) {
            $names[] = $child->name;
            $names = array_merge($names, $child->getDescendantNames());
        }

        return $names;
    }

    public static function treeByModule(): Collection
    {
        $permissions = static::orderBy('module')->orderBy('name')->get();
        $byId = $permissions->keyBy('id');
        $roots = collect();

        foreach ($permissions as $permission) {
            if ($permission->parent_id && $byId->has($permission->parent_id)) {
                continue;
            }
            $roots->push($permission);
        }

        return $permissions->groupBy('module')->map(function (Collection $modulePermissions) {
            return static::buildTree($modulePermissions);
        });
    }

    protected static function buildTree(Collection $permissions, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($permissions->where('parent_id', $parentId) as $permission) {
            $tree[] = [
                'permission' => $permission,
                'children' => static::buildTree($permissions, $permission->id),
            ];
        }

        return $tree;
    }
}

<?php

namespace Modules\User\Tests\Unit;

use Illuminate\Support\Facades\Hash;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    public function test_permission_hierarchy_allows_child_via_parent(): void
    {
        $parent = Permission::create([
            'name' => 'test.module',
            'display_name' => 'Test Module',
            'module' => 'Test',
            'guard_name' => 'web',
        ]);
        Permission::create([
            'name' => 'test.module.action',
            'display_name' => 'Action',
            'module' => 'Test',
            'guard_name' => 'web',
            'parent_id' => $parent->id,
        ]);

        $role = Role::create(['name' => 'test_role_hierarchy', 'guard_name' => 'web']);
        $role->givePermissionTo($parent);

        $user = $this->makeUser();
        $user->assignRole($role);

        $auth = app(AuthorizationService::class);
        $this->assertTrue($auth->checkPermission($user, 'test.module.action'));
    }

    public function test_role_inheritance_includes_parent_permissions(): void
    {
        $permission = Permission::create([
            'name' => 'test.inherited',
            'display_name' => 'Inherited',
            'module' => 'Test',
            'guard_name' => 'web',
        ]);

        $parentRole = Role::create(['name' => 'parent_role_test', 'guard_name' => 'web']);
        $parentRole->givePermissionTo($permission);

        $childRole = Role::create([
            'name' => 'child_role_test',
            'guard_name' => 'web',
            'parent_role_id' => $parentRole->id,
        ]);

        $user = $this->makeUser();
        $user->assignRole($childRole);

        $auth = app(AuthorizationService::class);
        $this->assertTrue($auth->checkPermission($user, 'test.inherited'));
    }

    public function test_direct_user_permission_override(): void
    {
        $permission = Permission::create([
            'name' => 'test.direct',
            'display_name' => 'Direct',
            'module' => 'Test',
            'guard_name' => 'web',
        ]);

        $user = $this->makeUser();
        $user->givePermissionTo($permission);

        $auth = app(AuthorizationService::class);
        $this->assertTrue($auth->checkPermission($user, 'test.direct'));
    }

    public function test_admin_bypasses_scoping(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web'], ['is_system' => 1]);
        $user = $this->makeUser();
        $user->assignRole($adminRole);

        $auth = app(AuthorizationService::class);
        $this->assertTrue($auth->bypassesScoping($user));
    }

    protected function makeUser(): User
    {
        return User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => uniqid('test') . '@example.com',
            'password' => Hash::make('password'),
            'gender' => 'male',
        ]);
    }
}

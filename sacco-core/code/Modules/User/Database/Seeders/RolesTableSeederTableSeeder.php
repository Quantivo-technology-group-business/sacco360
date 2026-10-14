<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;

class RolesTableSeederTableSeeder extends Seeder
{
    public function run()
    {
        Model::unguard();
        $admin = Role::create(['name' => 'admin', 'is_system' => 1]);
        $admin->syncPermissions(Permission::all());

        $client = Role::create(['name' => 'client', 'is_system' => 1]);
        $portalPermissions = Permission::where('name', 'like', 'portal.%')->pluck('name')->toArray();
        if (!empty($portalPermissions)) {
            $client->syncPermissions($portalPermissions);
        }

        $viewOnly = Role::firstOrCreate(['name' => 'client_view_only', 'guard_name' => 'web'], [
            'description' => 'Portal read-only member access',
        ]);
        $viewOnlyPerms = Permission::where('name', 'like', 'portal.%')
            ->where('name', 'not like', '%.apply')
            ->where('name', 'not like', '%.repay')
            ->where('name', 'not like', '%.deposit')
            ->where('name', 'not like', '%.withdraw')
            ->where('name', 'not like', '%.transfer')
            ->pluck('name')
            ->toArray();
        if (!empty($viewOnlyPerms)) {
            $viewOnly->syncPermissions($viewOnlyPerms);
        }

        $transact = Role::firstOrCreate(['name' => 'client_transact', 'guard_name' => 'web'], [
            'description' => 'Portal member with transaction access',
            'parent_role_id' => $viewOnly->id,
        ]);
        if (!empty($portalPermissions)) {
            $transact->syncPermissions($portalPermissions);
        }
    }
}

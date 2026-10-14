<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SeedChamaPermissions extends Migration
{
    protected $permissions = [
        ['name' => 'sacco.groups',              'display_name' => 'Groups & Chamas',                  'module' => 'Sacco'],
        ['name' => 'sacco.groups.index',        'display_name' => 'View Groups',                      'module' => 'Sacco'],
        ['name' => 'sacco.groups.create',       'display_name' => 'Create Group & Add Members',        'module' => 'Sacco'],
        ['name' => 'sacco.groups.manage',       'display_name' => 'Manage Group Members & Roles',      'module' => 'Sacco'],
        ['name' => 'sacco.meetings',            'display_name' => 'Meetings & AGM',                    'module' => 'Sacco'],
        ['name' => 'sacco.meetings.index',      'display_name' => 'View Meetings',                     'module' => 'Sacco'],
        ['name' => 'sacco.meetings.create',     'display_name' => 'Schedule & Update Meetings',        'module' => 'Sacco'],
        ['name' => 'sacco.meetings.attendance', 'display_name' => 'Record Meeting Attendance',         'module' => 'Sacco'],
    ];

    public function up()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => 'web'],
                ['display_name' => $perm['display_name'], 'module' => $perm['module']]
            );
        }

        // Grant all these permissions to the admin role
        if ($admin = Role::where('name', 'admin')->first()) {
            $admin->givePermissionTo(
                Permission::whereIn('name', array_column($this->permissions, 'name'))->get()
            );
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down()
    {
        Permission::whereIn('name', array_column($this->permissions, 'name'))->delete();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}

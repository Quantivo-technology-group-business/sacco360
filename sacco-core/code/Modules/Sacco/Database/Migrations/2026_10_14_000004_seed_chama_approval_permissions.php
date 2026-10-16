<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seeds the permissions the chama approval queue and payout disbursement need.
 *
 * Seven GroupController actions previously ran behind only auth + 2fa, with no
 * permission check — including disburseCycle(), which moves money. These give
 * the maker-checker split somewhere to hang.
 *
 * Follows the pattern in 2026_06_30_000005_seed_chama_permissions.php.
 */
class SeedChamaApprovalPermissions extends Migration
{
    protected $permissions = [
        ['name' => 'sacco.groups.contributions', 'display_name' => 'View Group Contributions',        'module' => 'Sacco'],
        ['name' => 'sacco.groups.approve',       'display_name' => 'Approve & Reverse Contributions', 'module' => 'Sacco'],
        ['name' => 'sacco.groups.disburse',      'display_name' => 'Disburse Rotation Payouts',       'module' => 'Sacco'],
        ['name' => 'sacco.groups.bidding',       'display_name' => 'Manage Rotation Bidding',         'module' => 'Sacco'],
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

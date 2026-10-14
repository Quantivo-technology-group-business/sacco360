<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Core\Entities\Menu;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SaccoDatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(SaccoProvisionRatesTableSeeder::class);
        $this->call(SaccoChartOfAccountsTableSeeder::class);
        $this->call(SaccoModelDataTableSeeder::class);
        $this->call(SaccoLoanProductTemplatesSeeder::class);
        $this->call(SaccoKycRequirementsSeeder::class);
        $this->call(SaccoComplianceDataSeeder::class);
        $this->call(SaccoDetailSeeder::class);
        $this->call(SaccoRolesTableSeeder::class);

        $permissions = config('sacco.permissions', []);
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'] ?? $permission['name'],
                    'module' => $permission['module'] ?? 'Sacco',
                    'guard_name' => 'web',
                ]
            );
        }

        $menus = config('sacco.menus', []);
        foreach ($menus as $menu) {
            $exists = Menu::where('slug', $menu['slug'])->where('module', 'Sacco')->first();
            if ($exists) {
                continue;
            }
            $m = new Menu();
            $m->is_parent = $menu['is_parent'];
            if ($menu['is_parent'] != 1 && !empty($menu['parent_slug'])) {
                $parent = Menu::where('slug', $menu['parent_slug'])->first();
                if ($parent) {
                    $m->parent_id = $parent->id;
                }
            }
            $m->module = $menu['module'];
            $m->name = $menu['name'];
            $m->slug = $menu['slug'];
            $m->url = $menu['url'];
            $m->icon = $menu['icon'];
            $m->menu_order = $menu['order'];
            $m->permissions = $menu['permissions'];
            $m->save();
        }

        if ($admin = Role::where('name', 'admin')->first()) {
            $admin->syncPermissions(Permission::all());
        }
    }
}

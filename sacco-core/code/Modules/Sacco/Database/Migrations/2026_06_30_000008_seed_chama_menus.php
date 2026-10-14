<?php

use Illuminate\Database\Migrations\Migration;
use Modules\Core\Entities\Menu;
use Spatie\Permission\Models\Permission;

class SeedChamaMenus extends Migration
{
    public function up()
    {
        $menus = config('sacco.menus', []);
        foreach ($menus as $menu) {
            $exists = Menu::where('slug', $menu['slug'])->where('module', 'Sacco')->first();
            if ($exists) {
                // Update existing if you want, but for now we just skip or update
                // Specifically for CHAMA we might want to update
                if ($menu['slug'] === 'chama') {
                    $exists->update([
                        'url' => $menu['url'],
                        'icon' => $menu['icon'],
                        'permissions' => $menu['permissions'],
                        'menu_order' => $menu['order'],
                    ]);
                }
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

        // Also ensure that the parent Sacco menu has the correct icon and order if it already exists
        $saccoMenu = Menu::where('slug', 'sacco')->first();
        if ($saccoMenu) {
             $saccoMenu->update(['icon' => 'fas fa-university', 'menu_order' => 4]);
        }
    }

    public function down()
    {
        // Down not really necessary for data seeding
    }
}

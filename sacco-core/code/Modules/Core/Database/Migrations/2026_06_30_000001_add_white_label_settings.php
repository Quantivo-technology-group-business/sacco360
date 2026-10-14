<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddWhiteLabelSettings extends Migration
{
    public function up(): void
    {
        $settings = [
            [
                'name' => 'Brand Primary Color',
                'setting_key' => 'core.brand_primary_color',
                'module' => 'Core',
                'setting_value' => '#0d5c3d',
                'category' => 'general',
                'type' => 'text',
                'options' => '',
                'class' => '',
                'required' => '0',
                'db_columns' => '',
                'displayed' => '1',
                'rules' => 'nullable|max:255',
                'order' => 0,
                'info' => null,
            ],
            [
                'name' => 'Brand Accent Color',
                'setting_key' => 'core.brand_accent_color',
                'module' => 'Core',
                'setting_value' => '#d4a012',
                'category' => 'general',
                'type' => 'text',
                'options' => '',
                'class' => '',
                'required' => '0',
                'db_columns' => '',
                'displayed' => '1',
                'rules' => 'nullable|max:255',
                'order' => 0,
                'info' => null,
            ],
            [
                'name' => 'Footer Text',
                'setting_key' => 'core.footer_text',
                'module' => 'Core',
                'setting_value' => '',
                'category' => 'general',
                'type' => 'text',
                'options' => '',
                'class' => '',
                'required' => '0',
                'db_columns' => '',
                'displayed' => '1',
                'rules' => 'nullable|max:255',
                'order' => 0,
                'info' => null,
            ],
            [
                'name' => 'Favicon',
                'setting_key' => 'core.favicon',
                'module' => 'Core',
                'setting_value' => '',
                'category' => 'general',
                'type' => 'file',
                'options' => 'jpeg,jpg,bmp,png,ico',
                'class' => '',
                'required' => '0',
                'db_columns' => '',
                'displayed' => '1',
                'rules' => 'nullable|file|mimes:jpeg,jpg,bmp,png,ico',
                'order' => 0,
                'info' => null,
            ],
        ];

        if (!Schema::hasTable('settings')) {
            return;
        }

        foreach ($settings as $setting) {
            if (!DB::table('settings')->where('setting_key', $setting['setting_key'])->exists()) {
                DB::table('settings')->insert($setting);
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        DB::table('settings')->whereIn('setting_key', [
            'core.brand_primary_color',
            'core.brand_accent_color',
            'core.footer_text',
            'core.favicon',
        ])->delete();
    }
}

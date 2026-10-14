<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class EnableMemberRegistration extends Migration
{
    public function up()
    {
        $setting = DB::table('settings')
            ->where('setting_key', 'user.enable_registration')
            ->first();

        if ($setting) {
            DB::table('settings')
                ->where('setting_key', 'user.enable_registration')
                ->update(['setting_value' => 'yes']);
        } else {
            DB::table('settings')->insert([
                'name' => 'Registration Enabled',
                'setting_key' => 'user.enable_registration',
                'module' => 'User',
                'setting_value' => 'yes',
                'category' => 'system',
                'type' => 'select',
                'options' => 'yes,no',
                'class' => '',
                'required' => 1,
                'db_columns' => '',
                'displayed' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down()
    {
        DB::table('settings')
            ->where('setting_key', 'user.enable_registration')
            ->update(['setting_value' => 'no']);
    }
}

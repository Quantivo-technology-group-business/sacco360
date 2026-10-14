<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddRegistrationWorkflowSetting extends Migration
{
    public function up()
    {
        if (!DB::table('settings')->where('setting_key', 'user.registration_workflow')->exists()) {
            DB::table('settings')->insert([
                'name' => 'Registration Workflow',
                'setting_key' => 'user.registration_workflow',
                'module' => 'User',
                'setting_value' => 'detail',
                'category' => 'system',
                'type' => 'select',
                'options' => 'basic,detail',
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
        DB::table('settings')->where('setting_key', 'user.registration_workflow')->delete();
    }
}

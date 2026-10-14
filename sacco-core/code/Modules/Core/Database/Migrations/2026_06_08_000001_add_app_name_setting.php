<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Setting\Entities\Setting;

class AddAppNameSetting extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $exists = Setting::where('setting_key', 'core.app_name')->exists();

        if (!$exists) {
            DB::table('settings')->insert([
                'name' => 'Application Name',
                'setting_key' => 'core.app_name',
                'module' => 'Core',
                'setting_value' => config('app.name', 'Ultimate Loan Manager'),
                'category' => 'general',
                'type' => 'text',
                'options' => '',
                'class' => '',
                'required' => '1',
                'db_columns' => '',
                'displayed' => '1',
                'rules' => 'required|max:255',
                'order' => 0,
                'info' => 'The name of your application displayed in the browser title and emails'
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Setting::where('setting_key', 'core.app_name')->delete();
    }
}

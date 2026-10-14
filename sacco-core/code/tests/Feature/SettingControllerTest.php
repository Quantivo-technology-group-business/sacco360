<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Setting\Entities\Setting;
use Modules\Setting\Http\Controllers\SettingController;

class SettingControllerTest extends \TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        Schema::create('settings', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('setting_key')->unique();
            $table->string('module')->nullable();
            $table->text('setting_value')->nullable();
            $table->string('category')->default('general');
            $table->string('type')->default('text');
            $table->string('options')->nullable();
            $table->string('class')->nullable();
            $table->boolean('required')->default(false);
            $table->string('db_columns')->nullable();
            $table->boolean('displayed')->default(true);
            $table->string('rules')->nullable();
            $table->integer('order')->default(0);
            $table->text('info')->nullable();
            $table->timestamps();
        });
    }

    public function test_update_preserves_existing_file_setting_value_when_no_new_upload_is_provided(): void
    {
        $setting = new Setting([
            'name' => 'Company Logo',
            'setting_key' => 'core.company_logo',
            'module' => 'Core',
            'setting_value' => 'existing-logo.png',
            'category' => 'general',
            'type' => 'file',
            'options' => 'jpeg,jpg,bmp,png',
            'class' => '',
            'required' => false,
            'db_columns' => '',
            'displayed' => true,
            'rules' => 'nullable|file|mimes:jpeg,jpg,bmp,png',
            'order' => 0,
            'info' => null,
        ]);
        $setting->save();

        $request = Request::create('/setting/update', 'POST', ['category' => 'general']);

        $response = (new SettingController())->update($request);

        $setting->refresh();

        $this->assertSame('existing-logo.png', $setting->setting_value);
        $this->assertTrue($response->isRedirect());
    }
}

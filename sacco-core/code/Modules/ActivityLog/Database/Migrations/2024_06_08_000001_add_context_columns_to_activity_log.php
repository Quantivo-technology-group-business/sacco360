<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddContextColumnsToActivityLog extends Migration
{
    public function up()
    {
        $table = config('activitylog.table_name', 'activity_log');

        Schema::table($table, function (Blueprint $blueprint) use ($table) {
            if (!Schema::hasColumn($table, 'ip_address')) {
                $blueprint->string('ip_address', 45)->nullable()->after('properties');
            }
            if (!Schema::hasColumn($table, 'user_agent')) {
                $blueprint->text('user_agent')->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn($table, 'module')) {
                $blueprint->string('module', 100)->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn($table, 'action_type')) {
                $blueprint->string('action_type', 50)->nullable()->after('module');
            }
        });
    }

    public function down()
    {
        $table = config('activitylog.table_name', 'activity_log');

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->dropColumn(['ip_address', 'user_agent', 'module', 'action_type']);
        });
    }
}

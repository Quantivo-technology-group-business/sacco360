<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRbacColumnsToRolesTable extends Migration
{
    public function up()
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['roles'], function (Blueprint $table) use ($tableNames) {
            if (!Schema::hasColumn($tableNames['roles'], 'parent_role_id')) {
                $table->unsignedInteger('parent_role_id')->nullable()->after('is_system');
                $table->foreign('parent_role_id')
                    ->references('id')
                    ->on($tableNames['roles'])
                    ->onDelete('set null');
            }
            if (!Schema::hasColumn($tableNames['roles'], 'description')) {
                $table->string('description')->nullable()->after('name');
            }
        });
    }

    public function down()
    {
        $tableNames = config('permission.table_names');

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropForeign(['parent_role_id']);
            $table->dropColumn(['parent_role_id', 'description']);
        });
    }
}

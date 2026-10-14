<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Scopes meetings to a chama group and closes a portal data leak.
 *
 * sacco_meetings had no group linkage, so Portal\ChamaController::meetings()
 * could not filter by membership and returned every meeting — including board,
 * credit-committee and supervisory-committee minutes — to every member.
 *
 * The column is nullable: a NULL group means a SACCO-wide meeting. Visibility
 * to members is then decided by meeting_type (see ChamaController).
 */
class AddGroupScopeToSaccoMeetings extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_meetings')) {
            return;
        }

        Schema::table('sacco_meetings', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_meetings', 'sacco_group_id')) {
                $table->bigInteger('sacco_group_id')->unsigned()->nullable()
                      ->after('branch_id')
                      ->comment('NULL = SACCO-wide meeting; set = private to that chama group');
                $table->index('sacco_group_id');
            }
        });

        // FK added separately so the hasColumn guard above stays readable and
        // so re-running on a partially-migrated schema cannot duplicate it.
        // INFORMATION_SCHEMA is MySQL-only, so check the driver first.
        if (\DB::getDriverName() !== 'mysql'
            || !Schema::hasColumn('sacco_meetings', 'sacco_group_id')) {
            return;
        }

        $hasFk = collect(\DB::select(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'sacco_meetings'
               AND COLUMN_NAME = 'sacco_group_id'
               AND REFERENCED_TABLE_NAME = 'sacco_groups'"
        ))->isNotEmpty();

        if (!$hasFk) {
            Schema::table('sacco_meetings', function (Blueprint $table) {
                $table->foreign('sacco_group_id')
                      ->references('id')->on('sacco_groups')
                      ->onDelete('set null');
            });
        }
    }

    public function down()
    {
        if (!Schema::hasTable('sacco_meetings')
            || !Schema::hasColumn('sacco_meetings', 'sacco_group_id')) {
            return;
        }

        Schema::table('sacco_meetings', function (Blueprint $table) {
            if (\DB::getDriverName() === 'mysql') {
                $table->dropForeign(['sacco_group_id']);
            }
            $table->dropIndex(['sacco_group_id']);
            $table->dropColumn('sacco_group_id');
        });
    }
}

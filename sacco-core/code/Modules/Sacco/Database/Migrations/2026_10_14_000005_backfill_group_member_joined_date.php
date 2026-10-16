<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs sacco_group_members.joined_date rows written by the bulk member
 * import.
 *
 * DataImportService::importMembers() inserted group enrollments without
 * joined_date. The column is NOT NULL with no default, so with 'strict' => false
 * MySQL wrote '0000-00-00'. That matters because joined_date is the sort key
 * GroupService::orderMembers() uses for the default 'fixed' rotation model —
 * every imported member tied on the same garbage date, making the merry-go-round
 * payout order arbitrary.
 *
 * Best available reconstruction is the row's own created_at date, which is the
 * moment the import ran. Groups whose true joining order matters should be
 * re-sequenced by an officer via the rotation swap controls afterwards.
 */
class BackfillGroupMemberJoinedDate extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_group_members')
            || !Schema::hasColumn('sacco_group_members', 'joined_date')) {
            return;
        }

        // MySQL non-strict stores the zero date; sqlite/other drivers store NULL.
        DB::table('sacco_group_members')
            ->whereNull('joined_date')
            ->update(['joined_date' => DB::raw('DATE(COALESCE(created_at, NOW()))')]);

        if (DB::getDriverName() === 'mysql') {
            DB::table('sacco_group_members')
                ->whereRaw("joined_date = '0000-00-00' OR joined_date IS NULL")
                ->update(['joined_date' => DB::raw('DATE(COALESCE(created_at, NOW()))')]);

            // Any row still without a usable date (created_at also zero) falls
            // back to today so the column never holds a zero date again.
            DB::table('sacco_group_members')
                ->whereRaw("joined_date = '0000-00-00' OR joined_date IS NULL")
                ->update(['joined_date' => DB::raw('CURDATE()')]);
        }
    }

    public function down()
    {
        // Deliberately a no-op: the previous value was corrupt and not worth
        // restoring.
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fixes a silent data-integrity defect in the merry-go-round rotation.
 *
 * GroupService::disburseCycle() writes status = 'paid', but the column was
 * created as enum('upcoming','active','completed','skipped'). Because
 * config/database.php sets 'strict' => false, MySQL did not reject the value;
 * it coerced it to '' and raised a warning. That defeated the
 * "already disbursed" guard (a pot could be paid out repeatedly) and hid
 * disbursed cycles from the member's rotation history.
 *
 * This migration widens the enum and repairs rows already corrupted to ''.
 */
class FixMerryGoRoundCycleStatusEnum extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_merry_go_round_cycles')
            || !Schema::hasColumn('sacco_merry_go_round_cycles', 'status')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE sacco_merry_go_round_cycles
                 MODIFY status ENUM('upcoming','active','completed','skipped','paid')
                 NOT NULL DEFAULT 'upcoming'"
            );
        }

        // Repair rows whose value was truncated to '' by the invalid write.
        // Anything already carrying a disbursement timestamp was a payout;
        // the rest were closed by resolveBiddingCycle() and are 'completed'.
        DB::table('sacco_merry_go_round_cycles')
            ->where('status', '')
            ->whereNotNull('disbursed_at')
            ->update(['status' => 'paid']);

        DB::table('sacco_merry_go_round_cycles')
            ->where('status', '')
            ->whereNull('disbursed_at')
            ->update(['status' => 'completed']);
    }

    public function down()
    {
        if (!Schema::hasTable('sacco_merry_go_round_cycles')
            || !Schema::hasColumn('sacco_merry_go_round_cycles', 'status')) {
            return;
        }

        // Collapse 'paid' back into 'completed' before narrowing the enum,
        // otherwise those rows would be truncated to '' again.
        DB::table('sacco_merry_go_round_cycles')
            ->where('status', 'paid')
            ->update(['status' => 'completed']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE sacco_merry_go_round_cycles
                 MODIFY status ENUM('upcoming','active','completed','skipped')
                 NOT NULL DEFAULT 'upcoming'"
            );
        }
    }
}

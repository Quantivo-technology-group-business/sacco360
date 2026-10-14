<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendMakerCheckerTables extends Migration
{
    public function up()
    {
        // Payload column: stores JSON context needed to finalize deferred actions
        if (!Schema::hasColumn('sacco_pending_approvals', 'payload')) {
            Schema::table('sacco_pending_approvals', function (Blueprint $table) {
                $table->text('payload')->nullable()->after('notes');
            });
        }

        // Label column: human-readable description for the approval queue UI
        if (!Schema::hasColumn('sacco_pending_approvals', 'label')) {
            Schema::table('sacco_pending_approvals', function (Blueprint $table) {
                $table->string('label')->nullable()->after('payload');
            });
        }

        // Expense status for deferred journal entry posting
        if (!Schema::hasColumn('expenses', 'status')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('status')->default('active')->after('description');
            });
        }

        // Share transactions: ensure requires_approval column exists
        if (!Schema::hasColumn('share_transactions', 'requires_approval')) {
            Schema::table('share_transactions', function (Blueprint $table) {
                $table->tinyInteger('requires_approval')->default(0)->after('status');
            });
        }
    }

    public function down()
    {
        Schema::table('sacco_pending_approvals', function (Blueprint $table) {
            $table->dropColumn(['payload', 'label']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('share_transactions', function (Blueprint $table) {
            $table->dropColumn('requires_approval');
        });
    }
}

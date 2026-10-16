<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the columns the group-contribution approval queue and the M-Pesa STK
 * callback both need.
 *
 * Without these there was no way to move a member-submitted contribution out
 * of 'pending': the portal recorded it, and nothing in the codebase ever
 * approved, rejected or reversed it. There was also nowhere to persist the
 * Daraja CheckoutRequestID, so an STK callback could not be matched back to
 * the contribution it belonged to.
 */
class AddApprovalAndMpesaFieldsToGroupContributions extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_group_contributions')) {
            return;
        }

        Schema::table('sacco_group_contributions', function (Blueprint $table) {
            // ── Approval / audit trail ────────────────────────────────────────
            if (!Schema::hasColumn('sacco_group_contributions', 'approved_by_id')) {
                $table->bigInteger('approved_by_id')->unsigned()->nullable()->after('created_by_id');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'approved_at')) {
                $table->dateTime('approved_at')->nullable()->after('approved_by_id');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'rejection_reason')) {
                $table->string('rejection_reason')->nullable()->after('approved_at');
            }

            // ── M-Pesa STK correlation ────────────────────────────────────────
            if (!Schema::hasColumn('sacco_group_contributions', 'checkout_request_id')) {
                $table->string('checkout_request_id')->nullable()->after('payment_reference')
                      ->comment('Daraja CheckoutRequestID; matched by the STK callback');
                $table->index('checkout_request_id');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'merchant_request_id')) {
                $table->string('merchant_request_id')->nullable()->after('checkout_request_id');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'mpesa_receipt_number')) {
                $table->string('mpesa_receipt_number')->nullable()->after('merchant_request_id');
            }
        });

        // The approval queue needs a distinct 'rejected' outcome. Reusing
        // 'reversed' would conflate "never accepted" with "accepted then
        // unwound", which are different audit events.
        if (\DB::getDriverName() === 'mysql'
            && Schema::hasColumn('sacco_group_contributions', 'status')) {
            \DB::statement(
                "ALTER TABLE sacco_group_contributions
                 MODIFY status ENUM('pending','posted','rejected','reversed')
                 NOT NULL DEFAULT 'posted'"
            );
        }
    }

    public function down()
    {
        if (!Schema::hasTable('sacco_group_contributions')) {
            return;
        }

        // Collapse 'rejected' into 'pending' before narrowing, so those rows
        // are not truncated to '' on the way back.
        if (\DB::getDriverName() === 'mysql'
            && Schema::hasColumn('sacco_group_contributions', 'status')) {
            \DB::table('sacco_group_contributions')
                ->where('status', 'rejected')
                ->update(['status' => 'pending']);

            \DB::statement(
                "ALTER TABLE sacco_group_contributions
                 MODIFY status ENUM('pending','posted','reversed')
                 NOT NULL DEFAULT 'posted'"
            );
        }

        Schema::table('sacco_group_contributions', function (Blueprint $table) {
            $columns = [
                'approved_by_id', 'approved_at', 'rejection_reason',
                'checkout_request_id', 'merchant_request_id', 'mpesa_receipt_number',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sacco_group_contributions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

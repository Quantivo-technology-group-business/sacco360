<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration: M-Pesa / Mobile Money Reconciliation Module
 *
 * Tables:
 *  1. sacco_mpesa_reconciliations       – reconciliation session header
 *  2. sacco_mpesa_reconciliation_items  – uploaded Safaricom statement lines
 *  3. sacco_mpesa_reconciliation_audit_logs – immutable audit trail
 *  4. sacco_mpesa_float_accounts        – float balance ledger
 *
 * Alters:
 *  - sacco_mpesa_c2b_transactions: adds reconciliation_item_id, reconciled_at
 *  - sacco_mpesa_b2c_transactions: adds reconciliation_item_id, reconciled_at
 */
class CreateMpesaReconciliationTables extends Migration
{
    public function up()
    {
        // ─────────────────────────────────────────────────────────────────────
        // 1. RECONCILIATION SESSION HEADER
        // ─────────────────────────────────────────────────────────────────────
        if (!Schema::hasTable('sacco_mpesa_reconciliations')) {
            Schema::create('sacco_mpesa_reconciliations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('reviewed_by_id')->unsigned()->nullable();
                $table->bigInteger('approved_by_id')->unsigned()->nullable();

                $table->string('reference')->unique()->nullable();
                $table->enum('provider', ['mpesa', 'airtel_money', 'tkash', 'mtn', 'other'])
                      ->default('mpesa');
                $table->string('short_code')->nullable()->comment('Safaricom PayBill / Till Number');
                $table->string('account_type')->default('c2b')
                      ->comment('c2b | b2c | both');

                // Period
                $table->date('period_from');
                $table->date('period_to');

                // Statement figures (from Safaricom portal/CSV)
                $table->decimal('statement_total_in',  65, 6)->default(0)
                      ->comment('Total credits per statement');
                $table->decimal('statement_total_out', 65, 6)->default(0)
                      ->comment('Total debits per statement');
                $table->integer('statement_count_in')->default(0);
                $table->integer('statement_count_out')->default(0);

                // System figures (from our DB)
                $table->decimal('system_total_in',  65, 6)->default(0);
                $table->decimal('system_total_out', 65, 6)->default(0);
                $table->integer('system_count_in')->default(0);
                $table->integer('system_count_out')->default(0);

                // Match summary
                $table->integer('matched_count')->default(0);
                $table->integer('unmatched_statement')->default(0)
                      ->comment('On statement but not in system');
                $table->integer('unmatched_system')->default(0)
                      ->comment('In system but not on statement');
                $table->integer('discrepancy_count')->default(0)
                      ->comment('Matched but amount differs');
                $table->decimal('net_variance', 65, 6)->default(0);

                // Workflow
                $table->enum('status', [
                    'draft',            // just created
                    'importing',        // CSV being processed
                    'pending_match',    // items imported, awaiting matching
                    'matched',          // auto-match complete
                    'under_review',     // staff reviewing
                    'approved',         // approved
                    'discrepancy',      // approved with known discrepancies
                    'archived',
                ])->default('draft');

                $table->string('statement_file_path')->nullable();
                $table->string('statement_file_name')->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->index(['period_from', 'period_to']);
                $table->index('status');
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // 2. RECONCILIATION LINE ITEMS (uploaded statement rows)
        // ─────────────────────────────────────────────────────────────────────
        if (!Schema::hasTable('sacco_mpesa_reconciliation_items')) {
            Schema::create('sacco_mpesa_reconciliation_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_mpesa_reconciliation_id')->unsigned();

                // Raw data from the Safaricom statement CSV
                $table->string('receipt_number')->nullable()
                      ->comment('e.g. QGH4J8KL3R — Safaricom receipt');
                $table->string('transaction_id')->nullable()
                      ->comment('Internal Safaricom transaction ID');
                $table->dateTime('completion_time')->nullable();
                $table->string('details')->nullable()
                      ->comment('e.g. CustomerPayBillOnline');
                $table->string('transaction_status')->nullable()
                      ->comment('Completed / Cancelled');
                $table->string('paid_in_number')->nullable()
                      ->comment('Phone number that paid');
                $table->string('account_reference')->nullable()
                      ->comment('Bill reference / account number');
                $table->decimal('amount', 65, 6)->default(0);
                $table->decimal('other_party_info_amount', 65, 6)->default(0)->nullable();
                $table->decimal('transaction_cost', 65, 6)->default(0);
                $table->decimal('running_balance', 65, 6)->nullable();
                $table->enum('direction', ['in', 'out'])->default('in');

                // Matching
                $table->enum('match_status', [
                    'unmatched',
                    'matched',
                    'matched_with_variance',
                    'manually_matched',
                    'excluded',
                ])->default('unmatched');

                // Linked internal records
                $table->bigInteger('c2b_transaction_id')->unsigned()->nullable();
                $table->bigInteger('b2c_transaction_id')->unsigned()->nullable();
                $table->bigInteger('matched_by_id')->unsigned()->nullable();
                $table->dateTime('matched_at')->nullable();

                // Variance
                $table->decimal('variance_amount', 65, 6)->nullable()
                      ->comment('statement_amount - system_amount');
                $table->text('match_notes')->nullable();

                // Exception flags
                $table->tinyInteger('is_duplicate')->default(0);
                $table->tinyInteger('is_exception')->default(0);
                $table->string('exception_reason')->nullable();

                $table->timestamps();

                // Explicit names — MySQL enforces a 64-char limit on index identifiers
                $table->index('sacco_mpesa_reconciliation_id', 'recon_items_recon_id_idx');
                $table->index('receipt_number',                'recon_items_receipt_idx');
                $table->index('match_status',                  'recon_items_match_status_idx');
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // 3. AUDIT LOG (immutable — no updates/deletes)
        // ─────────────────────────────────────────────────────────────────────
        if (!Schema::hasTable('sacco_mpesa_reconciliation_audit_logs')) {
            Schema::create('sacco_mpesa_reconciliation_audit_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_mpesa_reconciliation_id')->unsigned();
                $table->bigInteger('user_id')->unsigned()->nullable()
                      ->comment('NULL = system/automated action');
                $table->bigInteger('item_id')->unsigned()->nullable()
                      ->comment('If action is on a specific item');

                $table->string('action');           // e.g. session_created, csv_imported, auto_matched...
                $table->text('description');        // human-readable detail
                $table->json('meta')->nullable();   // e.g. {"matched":142,"unmatched":3}
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();

                // Snapshot of values at time of action
                $table->decimal('amount_before', 65, 6)->nullable();
                $table->decimal('amount_after',  65, 6)->nullable();
                $table->string('status_before')->nullable();
                $table->string('status_after')->nullable();

                $table->timestamps();  // created_at is the event timestamp

                // Explicit names — auto-generated name would exceed MySQL's 64-char limit
                $table->index(['sacco_mpesa_reconciliation_id', 'created_at'], 'recon_audit_recon_id_created_idx');
                $table->index('action', 'recon_audit_action_idx');
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // 4. M-PESA FLOAT ACCOUNT LEDGER
        // ─────────────────────────────────────────────────────────────────────
        if (!Schema::hasTable('sacco_mpesa_float_accounts')) {
            Schema::create('sacco_mpesa_float_accounts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();

                $table->string('provider')->default('mpesa');
                $table->string('short_code')->comment('PayBill or Till');
                $table->string('account_name')->nullable();
                $table->enum('type', ['paybill', 'till', 'b2c_account'])
                      ->default('paybill');

                $table->decimal('current_balance', 65, 6)->default(0)
                      ->comment('Running balance per our records');
                $table->decimal('last_confirmed_balance', 65, 6)->nullable()
                      ->comment('Balance confirmed from Safaricom portal');
                $table->date('last_confirmed_date')->nullable();

                $table->decimal('minimum_float', 65, 6)->default(0)
                      ->comment('Alert threshold');
                $table->tinyInteger('low_float_alert')->default(1);
                $table->tinyInteger('is_active')->default(1);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_mpesa_float_entries')) {
            Schema::create('sacco_mpesa_float_entries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_mpesa_float_account_id')->unsigned();
                $table->bigInteger('created_by_id')->unsigned()->nullable();

                $table->enum('entry_type', ['top_up', 'deduction', 'adjustment', 'confirmed_balance'])
                      ->default('top_up');
                $table->decimal('amount', 65, 6);
                $table->decimal('balance_after', 65, 6);
                $table->date('entry_date');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // ─────────────────────────────────────────────────────────────────────
        // ALTER EXISTING C2B/B2C TABLES
        // ─────────────────────────────────────────────────────────────────────
        if (Schema::hasTable('sacco_mpesa_c2b_transactions')) {
            Schema::table('sacco_mpesa_c2b_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('sacco_mpesa_c2b_transactions', 'reconciliation_item_id')) {
                    $table->bigInteger('reconciliation_item_id')->unsigned()->nullable()
                          ->after('savings_transaction_id');
                }
                if (!Schema::hasColumn('sacco_mpesa_c2b_transactions', 'reconciled_at')) {
                    $table->dateTime('reconciled_at')->nullable()
                          ->after('reconciliation_item_id');
                }
                if (!Schema::hasColumn('sacco_mpesa_c2b_transactions', 'is_reconciled')) {
                    $table->tinyInteger('is_reconciled')->default(0)
                          ->after('reconciled_at');
                }
            });
        }

        if (Schema::hasTable('sacco_mpesa_b2c_transactions')) {
            Schema::table('sacco_mpesa_b2c_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('sacco_mpesa_b2c_transactions', 'reconciliation_item_id')) {
                    $table->bigInteger('reconciliation_item_id')->unsigned()->nullable()
                          ->after('narration');
                }
                if (!Schema::hasColumn('sacco_mpesa_b2c_transactions', 'reconciled_at')) {
                    $table->dateTime('reconciled_at')->nullable()
                          ->after('reconciliation_item_id');
                }
                if (!Schema::hasColumn('sacco_mpesa_b2c_transactions', 'is_reconciled')) {
                    $table->tinyInteger('is_reconciled')->default(0)
                          ->after('reconciled_at');
                }
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_mpesa_float_entries');
        Schema::dropIfExists('sacco_mpesa_float_accounts');
        Schema::dropIfExists('sacco_mpesa_reconciliation_audit_logs');
        Schema::dropIfExists('sacco_mpesa_reconciliation_items');
        Schema::dropIfExists('sacco_mpesa_reconciliations');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaccoTables extends Migration
{
    public function up()
    {
        Schema::create('sacco_transfers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->enum('transfer_type', ['savings_to_loan', 'savings_to_savings', 'share_to_savings', 'savings_to_share']);
            $table->bigInteger('source_id')->unsigned();
            $table->bigInteger('destination_id')->unsigned();
            $table->decimal('amount', 65, 6);
            $table->date('transfer_date');
            $table->text('notes')->nullable();
            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'approved', 'declined', 'completed'])->default('completed');
            $table->timestamps();
        });

        Schema::create('sacco_dividends', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('share_product_id')->unsigned();
            $table->string('name');
            $table->decimal('dividend_per_share', 65, 6);
            $table->decimal('total_pool', 65, 6)->nullable();
            $table->date('declaration_date');
            $table->date('payment_date')->nullable();
            $table->enum('payout_method', ['cash', 'reinvest', 'savings'])->default('savings');
            $table->enum('status', ['draft', 'declared', 'processed', 'cancelled'])->default('draft');
            $table->text('agm_resolution')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_dividend_allocations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_dividend_id')->unsigned();
            $table->bigInteger('share_id')->unsigned();
            $table->bigInteger('client_id')->unsigned();
            $table->integer('shares_held');
            $table->decimal('amount', 65, 6);
            $table->enum('status', ['pending', 'paid', 'reinvested'])->default('pending');
            $table->timestamps();
        });

        Schema::create('sacco_checkoff_batches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->string('employer_name');
            $table->string('period');
            $table->date('upload_date');
            $table->string('filename')->nullable();
            $table->integer('total_records')->default(0);
            $table->integer('matched_records')->default(0);
            $table->integer('unmatched_records')->default(0);
            $table->decimal('total_amount', 65, 6)->default(0);
            $table->enum('status', ['uploaded', 'processing', 'reconciled', 'failed'])->default('uploaded');
            $table->timestamps();
        });

        Schema::create('sacco_checkoff_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_checkoff_batch_id')->unsigned();
            $table->string('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('loan_account_number')->nullable();
            $table->bigInteger('loan_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->decimal('amount', 65, 6);
            $table->enum('status', ['pending', 'matched', 'posted', 'refund', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_tellers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned();
            $table->bigInteger('branch_id')->unsigned();
            $table->string('till_number')->nullable();
            $table->decimal('opening_balance', 65, 6)->default(0);
            $table->decimal('current_balance', 65, 6)->default(0);
            $table->enum('status', ['closed', 'open'])->default('closed');
            $table->dateTime('opened_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_teller_sessions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_teller_id')->unsigned();
            $table->bigInteger('user_id')->unsigned();
            $table->decimal('opening_balance', 65, 6);
            $table->decimal('closing_balance', 65, 6)->nullable();
            $table->decimal('expected_balance', 65, 6)->nullable();
            $table->decimal('variance', 65, 6)->nullable();
            $table->dateTime('opened_at');
            $table->dateTime('closed_at')->nullable();
            $table->enum('status', ['open', 'closed', 'balanced'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_standing_orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('source_savings_id')->unsigned();
            $table->bigInteger('destination_loan_id')->unsigned()->nullable();
            $table->bigInteger('destination_savings_id')->unsigned()->nullable();
            $table->decimal('amount', 65, 6);
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date')->nullable();
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_cheques', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->bigInteger('savings_transaction_id')->unsigned()->nullable();
            $table->string('cheque_number');
            $table->string('bank_name')->nullable();
            $table->string('micr_code')->nullable();
            $table->decimal('amount', 65, 6);
            $table->date('cheque_date');
            $table->date('presented_date')->nullable();
            $table->date('cleared_date')->nullable();
            $table->string('image_path')->nullable();
            $table->enum('status', ['received', 'presented', 'cleared', 'bounced', 'cancelled'])->default('received');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_aml_alerts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->string('alert_type');
            $table->string('severity')->default('medium');
            $table->text('description');
            $table->decimal('amount', 65, 6)->nullable();
            $table->string('reference_type')->nullable();
            $table->bigInteger('reference_id')->unsigned()->nullable();
            $table->enum('status', ['open', 'investigating', 'closed', 'false_positive'])->default('open');
            $table->bigInteger('reviewed_by_id')->unsigned()->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_provision_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('classification');
            $table->integer('days_in_arrears_min')->default(0);
            $table->integer('days_in_arrears_max')->nullable();
            $table->decimal('provision_rate', 8, 4);
            $table->timestamps();
        });

        Schema::create('sacco_financial_periods', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['open', 'closing', 'closed'])->default('open');
            $table->bigInteger('closed_by_id')->unsigned()->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_integration_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('provider');
            $table->string('action');
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->enum('status', ['success', 'failed', 'pending'])->default('pending');
            $table->timestamps();
        });

        Schema::create('sacco_pending_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->string('approvable_type');
            $table->bigInteger('approvable_id')->unsigned();
            $table->decimal('amount', 65, 6)->nullable();
            $table->enum('status', ['pending', 'approved', 'declined'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sacco_pending_approvals');
        Schema::dropIfExists('sacco_integration_logs');
        Schema::dropIfExists('sacco_financial_periods');
        Schema::dropIfExists('sacco_provision_rates');
        Schema::dropIfExists('sacco_aml_alerts');
        Schema::dropIfExists('sacco_cheques');
        Schema::dropIfExists('sacco_standing_orders');
        Schema::dropIfExists('sacco_teller_sessions');
        Schema::dropIfExists('sacco_tellers');
        Schema::dropIfExists('sacco_checkoff_items');
        Schema::dropIfExists('sacco_checkoff_batches');
        Schema::dropIfExists('sacco_dividend_allocations');
        Schema::dropIfExists('sacco_dividends');
        Schema::dropIfExists('sacco_transfers');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration: Add Missing SACCO Features
 *
 * Adds the following modules:
 *  1. Member Welfare / Benevolent Fund
 *  2. Group / Chama Management
 *  3. Investment Portfolio
 *  4. Budget Management
 *  5. AGM / Meeting Management
 *  6. Member Dormancy Rules Engine
 *  7. Guarantor Substitution
 *  8. E-Statement Scheduled Delivery
 */
class AddMissingSaccoFeatures extends Migration
{
    public function up()
    {
        // =====================================================================
        // 1. MEMBER WELFARE / BENEVOLENT FUND
        // =====================================================================

        if (!Schema::hasTable('sacco_welfare_products')) {
            Schema::create('sacco_welfare_products', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('code')->unique();
                $table->text('description')->nullable();
                $table->enum('type', ['death', 'hospitalization', 'education', 'disability', 'maternity', 'general'])
                      ->default('general');
                $table->decimal('member_contribution', 65, 6)->default(0)
                      ->comment('Monthly member contribution amount');
                $table->decimal('max_claim_amount', 65, 6)->nullable()
                      ->comment('Maximum payout per claim');
                $table->integer('qualifying_months')->default(6)
                      ->comment('Months of contribution before eligible');
                $table->integer('waiting_period_days')->default(0)
                      ->comment('Waiting days after joining before claiming');
                $table->tinyInteger('requires_documents')->default(1);
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_welfare_contributions')) {
            Schema::create('sacco_welfare_contributions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('sacco_welfare_product_id')->unsigned();
                $table->decimal('amount', 65, 6);
                $table->date('contribution_date');
                $table->string('period')->nullable()->comment('e.g. 2026-06');
                $table->bigInteger('savings_transaction_id')->unsigned()->nullable();
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->enum('status', ['pending', 'posted', 'reversed'])->default('posted');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_welfare_claims')) {
            Schema::create('sacco_welfare_claims', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('reviewed_by_id')->unsigned()->nullable();
                $table->bigInteger('approved_by_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('sacco_welfare_product_id')->unsigned();
                $table->string('claim_reference')->unique()->nullable();
                $table->date('event_date')->comment('Date of death/hospitalisation/etc.');
                $table->date('claim_date');
                $table->string('beneficiary_name')->nullable();
                $table->string('beneficiary_relationship')->nullable();
                $table->string('beneficiary_phone')->nullable();
                $table->string('beneficiary_id_number')->nullable();
                $table->string('beneficiary_account')->nullable();
                $table->decimal('amount_claimed', 65, 6);
                $table->decimal('amount_approved', 65, 6)->nullable();
                $table->text('remarks')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'paid', 'cancelled'])
                      ->default('submitted');
                $table->date('payment_date')->nullable();
                $table->string('payment_reference')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_welfare_claim_documents')) {
            Schema::create('sacco_welfare_claim_documents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_welfare_claim_id')->unsigned();
                $table->string('document_type');
                $table->string('file_path');
                $table->string('original_filename')->nullable();
                $table->bigInteger('uploaded_by_id')->unsigned()->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 2. GROUP / CHAMA MANAGEMENT
        // =====================================================================

        if (!Schema::hasTable('sacco_groups')) {
            Schema::create('sacco_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('branch_id')->unsigned()->nullable();
                $table->string('name');
                $table->string('code')->unique()->nullable();
                $table->enum('type', ['chama', 'merry_go_round', 'table_banking', 'investment_club', 'other'])
                      ->default('chama');
                $table->string('registration_number')->nullable();
                $table->date('registration_date')->nullable();
                $table->integer('max_members')->nullable();
                $table->decimal('share_value', 65, 6)->default(0)
                      ->comment('Value of one group share');
                $table->decimal('monthly_contribution', 65, 6)->default(0);
                $table->text('purpose')->nullable();
                $table->bigInteger('chairperson_client_id')->unsigned()->nullable();
                $table->bigInteger('secretary_client_id')->unsigned()->nullable();
                $table->bigInteger('treasurer_client_id')->unsigned()->nullable();
                $table->bigInteger('group_savings_id')->unsigned()->nullable()
                      ->comment('Linked savings account for group funds');
                $table->enum('status', ['active', 'suspended', 'dissolved'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_group_members')) {
            Schema::create('sacco_group_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_group_id')->unsigned();
                $table->bigInteger('client_id')->unsigned();
                $table->enum('role', ['member', 'chairperson', 'secretary', 'treasurer', 'committee'])
                      ->default('member');
                $table->date('joined_date');
                $table->date('exit_date')->nullable();
                $table->decimal('shares_held', 10, 2)->default(0);
                $table->enum('status', ['active', 'suspended', 'exited'])->default('active');
                $table->timestamps();
                $table->unique(['sacco_group_id', 'client_id']);
            });
        }

        if (!Schema::hasTable('sacco_group_contributions')) {
            Schema::create('sacco_group_contributions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_group_id')->unsigned();
                $table->bigInteger('sacco_group_member_id')->unsigned();
                $table->bigInteger('client_id')->unsigned();
                $table->decimal('amount', 65, 6);
                $table->date('contribution_date');
                $table->string('period')->nullable()->comment('e.g. 2026-06');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->enum('status', ['pending', 'posted', 'reversed'])->default('posted');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_merry_go_round_cycles')) {
            Schema::create('sacco_merry_go_round_cycles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_group_id')->unsigned();
                $table->integer('cycle_number');
                $table->string('cycle_name')->nullable();
                $table->bigInteger('beneficiary_client_id')->unsigned()
                      ->comment('Member who receives the pot this cycle');
                $table->decimal('pot_amount', 65, 6)->default(0);
                $table->date('cycle_date');
                $table->enum('status', ['upcoming', 'active', 'completed', 'skipped'])->default('upcoming');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 3. INVESTMENT PORTFOLIO
        // =====================================================================

        if (!Schema::hasTable('sacco_investments')) {
            Schema::create('sacco_investments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('branch_id')->unsigned()->nullable();
                $table->string('name');
                $table->string('reference_number')->nullable();
                $table->enum('type', ['treasury_bill', 'treasury_bond', 'fixed_deposit_bank',
                                      'equities', 'real_estate', 'corporate_bond', 'unit_trust', 'other'])
                      ->default('treasury_bill');
                $table->string('issuer')->nullable()->comment('Bank, NSE, Government, etc.');
                $table->decimal('principal_amount', 65, 6);
                $table->decimal('current_value', 65, 6)->nullable();
                $table->decimal('interest_rate', 8, 4)->nullable()
                      ->comment('Annual rate %');
                $table->date('investment_date');
                $table->date('maturity_date')->nullable();
                $table->integer('tenure_days')->nullable();
                $table->decimal('expected_return', 65, 6)->nullable();
                $table->decimal('actual_return', 65, 6)->nullable();
                $table->bigInteger('chart_of_account_id')->unsigned()->nullable()
                      ->comment('GL account for this investment');
                $table->enum('status', ['active', 'matured', 'sold', 'defaulted', 'renewed'])->default('active');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_investment_valuations')) {
            Schema::create('sacco_investment_valuations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_investment_id')->unsigned();
                $table->date('valuation_date');
                $table->decimal('market_value', 65, 6);
                $table->decimal('accrued_interest', 65, 6)->default(0);
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 4. BUDGET MANAGEMENT
        // =====================================================================

        if (!Schema::hasTable('sacco_budget_periods')) {
            Schema::create('sacco_budget_periods', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->integer('fiscal_year');
                $table->date('start_date');
                $table->date('end_date');
                $table->enum('status', ['draft', 'approved', 'active', 'closed'])->default('draft');
                $table->bigInteger('approved_by_id')->unsigned()->nullable();
                $table->date('approved_date')->nullable();
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_budget_lines')) {
            Schema::create('sacco_budget_lines', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_budget_period_id')->unsigned();
                $table->bigInteger('chart_of_account_id')->unsigned();
                $table->decimal('budget_amount', 65, 6)->default(0);
                $table->decimal('q1_amount', 65, 6)->default(0)->comment('Q1 quarterly allocation');
                $table->decimal('q2_amount', 65, 6)->default(0);
                $table->decimal('q3_amount', 65, 6)->default(0);
                $table->decimal('q4_amount', 65, 6)->default(0);
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['sacco_budget_period_id', 'chart_of_account_id']);
            });
        }

        // =====================================================================
        // 5. AGM / MEETING MANAGEMENT
        // =====================================================================

        if (!Schema::hasTable('sacco_meetings')) {
            Schema::create('sacco_meetings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('branch_id')->unsigned()->nullable();
                $table->string('title');
                $table->enum('meeting_type', ['agm', 'sgm', 'board', 'credit_committee',
                                               'supervisory', 'management', 'other'])
                      ->default('board');
                $table->date('scheduled_date');
                $table->time('scheduled_time')->nullable();
                $table->string('venue')->nullable();
                $table->text('notice')->nullable()->comment('Notice to members');
                $table->integer('quorum_required')->nullable()->comment('Minimum members for quorum');
                $table->integer('members_present')->nullable();
                $table->integer('members_apology')->nullable();
                $table->text('minutes')->nullable();
                $table->enum('status', ['scheduled', 'held', 'postponed', 'cancelled'])->default('scheduled');
                $table->date('actual_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_meeting_agenda_items')) {
            Schema::create('sacco_meeting_agenda_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_meeting_id')->unsigned();
                $table->integer('order')->default(1);
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('presenter')->nullable();
                $table->integer('duration_minutes')->nullable();
                $table->text('outcome')->nullable()->comment('Outcome/resolution from this agenda item');
                $table->enum('status', ['pending', 'discussed', 'deferred'])->default('pending');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_meeting_attendance')) {
            Schema::create('sacco_meeting_attendance', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_meeting_id')->unsigned();
                $table->bigInteger('client_id')->unsigned()->nullable();
                $table->bigInteger('user_id')->unsigned()->nullable()
                      ->comment('For staff attendees');
                $table->string('attendee_name')->nullable()->comment('External/guest attendees');
                $table->string('attendee_title')->nullable();
                $table->enum('attendance_type', ['member', 'proxy', 'observer', 'staff', 'external'])
                      ->default('member');
                $table->tinyInteger('attended')->default(1);
                $table->string('proxy_for')->nullable();
                $table->string('signature_path')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_meeting_resolutions')) {
            Schema::create('sacco_meeting_resolutions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_meeting_id')->unsigned();
                $table->string('resolution_number')->nullable();
                $table->text('resolution_text');
                $table->integer('votes_for')->default(0);
                $table->integer('votes_against')->default(0);
                $table->integer('abstentions')->default(0);
                $table->enum('result', ['passed', 'rejected', 'deferred'])->default('passed');
                $table->bigInteger('action_owner_id')->unsigned()->nullable();
                $table->date('action_deadline')->nullable();
                $table->enum('implementation_status', ['pending', 'in_progress', 'completed', 'cancelled'])
                      ->default('pending');
                $table->timestamps();
            });
        }

        // =====================================================================
        // 6. MEMBER DORMANCY RULES ENGINE
        // =====================================================================

        if (!Schema::hasTable('sacco_dormancy_rules')) {
            Schema::create('sacco_dormancy_rules', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->integer('inactivity_months')->default(12)
                      ->comment('Months without any transaction to trigger dormancy');
                $table->decimal('minimum_balance', 65, 6)->default(0)
                      ->comment('Minimum balance below which account is dormant');
                $table->tinyInteger('block_withdrawals')->default(1);
                $table->tinyInteger('block_new_loans')->default(1);
                $table->tinyInteger('charge_dormancy_fee')->default(0);
                $table->decimal('dormancy_fee_amount', 65, 6)->default(0);
                $table->enum('fee_frequency', ['monthly', 'quarterly', 'annually'])->default('annually');
                $table->tinyInteger('notify_member_on_flag')->default(1);
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_dormancy_logs')) {
            Schema::create('sacco_dormancy_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('sacco_dormancy_rule_id')->unsigned()->nullable();
                $table->enum('action', ['flagged_dormant', 'reactivated', 'fee_charged', 'notified'])
                      ->default('flagged_dormant');
                $table->date('action_date');
                $table->decimal('fee_charged', 65, 6)->nullable();
                $table->bigInteger('actioned_by_id')->unsigned()->nullable()
                      ->comment('NULL = system/automated');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 7. GUARANTOR SUBSTITUTION
        // =====================================================================

        if (!Schema::hasTable('sacco_guarantor_substitutions')) {
            Schema::create('sacco_guarantor_substitutions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('approved_by_id')->unsigned()->nullable();
                $table->bigInteger('loan_id')->unsigned();
                $table->bigInteger('outgoing_guarantor_client_id')->unsigned()
                      ->comment('Guarantor being removed');
                $table->bigInteger('incoming_guarantor_client_id')->unsigned()
                      ->comment('New guarantor being added');
                $table->decimal('guaranteed_amount', 65, 6)->nullable()
                      ->comment('Amount being guaranteed by the new guarantor');
                $table->text('reason')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->date('request_date');
                $table->date('decision_date')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
                $table->tinyInteger('outgoing_guarantor_consented')->default(0);
                $table->tinyInteger('incoming_guarantor_consented')->default(0);
                $table->timestamp('outgoing_consent_at')->nullable();
                $table->timestamp('incoming_consent_at')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 8. E-STATEMENT SCHEDULED DELIVERY
        // =====================================================================

        if (!Schema::hasTable('sacco_statement_schedules')) {
            Schema::create('sacco_statement_schedules', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned()->nullable()
                      ->comment('NULL = schedule applies to all members');
                $table->string('name');
                $table->enum('statement_type', ['full', 'savings', 'loans', 'shares'])->default('full');
                $table->enum('frequency', ['monthly', 'quarterly', 'annually'])->default('monthly');
                $table->enum('delivery_channel', ['email', 'sms', 'portal', 'print'])->default('email');
                $table->integer('send_day_of_month')->default(1)
                      ->comment('Day of month to send (1-28)');
                $table->integer('lookback_months')->default(1)
                      ->comment('How many months of history to include');
                $table->tinyInteger('include_loans')->default(1);
                $table->tinyInteger('include_savings')->default(1);
                $table->tinyInteger('include_shares')->default(1);
                $table->tinyInteger('include_summary')->default(1);
                $table->date('last_run_date')->nullable();
                $table->date('next_run_date')->nullable();
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_statement_logs')) {
            Schema::create('sacco_statement_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_statement_schedule_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned();
                $table->string('statement_type')->default('full');
                $table->date('period_from');
                $table->date('period_to');
                $table->enum('delivery_channel', ['email', 'sms', 'portal', 'print'])->default('email');
                $table->string('recipient_email')->nullable();
                $table->string('recipient_phone')->nullable();
                $table->string('file_path')->nullable()->comment('Generated PDF path');
                $table->enum('status', ['queued', 'sent', 'failed', 'opened'])->default('queued');
                $table->text('error_message')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->timestamp('opened_at')->nullable();
                $table->bigInteger('triggered_by_id')->unsigned()->nullable()
                      ->comment('NULL = automated scheduler');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_statement_logs');
        Schema::dropIfExists('sacco_statement_schedules');
        Schema::dropIfExists('sacco_guarantor_substitutions');
        Schema::dropIfExists('sacco_dormancy_logs');
        Schema::dropIfExists('sacco_dormancy_rules');
        Schema::dropIfExists('sacco_meeting_resolutions');
        Schema::dropIfExists('sacco_meeting_attendance');
        Schema::dropIfExists('sacco_meeting_agenda_items');
        Schema::dropIfExists('sacco_meetings');
        Schema::dropIfExists('sacco_budget_lines');
        Schema::dropIfExists('sacco_budget_periods');
        Schema::dropIfExists('sacco_investment_valuations');
        Schema::dropIfExists('sacco_investments');
        Schema::dropIfExists('sacco_merry_go_round_cycles');
        Schema::dropIfExists('sacco_group_contributions');
        Schema::dropIfExists('sacco_group_members');
        Schema::dropIfExists('sacco_groups');
        Schema::dropIfExists('sacco_welfare_claim_documents');
        Schema::dropIfExists('sacco_welfare_claims');
        Schema::dropIfExists('sacco_welfare_contributions');
        Schema::dropIfExists('sacco_welfare_products');
    }
}

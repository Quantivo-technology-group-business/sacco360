<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaccoComplianceTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('loans') && !Schema::hasColumn('loans', 'sector_code')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->string('sector_code', 50)->nullable()->after('loan_product_id');
                $table->string('classification_status', 30)->nullable()->after('sector_code');
                $table->date('classification_date')->nullable()->after('classification_status');
                $table->tinyInteger('interest_suspended')->default(0)->after('classification_date');
            });
        }

        if (Schema::hasTable('chart_of_accounts') && !Schema::hasColumn('chart_of_accounts', 'sasra_line_code')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->string('sasra_line_code', 20)->nullable()->after('account_type');
                $table->string('sasra_category', 50)->nullable()->after('sasra_line_code');
            });
        }

        if (Schema::hasTable('sacco_aml_alerts') && !Schema::hasColumn('sacco_aml_alerts', 'sar_status')) {
            Schema::table('sacco_aml_alerts', function (Blueprint $table) {
                $table->enum('sar_status', ['open', 'reviewed', 'reported'])->default('open')->after('status');
            });
        }

        Schema::create('loan_classifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('loan_id')->unsigned();
            $table->string('classification', 30);
            $table->integer('days_in_arrears')->default(0);
            $table->decimal('outstanding', 65, 6)->default(0);
            $table->decimal('provision_rate', 8, 4)->default(0);
            $table->decimal('provision_amount', 65, 6)->default(0);
            $table->string('previous_classification', 30)->nullable();
            $table->enum('classified_by', ['system', 'manual'])->default('system');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->date('classified_at');
            $table->timestamps();
            $table->index(['loan_id', 'classified_at']);
        });

        Schema::create('sacco_liquidity_snapshots', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->date('snapshot_date');
            $table->decimal('liquid_assets', 65, 6)->default(0);
            $table->decimal('total_deposits', 65, 6)->default(0);
            $table->decimal('liquidity_ratio', 10, 4)->default(0);
            $table->enum('status', ['green', 'amber', 'red'])->default('green');
            $table->timestamps();
            $table->unique('snapshot_date');
        });

        Schema::create('sacco_borrowings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->string('lender_name');
            $table->decimal('principal', 65, 6);
            $table->decimal('outstanding', 65, 6);
            $table->decimal('interest_rate', 8, 4)->nullable();
            $table->date('disbursement_date');
            $table->date('maturity_date')->nullable();
            $table->enum('status', ['active', 'repaid', 'defaulted'])->default('active');
            $table->tinyInteger('sasra_approved')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_coa_line_mappings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chart_of_account_id')->unsigned();
            $table->string('sasra_form', 20);
            $table->string('line_code', 20);
            $table->string('line_description');
            $table->timestamps();
        });

        Schema::create('compliance_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->unsigned()->nullable();
            $table->string('action');
            $table->string('module', 50)->nullable();
            $table->string('subject_type')->nullable();
            $table->bigInteger('subject_id')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->json('properties')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('sacco_report_templates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('form_type', 30)->nullable();
            $table->enum('frequency', ['monthly', 'quarterly', 'annual', 'adhoc'])->default('monthly');
            $table->json('field_definitions')->nullable();
            $table->text('query_binding')->nullable();
            $table->json('output_mapping')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });

        Schema::create('sacco_regulatory_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->enum('approval_type', ['branch_open', 'branch_close', 'agency_open', 'relocation', 'business_closure', 'land_purchase', 'insider_lending']);
            $table->string('title');
            $table->text('description')->nullable();
            $table->bigInteger('board_resolution_id')->unsigned()->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'submitted_to_sasra'])->default('pending');
            $table->date('requested_date');
            $table->date('approval_date')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_provision_runs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->date('period_end');
            $table->decimal('total_provision', 65, 6)->default(0);
            $table->decimal('previous_allowance', 65, 6)->default(0);
            $table->decimal('adjustment_amount', 65, 6)->default(0);
            $table->string('journal_reference')->nullable();
            $table->enum('status', ['pending', 'approved', 'posted', 'reversed'])->default('pending');
            $table->timestamps();
        });

        Schema::create('sacco_license_renewals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('license_number')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date');
            $table->date('renewal_submitted_date')->nullable();
            $table->enum('status', ['active', 'pending_renewal', 'expired', 'submitted'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_board_resolutions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->string('resolution_number');
            $table->string('title');
            $table->enum('meeting_type', ['agm', 'board', 'special'])->default('board');
            $table->date('meeting_date');
            $table->text('resolution_text');
            $table->string('document_path')->nullable();
            $table->enum('status', ['draft', 'approved', 'filed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('sacco_fit_proper_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('person_name');
            $table->enum('role', ['director', 'ceo', 'cfo', 'manager', 'other']);
            $table->date('appointment_date')->nullable();
            $table->date('last_review_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_auditor_appointments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('auditor_name');
            $table->date('agm_date');
            $table->date('appointment_date');
            $table->date('sasra_submission_deadline');
            $table->date('sasra_submitted_date')->nullable();
            $table->enum('status', ['appointed', 'submitted', 'overdue'])->default('appointed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_inspection_actions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('assigned_to_id')->unsigned()->nullable();
            $table->string('inspection_reference')->nullable();
            $table->date('inspection_date')->nullable();
            $table->text('action_point');
            $table->date('deadline');
            $table->enum('status', ['open', 'in_progress', 'completed', 'overdue'])->default('open');
            $table->text('evidence_notes')->nullable();
            $table->string('evidence_path')->nullable();
            $table->date('completed_date')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_compliance_calendar', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('form_code', 30);
            $table->string('form_name');
            $table->enum('frequency', ['monthly', 'quarterly', 'annual', 'adhoc']);
            $table->date('due_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->enum('status', ['upcoming', 'due', 'submitted', 'overdue'])->default('upcoming');
            $table->date('submitted_date')->nullable();
            $table->bigInteger('submitted_by_id')->unsigned()->nullable();
            $table->timestamps();
        });

        Schema::create('loan_interest_accruals', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('loan_id')->unsigned();
            $table->date('accrual_date');
            $table->decimal('amount', 65, 6);
            $table->enum('status', ['accrued', 'suspended', 'reversed'])->default('accrued');
            $table->string('journal_reference')->nullable();
            $table->timestamps();
            $table->unique(['loan_id', 'accrual_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_interest_accruals');
        Schema::dropIfExists('sacco_compliance_calendar');
        Schema::dropIfExists('sacco_inspection_actions');
        Schema::dropIfExists('sacco_auditor_appointments');
        Schema::dropIfExists('sacco_fit_proper_records');
        Schema::dropIfExists('sacco_board_resolutions');
        Schema::dropIfExists('sacco_license_renewals');
        Schema::dropIfExists('sacco_provision_runs');
        Schema::dropIfExists('sacco_regulatory_approvals');
        Schema::dropIfExists('sacco_report_templates');
        Schema::dropIfExists('compliance_audit_log');
        Schema::dropIfExists('sacco_coa_line_mappings');
        Schema::dropIfExists('sacco_borrowings');
        Schema::dropIfExists('sacco_liquidity_snapshots');
        Schema::dropIfExists('loan_classifications');

        if (Schema::hasTable('sacco_aml_alerts') && Schema::hasColumn('sacco_aml_alerts', 'sar_status')) {
            Schema::table('sacco_aml_alerts', function (Blueprint $table) {
                $table->dropColumn('sar_status');
            });
        }
        if (Schema::hasTable('chart_of_accounts') && Schema::hasColumn('chart_of_accounts', 'sasra_line_code')) {
            Schema::table('chart_of_accounts', function (Blueprint $table) {
                $table->dropColumn(['sasra_line_code', 'sasra_category']);
            });
        }
        if (Schema::hasTable('loans') && Schema::hasColumn('loans', 'sector_code')) {
            Schema::table('loans', function (Blueprint $table) {
                $table->dropColumn(['sector_code', 'classification_status', 'classification_date', 'interest_suspended']);
            });
        }
    }
}

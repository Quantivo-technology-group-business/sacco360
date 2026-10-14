<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaccoEnterpriseFeatures extends Migration
{
    public function up()
    {
        if (Schema::hasTable('loan_products') && !Schema::hasColumn('loan_products', 'office_wing')) {
            Schema::table('loan_products', function (Blueprint $table) {
                if (Schema::hasColumn('loan_products', 'appraisal_method')) {
                    $table->enum('office_wing', ['bosa', 'fosa', 'any'])->default('bosa')->after('appraisal_method');
                } else {
                    $table->enum('office_wing', ['bosa', 'fosa', 'any'])->default('bosa');
                }
                $table->tinyInteger('is_salary_advance')->default(0)->after('office_wing');
            });
        }

        if (Schema::hasTable('clients')) {
            if (!Schema::hasColumn('clients', 'kyc_verified')) {
                Schema::table('clients', function (Blueprint $table) {
                    if (Schema::hasColumn('clients', 'status')) {
                        $table->tinyInteger('kyc_verified')->default(0)->after('status');
                    } else {
                        $table->tinyInteger('kyc_verified')->default(0);
                    }
                });
            }
            if (!Schema::hasColumn('clients', 'member_status_detail')) {
                Schema::table('clients', function (Blueprint $table) {
                    if (Schema::hasColumn('clients', 'kyc_verified')) {
                        $table->enum('member_status_detail', ['active', 'dormant', 'defaulting', 'exit', 'pending', 'inactive', 'closed', 'deceased'])->nullable()->after('kyc_verified');
                    } else {
                        $table->enum('member_status_detail', ['active', 'dormant', 'defaulting', 'exit', 'pending', 'inactive', 'closed', 'deceased'])->nullable();
                    }
                });
            }
            if (!Schema::hasColumn('clients', 'nominee_name')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->string('nominee_name')->nullable();
                    $table->string('nominee_relationship')->nullable();
                    $table->string('nominee_mobile')->nullable();
                    $table->string('employer_name')->nullable();
                    $table->string('employer_id')->nullable();
                });
            }
        }

        if (Schema::hasTable('sacco_checkoff_items') && !Schema::hasColumn('sacco_checkoff_items', 'deduction_type')) {
            Schema::table('sacco_checkoff_items', function (Blueprint $table) {
                $table->enum('deduction_type', ['loan', 'bosa_savings', 'share'])->default('loan')->after('loan_account_number');
                $table->bigInteger('savings_id')->unsigned()->nullable()->after('loan_id');
                $table->bigInteger('share_id')->unsigned()->nullable()->after('savings_id');
            });
        }

        if (!Schema::hasTable('sacco_fixed_deposits')) {
        Schema::create('sacco_fixed_deposits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('savings_id')->unsigned();
            $table->bigInteger('client_id')->unsigned();
            $table->decimal('principal', 65, 6);
            $table->decimal('interest_rate', 8, 4);
            $table->integer('tenure_months');
            $table->date('start_date');
            $table->date('maturity_date');
            $table->enum('status', ['active', 'matured', 'renewed', 'premature_withdrawn', 'closed'])->default('active');
            $table->enum('renewal_option', ['payout', 'renew_principal', 'renew_principal_interest'])->default('payout');
            $table->decimal('accrued_interest', 65, 6)->default(0);
            $table->decimal('penalty_rate', 8, 4)->default(0);
            $table->bigInteger('lien_loan_id')->unsigned()->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('sacco_exit_settlements')) {
        Schema::create('sacco_exit_settlements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned();
            $table->enum('exit_reason', ['resignation', 'retirement', 'death', 'expulsion', 'other']);
            $table->date('exit_date');
            $table->decimal('bosa_balance', 65, 6)->default(0);
            $table->decimal('share_value', 65, 6)->default(0);
            $table->decimal('loan_offset', 65, 6)->default(0);
            $table->decimal('net_payout', 65, 6)->default(0);
            $table->string('payout_account')->nullable();
            $table->string('nominee_name')->nullable();
            $table->enum('status', ['draft', 'approved', 'processed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('sacco_mpesa_c2b_transactions')) {
        Schema::create('sacco_mpesa_c2b_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('transaction_type')->nullable();
            $table->string('trans_id')->unique();
            $table->string('trans_time')->nullable();
            $table->decimal('trans_amount', 65, 6);
            $table->string('business_short_code')->nullable();
            $table->string('bill_ref_number')->nullable();
            $table->string('msisdn')->nullable();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->bigInteger('savings_id')->unsigned()->nullable();
            $table->bigInteger('savings_transaction_id')->unsigned()->nullable();
            $table->enum('status', ['received', 'validated', 'posted', 'failed'])->default('received');
            $table->text('response_message')->nullable();
            $table->timestamps();
        });
        }

        if (!Schema::hasTable('sacco_regulatory_approvals')) {
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
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_regulatory_approvals');
        Schema::dropIfExists('sacco_mpesa_c2b_transactions');
        Schema::dropIfExists('sacco_exit_settlements');
        Schema::dropIfExists('sacco_fixed_deposits');

        if (Schema::hasTable('sacco_checkoff_items') && Schema::hasColumn('sacco_checkoff_items', 'deduction_type')) {
            Schema::table('sacco_checkoff_items', function (Blueprint $table) {
                $table->dropColumn(['deduction_type', 'savings_id', 'share_id']);
            });
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'nominee_name')) {
            Schema::table('clients', function (Blueprint $table) {
                $cols = ['nominee_name', 'nominee_relationship', 'nominee_mobile'];
                if (Schema::hasColumn('clients', 'employer_id')) {
                    $cols[] = 'employer_id';
                }
                $table->dropColumn($cols);
            });
        }

        if (Schema::hasTable('loan_products') && Schema::hasColumn('loan_products', 'office_wing')) {
            Schema::table('loan_products', function (Blueprint $table) {
                $table->dropColumn(['office_wing', 'is_salary_advance']);
            });
        }
    }
}

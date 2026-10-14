<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaccoPremiumFeatures extends Migration
{
    public function up()
    {
        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'member_number')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('member_number')->nullable()->unique()->after('external_id');
            });
        }

        if (Schema::hasTable('loan_products')) {
            if (!Schema::hasColumn('loan_products', 'loan_multiplier')) {
                Schema::table('loan_products', function (Blueprint $table) {
                    if (Schema::hasColumn('loan_products', 'lock_guarantee_funds')) {
                        $table->decimal('loan_multiplier', 8, 4)->nullable()->after('lock_guarantee_funds');
                    } else {
                        $table->decimal('loan_multiplier', 8, 4)->nullable();
                    }
                });
            }
            if (!Schema::hasColumn('loan_products', 'appraisal_method')) {
                Schema::table('loan_products', function (Blueprint $table) {
                    if (Schema::hasColumn('loan_products', 'loan_multiplier')) {
                        $table->enum('appraisal_method', ['multiplier', 'two_third_rule', 'combined'])->default('combined')->after('loan_multiplier');
                    } else {
                        $table->enum('appraisal_method', ['multiplier', 'two_third_rule', 'combined'])->default('combined');
                    }
                });
            }
            if (!Schema::hasColumn('loan_products', 'min_guarantors')) {
                Schema::table('loan_products', function (Blueprint $table) {
                    if (Schema::hasColumn('loan_products', 'appraisal_method')) {
                        $table->integer('min_guarantors')->default(0)->after('appraisal_method');
                    } else {
                        $table->integer('min_guarantors')->default(0);
                    }
                });
            }
        }

        if (Schema::hasTable('sacco_tellers')) {
            Schema::table('sacco_tellers', function (Blueprint $table) {
                if (!Schema::hasColumn('sacco_tellers', 'max_single_transaction')) {
                    $table->decimal('max_single_transaction', 65, 6)->nullable()->after('current_balance');
                }
                if (!Schema::hasColumn('sacco_tellers', 'max_daily_withdrawal')) {
                    $table->decimal('max_daily_withdrawal', 65, 6)->nullable()->after('max_single_transaction');
                }
                if (!Schema::hasColumn('sacco_tellers', 'max_cash_on_hand')) {
                    $table->decimal('max_cash_on_hand', 65, 6)->nullable()->after('max_daily_withdrawal');
                }
            });
        }

        if (Schema::hasTable('sacco_teller_sessions')) {
            Schema::table('sacco_teller_sessions', function (Blueprint $table) {
                if (!Schema::hasColumn('sacco_teller_sessions', 'daily_withdrawal_total')) {
                    $table->decimal('daily_withdrawal_total', 65, 6)->default(0)->after('variance');
                }
                if (!Schema::hasColumn('sacco_teller_sessions', 'daily_deposit_total')) {
                    $table->decimal('daily_deposit_total', 65, 6)->default(0)->after('daily_withdrawal_total');
                }
            });
        }

        if (!Schema::hasTable('sacco_member_earmarks')) {
            Schema::create('sacco_member_earmarks', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned();
                $table->enum('severity', ['info', 'warning', 'critical'])->default('warning');
                $table->enum('restriction_type', ['alert', 'block_withdrawal', 'block_deposit', 'block_all_transactions', 'block_loans'])->default('alert');
                $table->string('title');
                $table->text('message');
                $table->tinyInteger('show_at_teller')->default(1);
                $table->date('expires_at')->nullable();
                $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_bank_reconciliations')) {
            Schema::create('sacco_bank_reconciliations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('chart_of_account_id')->unsigned();
                $table->bigInteger('branch_id')->unsigned()->nullable();
                $table->date('statement_date');
                $table->decimal('statement_balance', 65, 6);
                $table->decimal('book_balance', 65, 6)->default(0);
                $table->decimal('reconciled_balance', 65, 6)->nullable();
                $table->decimal('variance', 65, 6)->nullable();
                $table->enum('status', ['draft', 'in_progress', 'reconciled', 'discrepancy'])->default('draft');
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_bank_reconciliation_items')) {
            Schema::create('sacco_bank_reconciliation_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_bank_reconciliation_id')->unsigned();
                $table->date('transaction_date');
                $table->string('description')->nullable();
                $table->string('reference')->nullable();
                $table->decimal('amount', 65, 6);
                $table->enum('entry_type', ['debit', 'credit']);
                $table->enum('source', ['statement', 'book'])->default('statement');
                $table->bigInteger('journal_entry_id')->unsigned()->nullable();
                $table->enum('match_status', ['unmatched', 'matched', 'excluded'])->default('unmatched');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_atm_transactions')) {
            Schema::create('sacco_atm_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('terminal_id');
                $table->string('card_number')->nullable();
                $table->string('member_number')->nullable();
                $table->bigInteger('client_id')->unsigned()->nullable();
                $table->bigInteger('savings_id')->unsigned()->nullable();
                $table->enum('transaction_type', ['balance_inquiry', 'withdrawal', 'deposit', 'mini_statement']);
                $table->decimal('amount', 65, 6)->default(0);
                $table->string('reference')->unique();
                $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
                $table->text('response_message')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_atm_transactions');
        Schema::dropIfExists('sacco_bank_reconciliation_items');
        Schema::dropIfExists('sacco_bank_reconciliations');
        Schema::dropIfExists('sacco_member_earmarks');

        if (Schema::hasTable('sacco_teller_sessions')) {
            Schema::table('sacco_teller_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('sacco_teller_sessions', 'daily_withdrawal_total')) {
                    $table->dropColumn(['daily_withdrawal_total', 'daily_deposit_total']);
                }
            });
        }

        if (Schema::hasTable('sacco_tellers')) {
            Schema::table('sacco_tellers', function (Blueprint $table) {
                $columns = ['max_single_transaction', 'max_daily_withdrawal', 'max_cash_on_hand'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('sacco_tellers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('loan_products') && Schema::hasColumn('loan_products', 'appraisal_method')) {
            Schema::table('loan_products', function (Blueprint $table) {
                $table->dropColumn(['appraisal_method', 'min_guarantors']);
            });
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'member_number')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('member_number');
            });
        }
    }
}

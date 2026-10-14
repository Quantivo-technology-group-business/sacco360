<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgentPortalTables extends Migration
{
    public function up()
    {
        // Add user_id to sacco_agents for authentication
        Schema::table('sacco_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_agents', 'user_id')) {
                $table->bigInteger('user_id')->unsigned()->nullable()->after('id');
                $table->index('user_id');
            }
            if (!Schema::hasColumn('sacco_agents', 'pin')) {
                $table->string('pin', 255)->nullable()->after('phone');
            }
            if (!Schema::hasColumn('sacco_agents', 'pin_attempts')) {
                $table->tinyInteger('pin_attempts')->default(0)->after('pin');
            }
            if (!Schema::hasColumn('sacco_agents', 'locked_until')) {
                $table->timestamp('locked_until')->nullable()->after('pin_attempts');
            }
            if (!Schema::hasColumn('sacco_agents', 'daily_transaction_limit')) {
                $table->decimal('daily_transaction_limit', 65, 6)->default(500000)->after('float_limit');
            }
            if (!Schema::hasColumn('sacco_agents', 'single_transaction_limit')) {
                $table->decimal('single_transaction_limit', 65, 6)->default(100000)->after('daily_transaction_limit');
            }
        });

        // Add agent_id to sacco_agent_transactions for better tracking
        Schema::table('sacco_agent_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_agent_transactions', 'loan_id')) {
                $table->bigInteger('loan_id')->unsigned()->nullable()->after('savings_id');
            }
            if (!Schema::hasColumn('sacco_agent_transactions', 'utility_payment_id')) {
                $table->bigInteger('utility_payment_id')->unsigned()->nullable()->after('loan_id');
            }
            if (!Schema::hasColumn('sacco_agent_transactions', 'member_registration_id')) {
                $table->bigInteger('member_registration_id')->unsigned()->nullable()->after('utility_payment_id');
            }
            if (!Schema::hasColumn('sacco_agent_transactions', 'float_before')) {
                $table->decimal('float_before', 65, 6)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('sacco_agent_transactions', 'float_after')) {
                $table->decimal('float_after', 65, 6)->default(0)->after('float_before');
            }
            if (!Schema::hasColumn('sacco_agent_transactions', 'commission_rate')) {
                $table->decimal('commission_rate', 8, 4)->default(0)->after('commission');
            }
        });

        // Agent commission ledger for detailed commission tracking
        Schema::create('sacco_agent_commission_ledger', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->bigInteger('transaction_id')->unsigned()->nullable();
            $table->enum('type', ['earned', 'withdrawn', 'adjustment', 'bonus'])->default('earned');
            $table->decimal('amount', 65, 6);
            $table->decimal('balance_before', 65, 6)->default(0);
            $table->decimal('balance_after', 65, 6)->default(0);
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->timestamps();

            $table->index('sacco_agent_id');
            $table->index('type');
            $table->index('created_at');
        });

        // Agent-registered members tracking
        Schema::create('sacco_agent_member_registrations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->bigInteger('client_id')->unsigned();
            $table->decimal('registration_fee', 65, 6)->default(0);
            $table->decimal('commission_earned', 65, 6)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sacco_agent_id');
            $table->index('client_id');
            $table->index('status');
        });

        // Agent utility payments
        Schema::create('sacco_agent_utility_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->bigInteger('provider_id')->unsigned();
            $table->string('account_number');
            $table->string('customer_name')->nullable();
            $table->decimal('amount', 65, 6);
            $table->decimal('fee', 65, 6)->default(0);
            $table->decimal('commission', 65, 6)->default(0);
            $table->string('reference')->nullable();
            $table->string('provider_reference')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('response_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sacco_agent_id');
            $table->index('provider_id');
            $table->index('status');
            $table->index('created_at');
        });

        // Commission rate configurations per transaction type
        Schema::create('sacco_agent_commission_rates', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('transaction_type'); // deposit, withdrawal, loan_repayment, utility, registration
            $table->decimal('min_amount', 65, 6)->default(0);
            $table->decimal('max_amount', 65, 6)->nullable();
            $table->enum('rate_type', ['percentage', 'flat'])->default('percentage');
            $table->decimal('rate_value', 8, 4);
            $table->decimal('min_commission', 65, 6)->default(0);
            $table->decimal('max_commission', 65, 6)->nullable();
            $table->tinyInteger('active')->default(1);
            $table->timestamps();

            $table->index('transaction_type');
            $table->index('active');
        });

        // Add commission balance to agents
        Schema::table('sacco_agents', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_agents', 'commission_balance')) {
                $table->decimal('commission_balance', 65, 6)->default(0)->after('commission_rate');
            }
            if (!Schema::hasColumn('sacco_agents', 'total_commissions_earned')) {
                $table->decimal('total_commissions_earned', 65, 6)->default(0)->after('commission_balance');
            }
            if (!Schema::hasColumn('sacco_agents', 'total_commissions_withdrawn')) {
                $table->decimal('total_commissions_withdrawn', 65, 6)->default(0)->after('total_commissions_earned');
            }
        });

        // Seed default commission rates
        $this->seedDefaultCommissionRates();
    }

    protected function seedDefaultCommissionRates()
    {
        $rates = [
            ['transaction_type' => 'deposit', 'min_amount' => 0, 'max_amount' => 10000, 'rate_type' => 'percentage', 'rate_value' => 0.50, 'min_commission' => 5, 'max_commission' => 50],
            ['transaction_type' => 'deposit', 'min_amount' => 10001, 'max_amount' => 50000, 'rate_type' => 'percentage', 'rate_value' => 0.40, 'min_commission' => 40, 'max_commission' => 200],
            ['transaction_type' => 'deposit', 'min_amount' => 50001, 'max_amount' => null, 'rate_type' => 'percentage', 'rate_value' => 0.30, 'min_commission' => 150, 'max_commission' => 500],
            ['transaction_type' => 'withdrawal', 'min_amount' => 0, 'max_amount' => 10000, 'rate_type' => 'percentage', 'rate_value' => 0.50, 'min_commission' => 10, 'max_commission' => 50],
            ['transaction_type' => 'withdrawal', 'min_amount' => 10001, 'max_amount' => 50000, 'rate_type' => 'percentage', 'rate_value' => 0.40, 'min_commission' => 40, 'max_commission' => 200],
            ['transaction_type' => 'withdrawal', 'min_amount' => 50001, 'max_amount' => null, 'rate_type' => 'percentage', 'rate_value' => 0.30, 'min_commission' => 150, 'max_commission' => 500],
            ['transaction_type' => 'loan_repayment', 'min_amount' => 0, 'max_amount' => null, 'rate_type' => 'percentage', 'rate_value' => 0.25, 'min_commission' => 5, 'max_commission' => 300],
            ['transaction_type' => 'utility', 'min_amount' => 0, 'max_amount' => null, 'rate_type' => 'flat', 'rate_value' => 10, 'min_commission' => 10, 'max_commission' => 10],
            ['transaction_type' => 'registration', 'min_amount' => 0, 'max_amount' => null, 'rate_type' => 'flat', 'rate_value' => 100, 'min_commission' => 100, 'max_commission' => 100],
        ];

        foreach ($rates as $rate) {
            \DB::table('sacco_agent_commission_rates')->insert(array_merge($rate, [
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_agent_commission_rates');
        Schema::dropIfExists('sacco_agent_utility_payments');
        Schema::dropIfExists('sacco_agent_member_registrations');
        Schema::dropIfExists('sacco_agent_commission_ledger');

        Schema::table('sacco_agents', function (Blueprint $table) {
            $columns = ['user_id', 'pin', 'pin_attempts', 'locked_until', 'daily_transaction_limit', 
                       'single_transaction_limit', 'commission_balance', 'total_commissions_earned', 
                       'total_commissions_withdrawn'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sacco_agents', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('sacco_agent_transactions', function (Blueprint $table) {
            $columns = ['loan_id', 'utility_payment_id', 'member_registration_id', 
                       'float_before', 'float_after', 'commission_rate'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sacco_agent_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}

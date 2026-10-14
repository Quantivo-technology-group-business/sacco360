<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddChartOfAccountsToSaccoAgents extends Migration
{
    public function up()
    {
        Schema::table('sacco_agents', function (Blueprint $table) {
            $table->unsignedBigInteger('float_chart_of_account_id')->nullable()->after('branch_id');
            $table->unsignedBigInteger('commission_chart_of_account_id')->nullable()->after('float_chart_of_account_id');

            $table->foreign('float_chart_of_account_id')
                ->references('id')
                ->on('chart_of_accounts')
                ->onDelete('set null');

            $table->foreign('commission_chart_of_account_id')
                ->references('id')
                ->on('chart_of_accounts')
                ->onDelete('set null');
        });

        // Update sacco_agent_transactions enum to include all transaction types
        if (Schema::hasColumn('sacco_agent_transactions', 'transaction_type')) {
            \DB::statement("ALTER TABLE sacco_agent_transactions MODIFY transaction_type ENUM('deposit', 'withdrawal', 'float_topup', 'float_withdrawal', 'commission', 'loan_repayment', 'utility', 'registration') DEFAULT 'deposit'");
        }
    }

    public function down()
    {
        Schema::table('sacco_agents', function (Blueprint $table) {
            $table->dropForeign(['float_chart_of_account_id']);
            $table->dropForeign(['commission_chart_of_account_id']);
            $table->dropColumn(['float_chart_of_account_id', 'commission_chart_of_account_id']);
        });
    }
}

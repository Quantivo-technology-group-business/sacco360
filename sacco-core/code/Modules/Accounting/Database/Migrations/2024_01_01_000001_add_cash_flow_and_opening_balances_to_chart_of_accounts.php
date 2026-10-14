<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashFlowAndOpeningBalancesToChartOfAccounts extends Migration
{
    public function up()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->enum('cash_flow_category', ['operating', 'investing', 'financing', 'none'])
                  ->default('none')
                  ->after('account_type');
            $table->decimal('opening_debit', 65, 4)->nullable()->after('cash_flow_category');
            $table->decimal('opening_credit', 65, 4)->nullable()->after('opening_debit');
            $table->string('description')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropColumn(['cash_flow_category', 'opening_debit', 'opening_credit', 'description']);
        });
    }
}

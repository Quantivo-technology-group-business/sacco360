<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBudgetToChartOfAccounts extends Migration
{
    public function up()
    {
        Schema::create('account_budgets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('chart_of_account_id')->unsigned();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->integer('year');
            $table->integer('month')->nullable(); // null = annual budget
            $table->decimal('amount', 65, 4)->default(0);
            $table->text('notes')->nullable();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->timestamps();
            $table->index('chart_of_account_id');
            $table->index(['year', 'month']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('account_budgets');
    }
}

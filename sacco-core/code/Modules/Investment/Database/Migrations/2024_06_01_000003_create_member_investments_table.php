<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMemberInvestmentsTable extends Migration
{
    public function up()
    {
        Schema::create('member_investments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('account_number')->unique();
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('investment_plan_id')->unsigned();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->bigInteger('currency_id')->unsigned()->nullable();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            // Investment details
            $table->decimal('principal_amount', 65, 4);             // amount invested
            $table->bigInteger('units_held')->nullable();            // for unit-based plans
            $table->decimal('unit_price_at_purchase', 65, 4)->nullable();
            $table->decimal('effective_rate', 10, 4)->nullable();    // rate applied (from tier if tiered)
            // Dates
            $table->date('start_date');
            $table->date('maturity_date')->nullable();
            $table->integer('duration_months')->nullable();
            // Profit tracking
            $table->decimal('total_profit_accrued', 65, 4)->default(0);
            $table->decimal('total_profit_paid', 65, 4)->default(0);
            $table->date('last_profit_posting_date')->nullable();
            $table->date('next_profit_posting_date')->nullable();
            // Status
            $table->enum('status', ['pending','active','matured','closed','withdrawn'])->default('pending');
            $table->date('closed_date')->nullable();
            $table->decimal('payout_amount', 65, 4)->nullable();     // final payout on maturity/close
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('client_id');
            $table->index('investment_plan_id');
            $table->index('status');
        });
    }
    public function down() { Schema::dropIfExists('member_investments'); }
}

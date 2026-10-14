<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvestmentPlansTable extends Migration
{
    public function up()
    {
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->enum('plan_type', [
                'fixed_deposit','bond','unit_trust','equity','real_estate','money_market','other'
            ])->default('fixed_deposit');
            // Returns
            $table->decimal('return_rate', 10, 4)->default(0);       // annual %
            $table->enum('return_rate_type', ['fixed','variable','tiered'])->default('fixed');
            $table->enum('interest_method', ['simple','compound'])->default('compound');
            $table->enum('compounding_frequency', ['daily','monthly','quarterly','annually'])->default('monthly');
            $table->enum('profit_posting_frequency', ['monthly','quarterly','semi_annual','annual','on_maturity'])->default('monthly');
            // Duration
            $table->integer('min_duration_months')->default(1);
            $table->integer('max_duration_months')->nullable();       // null = open-ended
            // Amounts
            $table->decimal('min_amount', 65, 4)->default(0);
            $table->decimal('max_amount', 65, 4)->nullable();
            $table->decimal('unit_price', 65, 4)->nullable();         // for unit-based assets
            $table->bigInteger('total_units_available')->nullable();  // null = unlimited
            // Early withdrawal
            $table->tinyInteger('allow_early_withdrawal')->default(0);
            $table->decimal('early_withdrawal_penalty_rate', 10, 4)->default(0); // %
            // GL Accounts
            $table->bigInteger('investment_control_account_id')->unsigned()->nullable(); // Liability — member funds held
            $table->bigInteger('interest_expense_account_id')->unsigned()->nullable();   // Expense — profit paid out
            $table->bigInteger('cash_account_id')->unsigned()->nullable();               // Asset — cash received
            $table->bigInteger('profit_payable_account_id')->unsigned()->nullable();     // Liability — accrued profit
            // Meta
            $table->tinyInteger('active')->default(1);
            $table->tinyInteger('open_to_members')->default(1);
            $table->text('terms_conditions')->nullable();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('investment_plans'); }
}

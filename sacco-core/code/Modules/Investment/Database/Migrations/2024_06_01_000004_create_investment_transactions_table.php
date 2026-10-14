<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvestmentTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('investment_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('member_investment_id')->unsigned();
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->enum('transaction_type', [
                'investment',       // initial principal in
                'top_up',           // additional principal
                'profit_accrual',   // profit calculated (not yet paid)
                'profit_payment',   // profit actually paid to member
                'withdrawal',       // early withdrawal
                'maturity_payout',  // full payout at maturity
                'penalty',          // early withdrawal penalty
            ]);
            $table->decimal('amount', 65, 4);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('journal_entry_id')->unsigned()->nullable(); // link to accounting
            $table->string('transaction_number')->nullable();
            $table->timestamps();
            $table->index('member_investment_id');
        });
    }
    public function down() { Schema::dropIfExists('investment_transactions'); }
}

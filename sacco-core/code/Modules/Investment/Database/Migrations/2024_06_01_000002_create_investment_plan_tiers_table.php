<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInvestmentPlanTiersTable extends Migration
{
    public function up()
    {
        // For tiered return rates (e.g. 0-100k = 6%, 100k-500k = 8%, 500k+ = 10%)
        Schema::create('investment_plan_tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('investment_plan_id')->unsigned();
            $table->decimal('min_amount', 65, 4)->default(0);
            $table->decimal('max_amount', 65, 4)->nullable();
            $table->decimal('return_rate', 10, 4);
            $table->string('label')->nullable();
            $table->timestamps();
            $table->foreign('investment_plan_id')->references('id')->on('investment_plans')->onDelete('cascade');
        });
    }
    public function down() { Schema::dropIfExists('investment_plan_tiers'); }
}

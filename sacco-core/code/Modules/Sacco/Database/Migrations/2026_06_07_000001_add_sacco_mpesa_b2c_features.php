<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSaccoMpesaB2cFeatures extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_beneficiaries')) {
            Schema::create('sacco_beneficiaries', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('client_id')->unsigned();
                $table->string('nickname');
                $table->string('name');
                $table->string('phone');
                $table->tinyInteger('is_active')->default(1);
                $table->timestamps();
                $table->unique(['client_id', 'phone']);
            });
        }

        if (!Schema::hasTable('sacco_mpesa_b2c_transactions')) {
            Schema::create('sacco_mpesa_b2c_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned();
                $table->enum('source_type', ['fosa', 'wallet']);
                $table->bigInteger('savings_id')->unsigned()->nullable();
                $table->bigInteger('wallet_id')->unsigned()->nullable();
                $table->enum('transaction_type', ['send', 'withdraw']);
                $table->bigInteger('beneficiary_id')->unsigned()->nullable();
                $table->string('recipient_phone');
                $table->string('recipient_name')->nullable();
                $table->decimal('amount', 65, 6);
                $table->string('reference')->unique();
                $table->string('conversation_id')->nullable();
                $table->string('originator_conversation_id')->nullable();
                $table->string('mpesa_receipt')->nullable();
                $table->enum('status', ['pending', 'submitted', 'completed', 'failed', 'timeout'])->default('pending');
                $table->text('response_message')->nullable();
                $table->text('narration')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_mpesa_b2c_transactions');
        Schema::dropIfExists('sacco_beneficiaries');
    }
}

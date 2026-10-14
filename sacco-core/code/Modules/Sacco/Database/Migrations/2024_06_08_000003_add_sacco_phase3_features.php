<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaccoPhase3Features extends Migration
{
    public function up()
    {
        Schema::create('sacco_agents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('branch_id')->unsigned()->nullable();
            $table->string('agent_code')->unique();
            $table->string('agent_name');
            $table->string('phone')->nullable();
            $table->string('location')->nullable();
            $table->decimal('float_balance', 65, 6)->default(0);
            $table->decimal('commission_rate', 8, 4)->default(0);
            $table->enum('status', ['active', 'suspended', 'closed'])->default('active');
            $table->timestamps();
        });

        Schema::create('sacco_agent_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->bigInteger('savings_id')->unsigned()->nullable();
            $table->enum('transaction_type', ['deposit', 'withdrawal', 'float_topup', 'commission']);
            $table->decimal('amount', 65, 6);
            $table->decimal('commission', 65, 6)->default(0);
            $table->string('reference')->nullable();
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('completed');
            $table->timestamps();
        });

        Schema::create('sacco_debit_cards', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('savings_id')->unsigned();
            $table->string('card_number')->unique();
            $table->string('card_token')->nullable();
            $table->date('issue_date');
            $table->date('expiry_date');
            $table->enum('status', ['pending', 'active', 'blocked', 'expired', 'cancelled'])->default('pending');
            $table->decimal('daily_limit', 65, 6)->default(50000);
            $table->timestamps();
        });

        Schema::create('sacco_pesalink_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('client_id')->unsigned()->nullable();
            $table->bigInteger('savings_id')->unsigned()->nullable();
            $table->enum('direction', ['outbound', 'inbound'])->default('outbound');
            $table->string('beneficiary_account')->nullable();
            $table->string('beneficiary_bank')->nullable();
            $table->string('beneficiary_name')->nullable();
            $table->decimal('amount', 65, 6);
            $table->string('reference')->unique();
            $table->string('ips_reference')->nullable();
            $table->enum('status', ['pending', 'submitted', 'completed', 'failed', 'reversed'])->default('pending');
            $table->text('response_message')->nullable();
            $table->timestamps();
        });

        Schema::create('sacco_cross_sell_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->enum('source_wing', ['fosa', 'bosa', 'any'])->default('fosa');
            $table->enum('target_wing', ['bosa', 'fosa', 'share'])->default('bosa');
            $table->decimal('min_balance', 65, 6)->default(0);
            $table->text('message')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });

        if (!Schema::hasTable('sacco_integration_logs')) {
            Schema::create('sacco_integration_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('provider', 30);
                $table->string('action', 50);
                $table->text('request_payload')->nullable();
                $table->text('response_payload')->nullable();
                $table->string('status', 20)->default('pending');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_cross_sell_campaigns');
        Schema::dropIfExists('sacco_pesalink_transactions');
        Schema::dropIfExists('sacco_debit_cards');
        Schema::dropIfExists('sacco_agent_transactions');
        Schema::dropIfExists('sacco_agents');
    }
}

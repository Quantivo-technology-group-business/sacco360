<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnhanceAgencyBankingTables extends Migration
{
    public function up()
    {
        Schema::table('sacco_agents', function (Blueprint $table) {
            $table->string('email')->nullable()->after('phone');
            $table->string('id_number')->nullable()->after('email');
            $table->text('address')->nullable()->after('location');
            $table->decimal('float_limit', 65, 6)->default(100000)->after('float_balance');
            $table->string('commission_tier')->nullable()->after('commission_rate');
            $table->text('suspended_reason')->nullable()->after('status');
            $table->timestamp('activated_at')->nullable()->after('suspended_reason');
            $table->timestamp('last_transaction_at')->nullable()->after('activated_at');
            $table->text('notes')->nullable()->after('last_transaction_at');

            $table->index('status');
            $table->index('branch_id');
            $table->index('phone');
        });

        Schema::table('sacco_agent_transactions', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('status');
            $table->timestamp('reversed_at')->nullable()->after('notes');
            $table->bigInteger('reversed_by_id')->unsigned()->nullable()->after('reversed_at');
            $table->text('reversal_reason')->nullable()->after('reversed_by_id');

            $table->index('transaction_type');
            $table->index('status');
            $table->index(['sacco_agent_id', 'created_at']);
            $table->index('created_at');
        });

        Schema::create('sacco_agent_commission_tiers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('tier_name');
            $table->decimal('min_volume', 65, 6)->default(0);
            $table->decimal('max_volume', 65, 6)->nullable();
            $table->decimal('deposit_rate', 8, 4)->default(0.5);
            $table->decimal('withdrawal_rate', 8, 4)->default(0.5);
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });

        Schema::create('sacco_agent_float_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->bigInteger('requested_by_id')->unsigned()->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->decimal('amount', 65, 6);
            $table->enum('request_type', ['topup', 'withdrawal'])->default('topup');
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('sacco_agent_id');
            $table->index('status');
        });

        Schema::create('sacco_agent_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('sacco_agent_id')->unsigned();
            $table->string('document_type');
            $table->string('document_name');
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'verified', 'expired', 'rejected'])->default('pending');
            $table->timestamps();

            $table->index('sacco_agent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sacco_agent_documents');
        Schema::dropIfExists('sacco_agent_float_requests');
        Schema::dropIfExists('sacco_agent_commission_tiers');

        Schema::table('sacco_agent_transactions', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['sacco_agent_id', 'created_at']);
            $table->dropIndex(['created_at']);

            $table->dropColumn(['notes', 'reversed_at', 'reversed_by_id', 'reversal_reason']);
        });

        Schema::table('sacco_agents', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['branch_id']);
            $table->dropIndex(['phone']);

            $table->dropColumn([
                'email', 'id_number', 'address', 'float_limit', 'commission_tier',
                'suspended_reason', 'activated_at', 'last_transaction_at', 'notes'
            ]);
        });
    }
}

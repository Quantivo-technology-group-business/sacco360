<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInternetBankingFeatures extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_transaction_otps')) {
            Schema::create('sacco_transaction_otps', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id');
                $table->string('purpose', 50);
                $table->string('reference', 100)->nullable();
                $table->string('code_hash');
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();
                $table->index(['client_id', 'purpose']);
            });
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'transaction_pin')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('transaction_pin')->nullable()->after('mobile');
            });
        }

        if (Schema::hasTable('sacco_transfers') && !Schema::hasColumn('sacco_transfers', 'recipient_client_id')) {
            Schema::table('sacco_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('recipient_client_id')->nullable()->after('client_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_transaction_otps');

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'transaction_pin')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('transaction_pin');
            });
        }

        if (Schema::hasTable('sacco_transfers') && Schema::hasColumn('sacco_transfers', 'recipient_client_id')) {
            Schema::table('sacco_transfers', function (Blueprint $table) {
                $table->dropColumn('recipient_client_id');
            });
        }
    }
}

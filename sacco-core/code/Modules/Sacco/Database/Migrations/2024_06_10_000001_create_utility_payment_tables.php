<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateUtilityPaymentTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('sacco_utility_providers')) {
            Schema::create('sacco_utility_providers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->enum('category', ['electricity', 'water', 'tv', 'airtime'])->default('electricity');
                $table->string('account_label')->default('Account number');
                $table->decimal('min_amount', 15, 2)->default(10);
                $table->decimal('max_amount', 15, 2)->default(500000);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('sacco_utility_payments')) {
            Schema::create('sacco_utility_payments', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('savings_id')->nullable();
                $table->unsignedBigInteger('provider_id');
                $table->string('account_number', 50);
                $table->decimal('amount', 15, 2);
                $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
                $table->string('reference', 50)->unique();
                $table->string('channel', 20)->default('ussd');
                $table->unsignedBigInteger('savings_transaction_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'pin')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('pin', 10)->nullable()->after('mobile');
            });
        }

        if (Schema::hasTable('sacco_utility_providers') && DB::table('sacco_utility_providers')->count() === 0) {
            DB::table('sacco_utility_providers')->insert([
                ['code' => 'kplc', 'name' => 'KPLC', 'category' => 'electricity', 'account_label' => 'Meter number', 'min_amount' => 50, 'max_amount' => 50000, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'nairobi_water', 'name' => 'Nairobi Water', 'category' => 'water', 'account_label' => 'Account number', 'min_amount' => 50, 'max_amount' => 50000, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'dstv', 'name' => 'DSTV', 'category' => 'tv', 'account_label' => 'Smart card number', 'min_amount' => 100, 'max_amount' => 20000, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'gotv', 'name' => 'GOtv', 'category' => 'tv', 'account_label' => 'IUC number', 'min_amount' => 100, 'max_amount' => 5000, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
                ['code' => 'safaricom_airtime', 'name' => 'Safaricom Airtime', 'category' => 'airtime', 'account_label' => 'Phone number', 'min_amount' => 10, 'max_amount' => 10000, 'active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_utility_payments');
        Schema::dropIfExists('sacco_utility_providers');
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'pin')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('pin');
            });
        }
    }
}

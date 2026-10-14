<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentFieldsToSaccoGroupContributions extends Migration
{
    public function up()
    {
        Schema::table('sacco_group_contributions', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_group_contributions', 'payment_method')) {
                $table->enum('payment_method', ['cash', 'mpesa', 'savings', 'bank', 'other'])
                      ->default('cash')
                      ->after('status');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_method');
            }
            if (!Schema::hasColumn('sacco_group_contributions', 'phone_number')) {
                $table->string('phone_number')->nullable()->after('payment_reference');
            }
        });
    }

    public function down()
    {
        Schema::table('sacco_group_contributions', function (Blueprint $table) {
            foreach (['payment_method', 'payment_reference', 'phone_number'] as $col) {
                if (Schema::hasColumn('sacco_group_contributions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}

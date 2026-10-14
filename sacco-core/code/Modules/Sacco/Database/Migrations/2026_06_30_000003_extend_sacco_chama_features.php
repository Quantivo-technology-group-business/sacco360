<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendSaccoChamaFeatures extends Migration
{
    public function up()
    {
        // 1. Extend sacco_groups table
        Schema::table('sacco_groups', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_groups', 'rotation_model')) {
                $table->enum('rotation_model', ['fixed', 'random', 'bidding'])->default('fixed')->after('monthly_contribution');
            }
            if (!Schema::hasColumn('sacco_groups', 'late_payment_penalty')) {
                $table->decimal('late_payment_penalty', 10, 2)->default(0.00)->after('rotation_model');
            }
        });

        // 2. Create sacco_rotation_bids table
        if (!Schema::hasTable('sacco_rotation_bids')) {
            Schema::create('sacco_rotation_bids', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('sacco_group_id')->unsigned();
                $table->integer('cycle_number');
                $table->bigInteger('client_id')->unsigned();
                $table->decimal('bid_amount', 10, 2);
                $table->enum('status', ['pending', 'won', 'lost'])->default('pending');
                $table->timestamps();

                $table->foreign('sacco_group_id')->references('id')->on('sacco_groups')->onDelete('cascade');
                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }

        // 3. Extend sacco_merry_go_round_cycles table
        Schema::table('sacco_merry_go_round_cycles', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'bidding_discount')) {
                $table->decimal('bidding_discount', 10, 2)->default(0.00)->after('pot_amount');
            }
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'penalties_deducted')) {
                $table->decimal('penalties_deducted', 10, 2)->default(0.00)->after('bidding_discount');
            }
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'net_payout_amount')) {
                $table->decimal('net_payout_amount', 10, 2)->default(0.00)->after('penalties_deducted');
            }
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'disbursed_at')) {
                $table->dateTime('disbursed_at')->nullable()->after('net_payout_amount');
            }
            if (!Schema::hasColumn('sacco_merry_go_round_cycles', 'disbursement_reference')) {
                $table->string('disbursement_reference')->nullable()->after('disbursed_at');
            }
        });
    }

    public function down()
    {
        Schema::table('sacco_merry_go_round_cycles', function (Blueprint $table) {
            $columns = ['bidding_discount', 'penalties_deducted', 'net_payout_amount', 'disbursed_at', 'disbursement_reference'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sacco_merry_go_round_cycles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('sacco_rotation_bids');

        Schema::table('sacco_groups', function (Blueprint $table) {
            $columns = ['rotation_model', 'late_payment_penalty'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('sacco_groups', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

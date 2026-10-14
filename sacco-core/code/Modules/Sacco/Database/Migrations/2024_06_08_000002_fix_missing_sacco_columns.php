<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixMissingSaccoColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('share_products') && !Schema::hasColumn('share_products', 'share_category')) {
            Schema::table('share_products', function (Blueprint $table) {
                $table->enum('share_category', ['compulsory', 'voluntary'])->default('voluntary');
                $table->decimal('max_share_percent', 8, 4)->nullable();
                $table->integer('payment_deadline_months')->nullable();
            });
        }

        if (Schema::hasTable('shares') && !Schema::hasColumn('shares', 'locked_shares')) {
            Schema::table('shares', function (Blueprint $table) {
                $table->integer('locked_shares')->default(0);
            });
        }

        if (Schema::hasTable('loan_guarantors') && !Schema::hasColumn('loan_guarantors', 'share_id')) {
            Schema::table('loan_guarantors', function (Blueprint $table) {
                $table->bigInteger('share_id')->unsigned()->nullable();
                $table->integer('locked_shares')->default(0);
            });
        }

        if (Schema::hasTable('savings_products') && !Schema::hasColumn('savings_products', 'account_product_type')) {
            Schema::table('savings_products', function (Blueprint $table) {
                $table->enum('account_product_type', ['savings', 'current', 'fixed_deposit'])->default('savings');
            });
        }

        if (Schema::hasTable('savings_transactions') && !Schema::hasColumn('savings_transactions', 'requires_approval')) {
            Schema::table('savings_transactions', function (Blueprint $table) {
                $table->tinyInteger('requires_approval')->default(0);
                $table->bigInteger('approved_by_id')->unsigned()->nullable();
            });
        }
    }

    public function down()
    {
        // Non-destructive fix migration; no rollback.
    }
}

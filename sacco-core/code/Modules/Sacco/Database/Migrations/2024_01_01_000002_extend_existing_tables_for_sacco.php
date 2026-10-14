<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ExtendExistingTablesForSacco extends Migration
{
    public function up()
    {
        if (Schema::hasTable('share_products') && !Schema::hasColumn('share_products', 'share_category')) {
            Schema::table('share_products', function (Blueprint $table) {
                $table->enum('share_category', ['compulsory', 'voluntary'])->default('voluntary')->after('description');
                $table->decimal('max_share_percent', 8, 4)->nullable()->after('share_category');
                $table->integer('payment_deadline_months')->nullable()->after('max_share_percent');
            });
        }

        if (Schema::hasTable('shares') && !Schema::hasColumn('shares', 'locked_shares')) {
            Schema::table('shares', function (Blueprint $table) {
                $table->integer('locked_shares')->default(0)->after('total_shares');
            });
        }

        if (Schema::hasTable('loan_guarantors') && !Schema::hasColumn('loan_guarantors', 'share_id')) {
            Schema::table('loan_guarantors', function (Blueprint $table) {
                $table->bigInteger('share_id')->unsigned()->nullable()->after('client_id');
                $table->integer('locked_shares')->default(0)->after('guaranteed_amount');
            });
        }

        if (Schema::hasTable('loan_products') && !Schema::hasColumn('loan_products', 'loan_multiplier')) {
            Schema::table('loan_products', function (Blueprint $table) {
                $table->decimal('loan_multiplier', 8, 4)->nullable()->after('lock_guarantee_funds');
            });
        }

        if (Schema::hasTable('savings_products') && !Schema::hasColumn('savings_products', 'account_product_type')) {
            Schema::table('savings_products', function (Blueprint $table) {
                $table->enum('account_product_type', ['savings', 'current', 'fixed_deposit'])->default('savings')->after('savings_category');
            });
        }

        if (Schema::hasTable('clients')) {
            if (!Schema::hasColumn('clients', 'kyc_verified')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->tinyInteger('kyc_verified')->default(0)->after('status');
                });
            }
            if (!Schema::hasColumn('clients', 'member_status_detail')) {
                Schema::table('clients', function (Blueprint $table) {
                    $table->enum('member_status_detail', ['active', 'dormant', 'defaulting', 'exit', 'pending', 'inactive', 'closed', 'deceased'])->nullable()->after('kyc_verified');
                });
            }
        }

        if (Schema::hasTable('savings_transactions') && !Schema::hasColumn('savings_transactions', 'requires_approval')) {
            Schema::table('savings_transactions', function (Blueprint $table) {
                $table->tinyInteger('requires_approval')->default(0)->after('status');
                $table->bigInteger('approved_by_id')->unsigned()->nullable()->after('requires_approval');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('savings_transactions')) {
            Schema::table('savings_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('savings_transactions', 'requires_approval')) {
                    $table->dropColumn(['requires_approval', 'approved_by_id']);
                }
            });
        }
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                if (Schema::hasColumn('clients', 'kyc_verified')) {
                    $table->dropColumn(['kyc_verified', 'member_status_detail']);
                }
            });
        }
        if (Schema::hasTable('savings_products') && Schema::hasColumn('savings_products', 'account_product_type')) {
            Schema::table('savings_products', function (Blueprint $table) {
                $table->dropColumn('account_product_type');
            });
        }
        if (Schema::hasTable('loan_products') && Schema::hasColumn('loan_products', 'loan_multiplier')) {
            Schema::table('loan_products', function (Blueprint $table) {
                $table->dropColumn('loan_multiplier');
            });
        }
        if (Schema::hasTable('loan_guarantors')) {
            Schema::table('loan_guarantors', function (Blueprint $table) {
                if (Schema::hasColumn('loan_guarantors', 'share_id')) {
                    $table->dropColumn(['share_id', 'locked_shares']);
                }
            });
        }
        if (Schema::hasTable('shares') && Schema::hasColumn('shares', 'locked_shares')) {
            Schema::table('shares', function (Blueprint $table) {
                $table->dropColumn('locked_shares');
            });
        }
        if (Schema::hasTable('share_products') && Schema::hasColumn('share_products', 'share_category')) {
            Schema::table('share_products', function (Blueprint $table) {
                $table->dropColumn(['share_category', 'max_share_percent', 'payment_deadline_months']);
            });
        }
    }
}

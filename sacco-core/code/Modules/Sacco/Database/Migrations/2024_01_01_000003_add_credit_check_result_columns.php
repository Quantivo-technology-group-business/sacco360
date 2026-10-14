<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCreditCheckResultColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('loan_linked_credit_checks')) {
            Schema::table('loan_linked_credit_checks', function (Blueprint $table) {
                if (!Schema::hasColumn('loan_linked_credit_checks', 'status')) {
                    $table->string('status')->nullable()->after('loan_credit_check_id');
                }
                if (!Schema::hasColumn('loan_linked_credit_checks', 'message')) {
                    $table->text('message')->nullable()->after('status');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('loan_linked_credit_checks')) {
            Schema::table('loan_linked_credit_checks', function (Blueprint $table) {
                if (Schema::hasColumn('loan_linked_credit_checks', 'message')) {
                    $table->dropColumn('message');
                }
                if (Schema::hasColumn('loan_linked_credit_checks', 'status')) {
                    $table->dropColumn('status');
                }
            });
        }
    }
}

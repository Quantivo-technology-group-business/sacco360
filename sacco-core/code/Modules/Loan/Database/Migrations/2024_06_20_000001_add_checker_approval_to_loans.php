<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckerApprovalToLoans extends Migration
{
    public function up()
    {
        Schema::table('loans', function (Blueprint $table) {
            if (!Schema::hasColumn('loans', 'checker_approved_disbursement')) {
                $table->tinyInteger('checker_approved_disbursement')->default(0)->after('status');
            }
            if (!Schema::hasColumn('loans', 'checker_approved_writeoff')) {
                $table->tinyInteger('checker_approved_writeoff')->default(0)->after('checker_approved_disbursement');
            }
        });
    }

    public function down()
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['checker_approved_disbursement', 'checker_approved_writeoff']);
        });
    }
}

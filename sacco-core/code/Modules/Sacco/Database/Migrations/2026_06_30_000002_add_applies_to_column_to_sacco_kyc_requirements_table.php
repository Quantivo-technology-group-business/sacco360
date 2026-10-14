<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAppliesToColumnToSaccoKycRequirementsTable extends Migration
{
    public function up()
    {
        Schema::table('sacco_kyc_requirements', function (Blueprint $table) {
            if (!Schema::hasColumn('sacco_kyc_requirements', 'applies_to')) {
                $table->enum('applies_to', ['individual', 'organisation', 'both'])->default('both')->after('active');
            }
        });
    }

    public function down()
    {
        Schema::table('sacco_kyc_requirements', function (Blueprint $table) {
            if (Schema::hasColumn('sacco_kyc_requirements', 'applies_to')) {
                $table->dropColumn('applies_to');
            }
        });
    }
}

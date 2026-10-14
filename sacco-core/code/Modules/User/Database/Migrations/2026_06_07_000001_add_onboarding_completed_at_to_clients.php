<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOnboardingCompletedAtToClients extends Migration
{
    public function up()
    {
        if (Schema::hasTable('clients') && !Schema::hasColumn('clients', 'onboarding_completed_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dateTime('onboarding_completed_at')->nullable()->after('created_date');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'onboarding_completed_at')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('onboarding_completed_at');
            });
        }
    }
}

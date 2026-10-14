<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientProfilesAndDirectorsTables extends Migration
{
    public function up()
    {
        // 1. Alter clients table to support individual and organization profiles
        Schema::table('clients', function (Blueprint $table) {
            // Shared profile fields
            if (!Schema::hasColumn('clients', 'landing_site')) {
                $table->string('landing_site')->nullable();
            }
            if (!Schema::hasColumn('clients', 'years_in_business')) {
                $table->string('years_in_business')->nullable();
            }

            // Individual profile fields
            if (!Schema::hasColumn('clients', 'id_number')) {
                $table->string('id_number')->nullable();
            }
            if (!Schema::hasColumn('clients', 'passport_number')) {
                $table->string('passport_number')->nullable();
            }
            if (!Schema::hasColumn('clients', 'alternative_mobile')) {
                $table->string('alternative_mobile')->nullable();
            }
            if (!Schema::hasColumn('clients', 'employment_status')) {
                $table->string('employment_status')->nullable();
            }
            if (!Schema::hasColumn('clients', 'workplace_address')) {
                $table->string('workplace_address')->nullable();
            }
            if (!Schema::hasColumn('clients', 'employer_phone')) {
                $table->string('employer_phone')->nullable();
            }
            if (!Schema::hasColumn('clients', 'occupation')) {
                $table->string('occupation')->nullable();
            }

            // Organisation profile fields
            if (!Schema::hasColumn('clients', 'registration_number')) {
                $table->string('registration_number')->nullable();
            }
            if (!Schema::hasColumn('clients', 'registration_date')) {
                $table->date('registration_date')->nullable();
            }
            if (!Schema::hasColumn('clients', 'nature_of_business')) {
                $table->string('nature_of_business')->nullable();
            }
            if (!Schema::hasColumn('clients', 'business_category')) {
                $table->string('business_category')->nullable();
            }
            if (!Schema::hasColumn('clients', 'witnesses')) {
                $table->json('witnesses')->nullable();
            }
            if (!Schema::hasColumn('clients', 'membership_number')) {
                $table->string('membership_number')->nullable();
            }
            if (!Schema::hasColumn('clients', 'date_of_admission')) {
                $table->date('date_of_admission')->nullable();
            }
        });

        // 2. Alter client_next_of_kin to support id_number
        Schema::table('client_next_of_kin', function (Blueprint $table) {
            if (!Schema::hasColumn('client_next_of_kin', 'id_number')) {
                $table->string('id_number')->nullable()->after('last_name');
            }
        });

        // 3. Create client_directors table
        if (!Schema::hasTable('client_directors')) {
            Schema::create('client_directors', function (Blueprint $table) {
                $table->id();
                $table->bigInteger('client_id')->unsigned();
                $table->string('full_name');
                $table->string('id_number')->nullable();
                $table->string('passport_number')->nullable();
                $table->string('kra_pin')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('position')->nullable();
                $table->string('photo')->nullable();
                $table->string('id_copy')->nullable();
                $table->string('pin_certificate')->nullable();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('client_directors');

        Schema::table('client_next_of_kin', function (Blueprint $table) {
            if (Schema::hasColumn('client_next_of_kin', 'id_number')) {
                $table->dropColumn('id_number');
            }
        });

        Schema::table('clients', function (Blueprint $table) {
            $columns = [
                'landing_site', 'years_in_business',
                'id_number', 'passport_number', 'alternative_mobile',
                'employment_status', 'workplace_address', 'employer_phone', 'occupation',
                'registration_number', 'registration_date', 'nature_of_business', 'business_category',
                'witnesses', 'membership_number', 'date_of_admission'
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('clients', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaccoDetailsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sacco_details')) {
            return;
        }

        Schema::create('sacco_details', function (Blueprint $table) {
            $table->id();
            $table->string('tagline')->nullable();
            $table->text('mission')->nullable();
            $table->text('vision')->nullable();
            $table->text('about_text')->nullable();
            $table->string('license_number')->nullable();
            $table->string('registration_number')->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('paybill_number')->nullable();
            $table->string('office_hours')->nullable();
            $table->json('featured_services')->nullable();
            $table->boolean('show_live_stats')->default(true);
            $table->unsignedInteger('member_count_override')->nullable();
            $table->unsignedInteger('branch_count_override')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('primary_color', 7)->default('#0d5c3d');
            $table->string('accent_color', 7)->default('#d4a012');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sacco_details');
    }
}

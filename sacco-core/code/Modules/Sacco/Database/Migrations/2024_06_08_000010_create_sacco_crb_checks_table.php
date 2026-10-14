<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSaccoCrbChecksTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('sacco_crb_checks')) {
            return;
        }

        Schema::create('sacco_crb_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('client_id')->index();
            $table->unsignedBigInteger('checked_by_id')->nullable();

            // Who/what was checked
            $table->string('id_number', 30)->nullable();
            $table->string('provider', 50)->default('metropol'); // metropol | transunion | creditinfo

            // Result
            $table->string('status', 30)->default('pending'); // pending | clean | listed | error | simulated
            $table->string('check_type', 30)->default('full'); // full | score_only | listing_only

            // Score & listing
            $table->integer('credit_score')->nullable();
            $table->string('score_band', 20)->nullable();    // A/B/C/D/E or excellent/good/fair/poor
            $table->boolean('is_listed')->default(false);    // listed on CRB
            $table->integer('negative_listings')->default(0);
            $table->integer('npl_count')->default(0);        // Non-performing loans

            // Narrative / recommendation
            $table->text('summary')->nullable();
            $table->string('recommendation', 50)->nullable(); // approve | review | decline

            // Raw API data
            $table->longText('raw_request')->nullable();
            $table->longText('raw_response')->nullable();

            // Reference
            $table->string('reference_number', 100)->nullable(); // CRB's own reference
            $table->string('consent_reference', 100)->nullable();// Consent token / member-signed
            $table->boolean('consent_obtained')->default(false);
            $table->timestamp('consent_date')->nullable();

            // Validity
            $table->date('valid_until')->nullable();         // CRB reports are valid for 30/90 days
            $table->string('error_message', 500)->nullable();

            $table->timestamps();

            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sacco_crb_checks');
    }
}

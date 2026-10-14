<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSaccoKycTables extends Migration
{
    public function up()
    {
        Schema::create('sacco_kyc_requirements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('category', ['identity', 'tax', 'address', 'employment', 'biometric', 'other'])->default('identity');
            $table->tinyInteger('mandatory')->default(1);
            $table->tinyInteger('requires_iprs')->default(0);
            $table->integer('sort_order')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });

        Schema::create('sacco_kyc_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('uploaded_by_id')->unsigned()->nullable();
            $table->bigInteger('reviewed_by_id')->unsigned()->nullable();
            $table->string('document_type', 50);
            $table->string('document_number')->nullable();
            $table->string('file_path')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->dateTime('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'document_type']);
        });

        Schema::create('sacco_kyc_verifications', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('client_id')->unsigned();
            $table->bigInteger('verified_by_id')->unsigned()->nullable();
            $table->enum('provider', ['iprs', 'nida', 'manual', 'crb'])->default('iprs');
            $table->string('id_number');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->enum('match_status', ['matched', 'mismatched', 'not_found', 'error', 'simulated'])->default('simulated');
            $table->json('response_data')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                if (!Schema::hasColumn('clients', 'kyc_status')) {
                    $table->enum('kyc_status', ['incomplete', 'pending_review', 'verified', 'rejected', 'expired'])->default('incomplete')->after('kyc_verified');
                }
                if (!Schema::hasColumn('clients', 'kyc_verified_at')) {
                    $table->dateTime('kyc_verified_at')->nullable()->after('kyc_status');
                }
                if (!Schema::hasColumn('clients', 'kyc_verified_by_id')) {
                    $table->bigInteger('kyc_verified_by_id')->unsigned()->nullable()->after('kyc_verified_at');
                }
                if (!Schema::hasColumn('clients', 'iprs_verified_at')) {
                    $table->dateTime('iprs_verified_at')->nullable()->after('kyc_verified_by_id');
                }
                if (!Schema::hasColumn('clients', 'kra_pin')) {
                    $table->string('kra_pin', 20)->nullable()->after('iprs_verified_at');
                }
                if (!Schema::hasColumn('clients', 'kyc_expiry_date')) {
                    $table->date('kyc_expiry_date')->nullable()->after('kra_pin');
                }
                if (!Schema::hasColumn('clients', 'kyc_notes')) {
                    $table->text('kyc_notes')->nullable()->after('kyc_expiry_date');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('clients')) {
            Schema::table('clients', function (Blueprint $table) {
                foreach (['kyc_notes', 'kyc_expiry_date', 'kra_pin', 'iprs_verified_at', 'kyc_verified_by_id', 'kyc_verified_at', 'kyc_status'] as $col) {
                    if (Schema::hasColumn('clients', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
        Schema::dropIfExists('sacco_kyc_verifications');
        Schema::dropIfExists('sacco_kyc_documents');
        Schema::dropIfExists('sacco_kyc_requirements');
    }
}

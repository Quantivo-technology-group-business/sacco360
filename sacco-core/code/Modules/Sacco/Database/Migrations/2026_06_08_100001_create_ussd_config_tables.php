<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUssdConfigTables extends Migration
{
    public function up(): void
    {
        // ── Per-member USSD configuration ─────────────────────────────────
        if (!Schema::hasTable('sacco_ussd_member_configs')) {
            Schema::create('sacco_ussd_member_configs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id')->unique();
                $table->tinyInteger('ussd_enabled')->default(0);
                $table->string('pin_hash')->nullable()->comment('bcrypt-hashed 4-digit PIN');
                $table->tinyInteger('pin_set')->default(0)->comment('Whether member has set a PIN');
                $table->decimal('daily_transaction_limit', 65, 6)->nullable()
                      ->comment('Per-member daily limit; null = use global config');
                $table->json('allowed_services')->nullable()
                      ->comment('Array of: balance, statement, transfer, loan, utility, deposit');
                $table->timestamp('last_access_at')->nullable();
                $table->unsignedTinyInteger('failed_pin_attempts')->default(0);
                $table->timestamp('locked_at')->nullable()
                      ->comment('Set when failed_pin_attempts exceeds threshold');
                $table->unsignedBigInteger('enabled_by_id')->nullable();
                $table->timestamp('enabled_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            });
        }

        // ── USSD session log ───────────────────────────────────────────────
        if (!Schema::hasTable('sacco_ussd_sessions')) {
            Schema::create('sacco_ussd_sessions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('client_id')->nullable()
                      ->comment('Null if caller not found');
                $table->string('session_id')->index()->comment('Session ID from telecom gateway');
                $table->string('phone_number', 30);
                $table->text('session_text')->nullable()->comment('Full USSD input string');
                $table->string('last_menu', 100)->nullable();
                $table->enum('status', ['active', 'completed', 'timeout', 'error'])->default('active');
                $table->string('exit_reason', 255)->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('ended_at')->nullable();
                $table->timestamps();

                $table->index(['client_id', 'created_at']);
                $table->index('phone_number');
            });
        }

        // ── Global USSD settings stored in settings table ──────────────────
        // Seeded via the controller on first save; no migration rows needed.
    }

    public function down(): void
    {
        Schema::dropIfExists('sacco_ussd_sessions');
        Schema::dropIfExists('sacco_ussd_member_configs');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Migration: Full Card Management Features
 *
 * Enhances existing sacco_debit_cards table and adds:
 *  - sacco_card_transactions  : full transaction ledger per card
 *  - sacco_card_disputes      : chargeback / dispute workflow
 *  - sacco_card_pin_logs      : PIN change audit trail
 *  - sacco_card_requests      : formal card application / replacement requests
 */
class AddCardManagementFeatures extends Migration
{
    public function up()
    {
        // =====================================================================
        // 1. ENHANCE EXISTING sacco_debit_cards
        // =====================================================================
        if (Schema::hasTable('sacco_debit_cards')) {
            Schema::table('sacco_debit_cards', function (Blueprint $table) {
                // Card type & network
                if (!Schema::hasColumn('sacco_debit_cards', 'card_type')) {
                    $table->enum('card_type', ['physical', 'virtual'])->default('physical')->after('savings_id');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'card_network')) {
                    $table->enum('card_network', ['visa', 'mastercard', 'local'])->default('visa')->after('card_type');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'card_name')) {
                    $table->string('card_name')->nullable()->after('card_network')
                        ->comment('Name embossed on card');
                }

                // Spending limits
                if (!Schema::hasColumn('sacco_debit_cards', 'atm_daily_limit')) {
                    $table->decimal('atm_daily_limit', 65, 6)->default(20000)->after('daily_limit');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'pos_daily_limit')) {
                    $table->decimal('pos_daily_limit', 65, 6)->default(100000)->after('atm_daily_limit');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'online_daily_limit')) {
                    $table->decimal('online_daily_limit', 65, 6)->default(50000)->after('pos_daily_limit');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'contactless_limit')) {
                    $table->decimal('contactless_limit', 65, 6)->default(5000)->after('online_daily_limit')
                        ->comment('Per-tap limit for contactless');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'monthly_limit')) {
                    $table->decimal('monthly_limit', 65, 6)->nullable()->after('contactless_limit');
                }

                // Channel toggles
                if (!Schema::hasColumn('sacco_debit_cards', 'atm_enabled')) {
                    $table->tinyInteger('atm_enabled')->default(1)->after('monthly_limit');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'pos_enabled')) {
                    $table->tinyInteger('pos_enabled')->default(1)->after('atm_enabled');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'online_enabled')) {
                    $table->tinyInteger('online_enabled')->default(0)->after('pos_enabled');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'contactless_enabled')) {
                    $table->tinyInteger('contactless_enabled')->default(1)->after('online_enabled');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'international_enabled')) {
                    $table->tinyInteger('international_enabled')->default(0)->after('contactless_enabled');
                }

                // PIN & security
                if (!Schema::hasColumn('sacco_debit_cards', 'pin_set')) {
                    $table->tinyInteger('pin_set')->default(0)->after('international_enabled');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'pin_failed_attempts')) {
                    $table->integer('pin_failed_attempts')->default(0)->after('pin_set');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'pin_locked')) {
                    $table->tinyInteger('pin_locked')->default(0)->after('pin_failed_attempts');
                }

                // Delivery
                if (!Schema::hasColumn('sacco_debit_cards', 'delivery_status')) {
                    $table->enum('delivery_status', ['not_dispatched', 'dispatched', 'delivered', 'returned'])
                        ->default('not_dispatched')->after('pin_locked');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'dispatched_date')) {
                    $table->date('dispatched_date')->nullable()->after('delivery_status');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'delivered_date')) {
                    $table->date('delivered_date')->nullable()->after('dispatched_date');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'delivery_address')) {
                    $table->text('delivery_address')->nullable()->after('delivered_date');
                }

                // Notifications
                if (!Schema::hasColumn('sacco_debit_cards', 'sms_alerts')) {
                    $table->tinyInteger('sms_alerts')->default(1)->after('delivery_address');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'email_alerts')) {
                    $table->tinyInteger('email_alerts')->default(0)->after('sms_alerts');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'alert_threshold')) {
                    $table->decimal('alert_threshold', 65, 6)->default(0)->after('email_alerts')
                        ->comment('Alert only on transactions above this amount (0 = all)');
                }

                // Metadata
                if (!Schema::hasColumn('sacco_debit_cards', 'issued_by_id')) {
                    $table->bigInteger('issued_by_id')->unsigned()->nullable()->after('alert_threshold');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'blocked_reason')) {
                    $table->string('blocked_reason')->nullable()->after('issued_by_id');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'cancelled_reason')) {
                    $table->string('cancelled_reason')->nullable()->after('blocked_reason');
                }
                if (!Schema::hasColumn('sacco_debit_cards', 'notes')) {
                    $table->text('notes')->nullable()->after('cancelled_reason');
                }
            });
        }

        // =====================================================================
        // 2. CARD TRANSACTIONS
        // =====================================================================
        if (!Schema::hasTable('sacco_card_transactions')) {
            Schema::create('sacco_card_transactions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_debit_card_id')->unsigned();
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('savings_id')->unsigned()->nullable();
                $table->string('transaction_reference')->unique();
                $table->string('external_reference')->nullable()->comment('Bank/switch reference');
                $table->enum('channel', ['atm', 'pos', 'online', 'contactless', 'mobile', 'reversal', 'other'])
                    ->default('pos');
                $table->enum('transaction_type', ['purchase', 'withdrawal', 'reversal', 'refund', 'fee',
                                                   'balance_inquiry', 'transfer', 'other'])
                    ->default('purchase');
                $table->decimal('amount', 65, 6);
                $table->string('currency', 3)->default('KES');
                $table->decimal('fee_amount', 65, 6)->default(0);
                $table->string('merchant_name')->nullable();
                $table->string('merchant_category')->nullable()->comment('MCC code or description');
                $table->string('merchant_location')->nullable();
                $table->string('terminal_id')->nullable();
                $table->decimal('balance_before', 65, 6)->nullable();
                $table->decimal('balance_after', 65, 6)->nullable();
                $table->enum('status', ['pending', 'approved', 'declined', 'reversed', 'failed'])
                    ->default('approved');
                $table->string('decline_reason')->nullable();
                $table->dateTime('transaction_at');
                $table->tinyInteger('is_international')->default(0);
                $table->timestamps();

                $table->index(['sacco_debit_card_id', 'transaction_at']);
                $table->index(['client_id', 'transaction_at']);
            });
        }

        // =====================================================================
        // 3. CARD DISPUTES / CHARGEBACKS
        // =====================================================================
        if (!Schema::hasTable('sacco_card_disputes')) {
            Schema::create('sacco_card_disputes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_debit_card_id')->unsigned();
                $table->bigInteger('sacco_card_transaction_id')->unsigned()->nullable();
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('resolved_by_id')->unsigned()->nullable();
                $table->string('dispute_reference')->unique()->nullable();
                $table->enum('dispute_type', [
                    'unauthorized_transaction',
                    'amount_mismatch',
                    'goods_not_received',
                    'duplicate_charge',
                    'atm_dispense_error',
                    'fraud',
                    'other',
                ])->default('unauthorized_transaction');
                $table->decimal('disputed_amount', 65, 6);
                $table->decimal('settled_amount', 65, 6)->nullable();
                $table->date('transaction_date')->nullable();
                $table->string('merchant_name')->nullable();
                $table->text('description');
                $table->text('supporting_documents')->nullable()->comment('Comma-separated file paths');
                $table->text('resolution_notes')->nullable();
                $table->enum('status', ['submitted', 'under_investigation', 'resolved_in_favour',
                                        'resolved_against', 'cancelled'])->default('submitted');
                $table->date('submitted_date');
                $table->date('resolution_date')->nullable();
                $table->integer('resolution_days')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 4. CARD PIN AUDIT LOG
        // =====================================================================
        if (!Schema::hasTable('sacco_card_pin_logs')) {
            Schema::create('sacco_card_pin_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('sacco_debit_card_id')->unsigned();
                $table->bigInteger('client_id')->unsigned();
                $table->enum('action', ['set', 'changed', 'reset_by_staff', 'locked', 'unlocked'])
                    ->default('set');
                $table->bigInteger('actioned_by_id')->unsigned()->nullable()
                    ->comment('NULL = self-service');
                $table->string('channel')->nullable()->comment('atm, app, branch');
                $table->string('ip_address')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================================
        // 5. CARD REQUESTS (application + replacement)
        // =====================================================================
        if (!Schema::hasTable('sacco_card_requests')) {
            Schema::create('sacco_card_requests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->bigInteger('client_id')->unsigned();
                $table->bigInteger('sacco_debit_card_id')->unsigned()->nullable()
                    ->comment('Populated when card is issued from this request');
                $table->bigInteger('savings_id')->unsigned()->nullable();
                $table->bigInteger('created_by_id')->unsigned()->nullable();
                $table->bigInteger('processed_by_id')->unsigned()->nullable();
                $table->enum('request_type', ['new', 'replacement', 'virtual'])->default('new');
                $table->enum('replacement_reason', ['lost', 'stolen', 'damaged', 'expired', 'name_change', 'other'])
                    ->nullable();
                $table->bigInteger('replaced_card_id')->unsigned()->nullable();
                $table->string('preferred_name')->nullable()->comment('Name to emboss on card');
                $table->text('delivery_address')->nullable();
                $table->enum('delivery_method', ['branch_pickup', 'courier', 'post'])->default('branch_pickup');
                $table->enum('card_network', ['visa', 'mastercard', 'local'])->default('visa');
                $table->enum('card_type', ['physical', 'virtual'])->default('physical');
                $table->decimal('card_fee', 65, 6)->default(0);
                $table->tinyInteger('fee_waived')->default(0);
                $table->text('notes')->nullable();
                $table->enum('status', ['pending', 'approved', 'processing', 'dispatched', 'completed', 'rejected'])
                    ->default('pending');
                $table->date('requested_date');
                $table->date('processed_date')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('sacco_card_requests');
        Schema::dropIfExists('sacco_card_pin_logs');
        Schema::dropIfExists('sacco_card_disputes');
        Schema::dropIfExists('sacco_card_transactions');
    }
}

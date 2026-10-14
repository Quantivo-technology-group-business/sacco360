<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateShareMarketplaceTables extends Migration
{
    public function up()
    {
        Schema::create('share_listings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('share_id')->unsigned();
            $table->bigInteger('seller_client_id')->unsigned();
            $table->integer('quantity');
            $table->integer('remaining_quantity');
            $table->decimal('price_per_share', 65, 6);
            $table->enum('status', ['active', 'partially_sold', 'sold_out', 'cancelled'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index('share_id');
            $table->index('seller_client_id');
            $table->index('status');
        });

        Schema::create('share_transfer_requests', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('reference')->nullable();
            $table->enum('type', ['marketplace', 'direct']);
            $table->bigInteger('listing_id')->unsigned()->nullable();
            $table->bigInteger('seller_client_id')->unsigned();
            $table->bigInteger('buyer_client_id')->unsigned();
            $table->bigInteger('seller_share_id')->unsigned();
            $table->bigInteger('buyer_share_id')->unsigned();
            $table->integer('quantity');
            $table->decimal('price_per_share', 65, 6);
            $table->decimal('total_amount', 65, 6);
            $table->bigInteger('buyer_savings_id')->unsigned()->nullable();
            $table->bigInteger('buyer_hold_transaction_id')->unsigned()->nullable();
            $table->bigInteger('seller_savings_id')->unsigned()->nullable();
            $table->bigInteger('seller_credit_transaction_id')->unsigned()->nullable();
            $table->bigInteger('seller_share_transaction_id')->unsigned()->nullable();
            $table->bigInteger('buyer_share_transaction_id')->unsigned()->nullable();
            $table->enum('status', [
                'pending_buyer_acceptance',
                'pending_admin',
                'approved',
                'rejected',
                'cancelled',
            ])->default('pending_admin');
            $table->bigInteger('created_by_id')->unsigned()->nullable();
            $table->bigInteger('approved_by_id')->unsigned()->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->bigInteger('rejected_by_id')->unsigned()->nullable();
            $table->text('rejected_notes')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->index('listing_id');
            $table->index('seller_client_id');
            $table->index('buyer_client_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('share_transfer_requests');
        Schema::dropIfExists('share_listings');
    }
}

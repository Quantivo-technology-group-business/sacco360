<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;
use Modules\User\Entities\User;

class ShareTransferRequest extends Model
{
    protected $fillable = [
        'reference', 'type', 'listing_id', 'seller_client_id', 'buyer_client_id',
        'seller_share_id', 'buyer_share_id', 'quantity', 'price_per_share', 'total_amount',
        'buyer_savings_id', 'buyer_hold_transaction_id', 'seller_savings_id',
        'seller_credit_transaction_id', 'seller_share_transaction_id', 'buyer_share_transaction_id',
        'status', 'created_by_id', 'approved_by_id', 'approved_at',
        'rejected_by_id', 'rejected_notes', 'rejected_at',
    ];

    protected $dates = ['approved_at', 'rejected_at'];

    public function listing()
    {
        return $this->belongsTo(ShareListing::class, 'listing_id');
    }

    public function seller()
    {
        return $this->belongsTo(Client::class, 'seller_client_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Client::class, 'buyer_client_id');
    }

    public function seller_share()
    {
        return $this->belongsTo(Share::class, 'seller_share_id');
    }

    public function buyer_share()
    {
        return $this->belongsTo(Share::class, 'buyer_share_id');
    }

    public function buyer_savings()
    {
        return $this->belongsTo(Savings::class, 'buyer_savings_id');
    }

    public function seller_savings()
    {
        return $this->belongsTo(Savings::class, 'seller_savings_id');
    }

    public function buyer_hold_transaction()
    {
        return $this->belongsTo(SavingsTransaction::class, 'buyer_hold_transaction_id');
    }

    public function seller_credit_transaction()
    {
        return $this->belongsTo(SavingsTransaction::class, 'seller_credit_transaction_id');
    }

    public function seller_share_transaction()
    {
        return $this->belongsTo(ShareTransaction::class, 'seller_share_transaction_id');
    }

    public function buyer_share_transaction()
    {
        return $this->belongsTo(ShareTransaction::class, 'buyer_share_transaction_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approved_by()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function rejected_by()
    {
        return $this->belongsTo(User::class, 'rejected_by_id');
    }
}

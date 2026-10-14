<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Share\Entities\Share;
use Modules\User\Entities\User;

class ShareListing extends Model
{
    protected $fillable = [
        'created_by_id', 'share_id', 'seller_client_id', 'quantity',
        'remaining_quantity', 'price_per_share', 'status', 'notes', 'expires_at',
    ];

    protected $dates = ['expires_at'];

    public function share()
    {
        return $this->belongsTo(Share::class);
    }

    public function seller()
    {
        return $this->belongsTo(Client::class, 'seller_client_id');
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function transfer_requests()
    {
        return $this->hasMany(ShareTransferRequest::class, 'listing_id');
    }
}

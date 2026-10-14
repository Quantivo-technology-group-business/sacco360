<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoTransfer extends Model
{
    protected $fillable = [
        'created_by_id', 'approved_by_id', 'client_id', 'recipient_client_id', 'branch_id',
        'transfer_type', 'source_id', 'destination_id', 'amount',
        'transfer_date', 'notes', 'reference', 'status',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

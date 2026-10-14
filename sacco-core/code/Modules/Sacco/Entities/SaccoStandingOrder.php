<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoStandingOrder extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'source_savings_id', 'destination_loan_id',
        'destination_savings_id', 'amount', 'frequency', 'start_date', 'end_date',
        'next_run_date', 'status', 'description',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

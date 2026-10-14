<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoWelfareContribution extends Model
{
    protected $fillable = [
        'client_id', 'sacco_welfare_product_id', 'amount',
        'contribution_date', 'period', 'savings_transaction_id',
        'created_by_id', 'status',
    ];

    protected $dates = ['contribution_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(SaccoWelfareProduct::class, 'sacco_welfare_product_id');
    }
}

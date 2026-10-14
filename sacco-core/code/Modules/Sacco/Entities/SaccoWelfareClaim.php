<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoWelfareClaim extends Model
{
    protected $fillable = [
        'created_by_id', 'reviewed_by_id', 'approved_by_id',
        'client_id', 'sacco_welfare_product_id', 'claim_reference',
        'event_date', 'claim_date',
        'beneficiary_name', 'beneficiary_relationship', 'beneficiary_phone',
        'beneficiary_id_number', 'beneficiary_account',
        'amount_claimed', 'amount_approved', 'remarks', 'rejection_reason',
        'status', 'payment_date', 'payment_reference',
    ];

    protected $dates = ['event_date', 'claim_date', 'payment_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function product()
    {
        return $this->belongsTo(SaccoWelfareProduct::class, 'sacco_welfare_product_id');
    }

    public function documents()
    {
        return $this->hasMany(SaccoWelfareClaimDocument::class);
    }
}

<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoUtilityPayment extends Model
{
    protected $table = 'sacco_utility_payments';

    protected $guarded = ['id'];

    public function provider()
    {
        return $this->belongsTo(SaccoUtilityProvider::class, 'provider_id');
    }
}

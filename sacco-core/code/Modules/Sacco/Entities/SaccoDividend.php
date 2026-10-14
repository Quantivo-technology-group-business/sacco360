<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoDividend extends Model
{
    protected $fillable = [
        'created_by_id', 'share_product_id', 'name', 'dividend_per_share',
        'total_pool', 'declaration_date', 'payment_date', 'payout_method',
        'status', 'agm_resolution',
    ];

    public function allocations()
    {
        return $this->hasMany(SaccoDividendAllocation::class);
    }
}

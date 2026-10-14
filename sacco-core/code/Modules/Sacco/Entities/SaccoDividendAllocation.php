<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoDividendAllocation extends Model
{
    protected $fillable = [
        'sacco_dividend_id', 'share_id', 'client_id', 'shares_held', 'amount', 'status',
    ];

    public function dividend()
    {
        return $this->belongsTo(SaccoDividend::class, 'sacco_dividend_id');
    }
}

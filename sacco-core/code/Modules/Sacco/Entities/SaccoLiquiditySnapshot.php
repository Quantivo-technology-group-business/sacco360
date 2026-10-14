<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoLiquiditySnapshot extends Model
{
    protected $table = 'sacco_liquidity_snapshots';

    protected $fillable = [
        'snapshot_date', 'liquid_assets', 'total_deposits', 'liquidity_ratio', 'status',
    ];
}

<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoCrossSellCampaign extends Model
{
    protected $fillable = [
        'name', 'source_wing', 'target_wing', 'min_balance', 'message',
        'start_date', 'end_date', 'active',
    ];
}

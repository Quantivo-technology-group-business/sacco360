<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoDormancyLog extends Model
{
    protected $fillable = [
        'client_id', 'sacco_dormancy_rule_id', 'action',
        'action_date', 'fee_charged', 'actioned_by_id', 'notes',
    ];

    protected $dates = ['action_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function rule()
    {
        return $this->belongsTo(SaccoDormancyRule::class, 'sacco_dormancy_rule_id');
    }
}

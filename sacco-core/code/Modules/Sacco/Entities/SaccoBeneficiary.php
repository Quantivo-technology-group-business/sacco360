<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoBeneficiary extends Model
{
    protected $fillable = [
        'client_id', 'nickname', 'name', 'phone', 'is_active',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

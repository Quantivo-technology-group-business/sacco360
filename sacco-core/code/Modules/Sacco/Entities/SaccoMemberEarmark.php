<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\User\Entities\User;

class SaccoMemberEarmark extends Model
{
    protected $fillable = [
        'created_by_id', 'client_id', 'severity', 'restriction_type',
        'title', 'message', 'show_at_teller', 'expires_at', 'status',
    ];

    protected $dates = ['expires_at'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}

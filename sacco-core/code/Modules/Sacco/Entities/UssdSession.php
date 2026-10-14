<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class UssdSession extends Model
{
    protected $table = 'sacco_ussd_sessions';

    protected $fillable = [
        'client_id', 'session_id', 'phone_number', 'session_text',
        'last_menu', 'status', 'exit_reason', 'started_at', 'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
}

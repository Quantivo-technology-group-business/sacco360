<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoStatementLog extends Model
{
    protected $fillable = [
        'sacco_statement_schedule_id', 'client_id',
        'statement_type', 'period_from', 'period_to',
        'delivery_channel', 'recipient_email', 'recipient_phone',
        'file_path', 'status', 'error_message',
        'sent_at', 'opened_at', 'triggered_by_id',
    ];

    protected $dates = ['period_from', 'period_to', 'sent_at', 'opened_at'];

    public function schedule()
    {
        return $this->belongsTo(SaccoStatementSchedule::class, 'sacco_statement_schedule_id');
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;

class SaccoMeetingAttendance extends Model
{
    protected $fillable = [
        'sacco_meeting_id', 'client_id', 'user_id',
        'attendee_name', 'attendee_title', 'attendance_type',
        'attended', 'proxy_for', 'signature_path',
    ];

    public function meeting()
    {
        return $this->belongsTo(SaccoMeeting::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}

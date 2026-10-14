<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMeetingAgendaItem extends Model
{
    protected $fillable = [
        'sacco_meeting_id', 'order', 'title', 'description',
        'presenter', 'duration_minutes', 'outcome', 'status',
    ];

    public function meeting()
    {
        return $this->belongsTo(SaccoMeeting::class);
    }
}

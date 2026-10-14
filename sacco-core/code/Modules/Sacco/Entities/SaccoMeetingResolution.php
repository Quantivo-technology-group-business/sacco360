<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMeetingResolution extends Model
{
    protected $fillable = [
        'sacco_meeting_id', 'resolution_number', 'resolution_text',
        'votes_for', 'votes_against', 'abstentions', 'result',
        'action_owner_id', 'action_deadline', 'implementation_status',
    ];

    protected $dates = ['action_deadline'];

    public function meeting()
    {
        return $this->belongsTo(SaccoMeeting::class);
    }

    public function getTotalVotesAttribute()
    {
        return $this->votes_for + $this->votes_against + $this->abstentions;
    }
}

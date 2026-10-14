<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Branch\Entities\Branch;

class SaccoMeeting extends Model
{
    protected $fillable = [
        'created_by_id', 'branch_id', 'title', 'meeting_type',
        'scheduled_date', 'scheduled_time', 'venue', 'notice',
        'quorum_required', 'members_present', 'members_apology',
        'minutes', 'status', 'actual_date',
    ];

    protected $dates = ['scheduled_date', 'actual_date'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function agendaItems()
    {
        return $this->hasMany(SaccoMeetingAgendaItem::class)->orderBy('order');
    }

    public function attendances()
    {
        return $this->hasMany(SaccoMeetingAttendance::class);
    }

    public function resolutions()
    {
        return $this->hasMany(SaccoMeetingResolution::class);
    }

    public function hasQuorum(): bool
    {
        if (!$this->quorum_required) {
            return true;
        }
        return ($this->members_present ?? 0) >= $this->quorum_required;
    }
}

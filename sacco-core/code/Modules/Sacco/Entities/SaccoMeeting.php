<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Branch\Entities\Branch;

class SaccoMeeting extends Model
{
    protected $fillable = [
        'created_by_id', 'branch_id', 'sacco_group_id', 'title', 'meeting_type',
        'scheduled_date', 'scheduled_time', 'venue', 'notice',
        'quorum_required', 'members_present', 'members_apology',
        'minutes', 'status', 'actual_date',
    ];

    protected $dates = ['scheduled_date', 'actual_date'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * The chama group this meeting belongs to. NULL means SACCO-wide.
     */
    public function group()
    {
        return $this->belongsTo(SaccoGroup::class, 'sacco_group_id');
    }

    /**
     * Meeting types a member may see when the meeting is SACCO-wide
     * (sacco_group_id IS NULL). Governance types are excluded so board,
     * credit-committee and supervisory minutes never reach the member portal.
     */
    public const MEMBER_VISIBLE_TYPES = ['agm', 'sgm', 'other'];

    /**
     * Restrict a query to meetings the given member is entitled to see:
     * meetings of groups they belong to, plus SACCO-wide general meetings.
     */
    public function scopeVisibleToMember($query, int $clientId)
    {
        $groupIds = SaccoGroupMember::where('client_id', $clientId)
            ->where('status', 'active')
            ->pluck('sacco_group_id');

        return $query->where(function ($q) use ($groupIds) {
            $q->whereIn('sacco_group_id', $groupIds)
              ->orWhere(function ($q2) {
                  $q2->whereNull('sacco_group_id')
                     ->whereIn('meeting_type', self::MEMBER_VISIBLE_TYPES);
              });
        });
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

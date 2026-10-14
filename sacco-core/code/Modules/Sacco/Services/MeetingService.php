<?php

namespace Modules\Sacco\Services;

use Modules\Sacco\Entities\SaccoMeeting;
use Modules\Sacco\Entities\SaccoMeetingAgendaItem;
use Modules\Sacco\Entities\SaccoMeetingAttendance;
use Modules\Sacco\Entities\SaccoMeetingResolution;

class MeetingService
{
    /**
     * Create a meeting with optional agenda items.
     */
    public function create(array $data, array $agendaItems = []): SaccoMeeting
    {
        $meeting = SaccoMeeting::create($data);

        foreach ($agendaItems as $i => $item) {
            SaccoMeetingAgendaItem::create(array_merge($item, [
                'sacco_meeting_id' => $meeting->id,
                'order'            => $item['order'] ?? ($i + 1),
            ]));
        }

        return $meeting;
    }

    /**
     * Record attendance for a meeting.
     */
    public function recordAttendance(SaccoMeeting $meeting, array $attendees): void
    {
        foreach ($attendees as $attendee) {
            SaccoMeetingAttendance::updateOrCreate(
                [
                    'sacco_meeting_id' => $meeting->id,
                    'client_id'        => $attendee['client_id'] ?? null,
                    'user_id'          => $attendee['user_id'] ?? null,
                ],
                $attendee
            );
        }

        // Update headcount on the meeting
        $meeting->members_present = SaccoMeetingAttendance::where('sacco_meeting_id', $meeting->id)
            ->where('attended', 1)
            ->count();
        $meeting->save();
    }

    /**
     * Add a resolution to a meeting.
     */
    public function addResolution(SaccoMeeting $meeting, array $data): SaccoMeetingResolution
    {
        // Auto-number resolutions: RESOLUTION-001, RESOLUTION-002, ...
        $count = SaccoMeetingResolution::where('sacco_meeting_id', $meeting->id)->count();
        $number = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        return SaccoMeetingResolution::create(array_merge($data, [
            'sacco_meeting_id'  => $meeting->id,
            'resolution_number' => $data['resolution_number'] ?? "RES-{$number}",
        ]));
    }

    /**
     * Mark a meeting as held and record summary data.
     */
    public function markHeld(SaccoMeeting $meeting, array $data): void
    {
        $meeting->status          = 'held';
        $meeting->actual_date     = $data['actual_date'] ?? $meeting->scheduled_date;
        $meeting->members_present = $data['members_present'] ?? $meeting->members_present;
        $meeting->members_apology = $data['members_apology'] ?? null;
        $meeting->minutes         = $data['minutes'] ?? null;
        $meeting->save();
    }

    /**
     * Check if a meeting has quorum.
     */
    public function hasQuorum(SaccoMeeting $meeting): bool
    {
        return $meeting->hasQuorum();
    }

    /**
     * Get upcoming meetings.
     */
    public function getUpcoming(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoMeeting::where('status', 'scheduled')
            ->whereBetween('scheduled_date', [now(), now()->addDays($days)])
            ->orderBy('scheduled_date')
            ->get();
    }
}

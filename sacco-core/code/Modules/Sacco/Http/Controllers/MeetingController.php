<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoMeeting;
use Modules\Sacco\Entities\SaccoMeetingAgendaItem;
use Modules\Sacco\Entities\SaccoMeetingResolution;
use Modules\Sacco\Services\MeetingService;
use Modules\Client\Entities\Client;
use Modules\Branch\Entities\Branch;
use Yajra\DataTables\Facades\DataTables;

class MeetingController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware('permission:sacco.meetings.index')->only(['index', 'show', 'get_meetings']);
        $this->middleware('permission:sacco.meetings.create')->only(['create', 'store', 'addAgendaItem', 'addResolution', 'markHeld']);
        $this->middleware('permission:sacco.meetings.attendance')->only(['recordAttendance']);
    }

    public function index()
    {
        $now = now();

        // Upcoming meetings (scheduled, date >= today)
        $upcoming = SaccoMeeting::where('status', 'scheduled')
            ->where('scheduled_date', '>=', $now->toDateString())
            ->orderBy('scheduled_date')
            ->withCount(['agendaItems', 'resolutions', 'attendances'])
            ->take(6)
            ->get();

        // Stats
        $upcomingCount = SaccoMeeting::where('status', 'scheduled')
            ->where('scheduled_date', '>=', $now->toDateString())
            ->count();

        $heldThisMonth = SaccoMeeting::where('status', 'held')
            ->whereYear('scheduled_date', $now->year)
            ->whereMonth('scheduled_date', $now->month)
            ->count();

        $totalHeld = SaccoMeeting::where('status', 'held')->count();
        $withQuorum = SaccoMeeting::where('status', 'held')
            ->whereNotNull('quorum_required')
            ->whereRaw('members_present >= quorum_required')
            ->count();
        $quorumRate = $totalHeld > 0 ? round(($withQuorum / $totalHeld) * 100) : 0;

        return theme_view('sacco::meeting.index', compact(
            'upcoming', 'upcomingCount', 'heldThisMonth', 'quorumRate'
        ));
    }

    public function get_meetings(Request $request)
    {
        $query = SaccoMeeting::withCount(['agendaItems', 'resolutions', 'attendances'])
            ->orderByDesc('scheduled_date');

        return DataTables::of($query)
            ->addColumn('type_label', fn($m) => strtoupper($m->meeting_type))
            ->addColumn('status_badge', function ($m) {
                $colours = ['scheduled' => 'primary', 'held' => 'success', 'postponed' => 'warning', 'cancelled' => 'danger'];
                $c = $colours[$m->status] ?? 'secondary';
                return '<span class="badge badge-' . $c . '">' . ucfirst($m->status) . '</span>';
            })
            ->addColumn('action', fn($m) =>
                '<a href="' . url('sacco/meeting/' . $m->id . '/show') . '" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> View</a>'
            )
            ->rawColumns(['status_badge', 'action'])
            ->make(true);
    }

    public function create()
    {
        $branches = Branch::where('active', 1)->orderBy('name')->get();
        return theme_view('sacco::meeting.create', compact('branches'));
    }

    public function store(Request $request, MeetingService $service)
    {
        $request->validate([
            'title'          => 'required|string|max:200',
            'meeting_type'   => 'required|in:agm,sgm,board,credit_committee,supervisory,management,other',
            'scheduled_date' => 'required|date',
        ]);

        $agendaItems = [];
        if ($request->has('agenda_titles')) {
            foreach ($request->agenda_titles as $i => $title) {
                if (!empty($title)) {
                    $agendaItems[] = [
                        'title'       => $title,
                        'description' => $request->agenda_descriptions[$i] ?? null,
                        'presenter'   => $request->agenda_presenters[$i] ?? null,
                        'order'       => $i + 1,
                    ];
                }
            }
        }

        $meeting = $service->create(
            array_merge(
                $request->only([
                    'title', 'meeting_type', 'scheduled_date', 'scheduled_time',
                    'venue', 'notice', 'quorum_required', 'branch_id',
                ]),
                ['created_by_id' => Auth::id(), 'status' => 'scheduled']
            ),
            $agendaItems
        );

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        return redirect('sacco/meeting/' . $meeting->id . '/show');
    }

    public function show($id)
    {
        $meeting = SaccoMeeting::with([
            'agendaItems', 'resolutions', 'attendances.client',
        ])->findOrFail($id);
        $clients = Client::where('status', 1)->orderBy('first_name')->get();
        return theme_view('sacco::meeting.show', compact('meeting', 'clients'));
    }

    public function addAgendaItem(Request $request, $id)
    {
        $request->validate(['title' => 'required|string|max:200']);
        $meeting  = SaccoMeeting::findOrFail($id);
        $maxOrder = SaccoMeetingAgendaItem::where('sacco_meeting_id', $id)->max('order') ?? 0;
        SaccoMeetingAgendaItem::create(array_merge(
            $request->only(['title', 'description', 'presenter', 'duration_minutes']),
            ['sacco_meeting_id' => $id, 'order' => $maxOrder + 1]
        ));
        \flash('Agenda item added.')->success()->important();
        return redirect('sacco/meeting/' . $id . '/show');
    }

    public function addResolution(Request $request, $id, MeetingService $service)
    {
        $request->validate(['resolution_text' => 'required|string']);
        $meeting = SaccoMeeting::findOrFail($id);
        $service->addResolution($meeting, $request->only([
            'resolution_text', 'votes_for', 'votes_against', 'abstentions',
            'result', 'action_owner_id', 'action_deadline',
        ]));
        \flash('Resolution recorded.')->success()->important();
        return redirect('sacco/meeting/' . $id . '/show');
    }

    public function recordAttendance(Request $request, $id, MeetingService $service)
    {
        $meeting   = SaccoMeeting::findOrFail($id);
        $attendees = [];
        if ($request->has('attendee_client_ids')) {
            foreach ($request->attendee_client_ids as $clientId) {
                if (!empty($clientId)) {
                    $attendees[] = [
                        'client_id'       => $clientId,
                        'attendance_type' => 'member',
                        'attended'        => 1,
                    ];
                }
            }
        }
        $service->recordAttendance($meeting, $attendees);
        \flash('Attendance recorded.')->success()->important();
        return redirect('sacco/meeting/' . $id . '/show');
    }

    public function markHeld(Request $request, $id, MeetingService $service)
    {
        $request->validate(['actual_date' => 'required|date']);
        $meeting = SaccoMeeting::findOrFail($id);
        $service->markHeld($meeting, $request->only([
            'actual_date', 'members_present', 'members_apology', 'minutes',
        ]));
        \flash('Meeting marked as held.')->success()->important();
        return redirect('sacco/meeting/' . $id . '/show');
    }
}

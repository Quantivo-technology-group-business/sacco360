@extends('core::layouts.master')
@section('title') Meetings & Events @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
    <style>
        /* ── Event card layout ─────────────────────────────── */
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }
        .event-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8eaf0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow .18s;
        }
        .event-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,.10); }

        /* coloured top accent strip + date badge */
        .event-card-header {
            display: flex;
            align-items: flex-start;
            padding: 16px 18px 14px;
            gap: 14px;
        }
        .date-badge {
            min-width: 54px;
            text-align: center;
            border-radius: 10px;
            padding: 8px 0 6px;
            flex-shrink: 0;
        }
        .date-badge .dow   { font-size: 10px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; opacity: .85; }
        .date-badge .day   { font-size: 26px; font-weight: 800; line-height: 1; }
        .date-badge .month { font-size: 11px; font-weight: 600; letter-spacing: .06em; text-transform: uppercase; opacity: .8; }

        /* colour rotation for date badges */
        .badge-purple { background: #6c5ce7; color: #fff; }
        .badge-teal   { background: #00b894; color: #fff; }
        .badge-coral  { background: #e17055; color: #fff; }
        .badge-blue   { background: #0984e3; color: #fff; }
        .badge-amber  { background: #fdcb6e; color: #5d4a00; }
        .badge-pink   { background: #e84393; color: #fff; }

        .event-meta { flex: 1; min-width: 0; }
        .event-meta .event-title {
            font-size: 15px;
            font-weight: 700;
            color: #2d3436;
            margin: 0 0 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .event-meta .event-sub {
            font-size: 12px;
            color: #636e72;
        }

        /* attendance progress */
        .event-progress {
            padding: 0 18px 14px;
        }
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #636e72;
            margin-bottom: 5px;
        }
        .progress { height: 6px; border-radius: 3px; background: #f0f0f5; margin-bottom: 0; }
        .progress-bar { border-radius: 3px; }

        /* card footer */
        .event-card-footer {
            padding: 10px 18px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid #f2f3f7;
            margin-top: auto;
        }
        .event-status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .status-scheduled { background: #e8f4fd; color: #1a73e8; }
        .status-held      { background: #e6f9f0; color: #0d7a48; }
        .status-postponed { background: #fff8e1; color: #b7790a; }
        .status-cancelled { background: #fce8e8; color: #c0392b; }

        /* stat cards */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e8eaf0;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .stat-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            flex-shrink: 0;
        }
        .stat-icon.purple { background: #f0eeff; color: #6c5ce7; }
        .stat-icon.teal   { background: #e0faf4; color: #00b894; }
        .stat-icon.blue   { background: #e6f2ff; color: #0984e3; }
        .stat-body .stat-value { font-size: 26px; font-weight: 800; color: #2d3436; line-height: 1; margin-bottom: 3px; }
        .stat-body .stat-label { font-size: 12px; color: #636e72; font-weight: 500; }

        /* section heading */
        .section-heading {
            font-size: 16px;
            font-weight: 700;
            color: #2d3436;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-heading .count-pill {
            font-size: 11px;
            background: #f0eeff;
            color: #6c5ce7;
            padding: 2px 9px;
            border-radius: 20px;
            font-weight: 700;
        }

        /* empty state */
        .empty-events {
            text-align: center;
            padding: 40px 20px;
            color: #b2bec3;
        }
        .empty-events i { font-size: 48px; margin-bottom: 12px; display: block; }
    </style>
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col">
                <h1 style="font-size:22px;font-weight:800;color:#2d3436;margin:0">
                    Meetings &amp; Events
                </h1>
                <p style="margin:2px 0 0;font-size:13px;color:#636e72">{{ config('app.name') }}</p>
            </div>
            <div class="col-auto">
                @can('sacco.meetings.create')
                    <a href="{{ url('sacco/meeting/create') }}" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Schedule Meeting
                    </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        {{-- ── Stats row ─────────────────────────────────── --}}
        <div class="row mb-4">
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-calendar-alt"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{{ $upcomingCount }}</div>
                        <div class="stat-label">Upcoming meetings</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon teal"><i class="fas fa-users"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{{ $heldThisMonth }}</div>
                        <div class="stat-label">Meetings held this month</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-check-circle"></i></div>
                    <div class="stat-body">
                        <div class="stat-value">{{ $quorumRate }}%</div>
                        <div class="stat-label">Quorum success rate</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Upcoming events grid ──────────────────────── --}}
        <div class="section-heading">
            <i class="fas fa-calendar-check" style="color:#6c5ce7"></i>
            Upcoming meetings
            <span class="count-pill">{{ $upcomingCount }}</span>
        </div>

        @php
            $badgeClasses = ['badge-purple','badge-teal','badge-coral','badge-blue','badge-amber','badge-pink'];
            $days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        @endphp

        @if($upcoming->isEmpty())
            <div class="empty-events">
                <i class="fas fa-calendar-times"></i>
                <p style="font-size:15px;font-weight:600">No upcoming meetings scheduled</p>
                @can('sacco.meetings.create')
                    <a href="{{ url('sacco/meeting/create') }}" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-plus mr-1"></i> Schedule one now
                    </a>
                @endcan
            </div>
        @else
        <div class="events-grid">
            @foreach($upcoming as $i => $m)
                @php
                    $date      = \Carbon\Carbon::parse($m->scheduled_date);
                    $dow       = $days[$date->dayOfWeek];
                    $dayNum    = $date->day;
                    $mon       = $months[$date->month - 1];
                    $colorCls  = $badgeClasses[$i % count($badgeClasses)];
                    $quorum    = $m->quorum_required ?: 0;
                    $present   = $m->attendances_count ?: 0;
                    $pct       = $quorum > 0 ? min(100, round(($present / $quorum) * 100)) : 0;
                    $timeStr   = $m->scheduled_time ? \Carbon\Carbon::parse($m->scheduled_time)->format('g:i A') : 'TBD';
                    $typeLabel = strtoupper(str_replace('_', ' ', $m->meeting_type));
                @endphp
                <div class="event-card">
                    <div class="event-card-header">
                        <div class="date-badge {{ $colorCls }}">
                            <div class="dow">{{ $dow }}</div>
                            <div class="day">{{ $dayNum }}</div>
                            <div class="month">{{ $mon }}</div>
                        </div>
                        <div class="event-meta">
                            <div class="event-title" title="{{ $m->title }}">{{ $m->title }}</div>
                            <div class="event-sub">
                                <i class="fas fa-tag mr-1"></i>{{ $typeLabel }}
                                &nbsp;&bull;&nbsp;
                                <i class="fas fa-clock mr-1"></i>{{ $timeStr }}
                            </div>
                            @if($m->venue)
                            <div class="event-sub mt-1">
                                <i class="fas fa-map-marker-alt mr-1"></i>{{ $m->venue }}
                            </div>
                            @endif
                        </div>
                    </div>

                    @if($quorum > 0)
                    <div class="event-progress">
                        <div class="progress-label">
                            <span><i class="fas fa-users mr-1"></i>{{ $present }} attended of {{ $quorum }} quorum</span>
                            <span>{{ $pct }}%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar {{ $pct >= 100 ? 'bg-success' : ($pct >= 60 ? 'bg-info' : 'bg-warning') }}"
                                 style="width:{{ $pct }}%"></div>
                        </div>
                    </div>
                    @else
                    <div class="event-progress">
                        <div class="progress-label">
                            <span><i class="fas fa-list mr-1"></i>{{ $m->agenda_items_count }} agenda items</span>
                            <span>{{ $m->resolutions_count }} resolutions</span>
                        </div>
                    </div>
                    @endif

                    <div class="event-card-footer">
                        <span class="event-status-badge status-{{ $m->status }}">
                            {{ $m->status === 'scheduled' ? 'Confirmed' : ucfirst($m->status) }}
                        </span>
                        <a href="{{ url('sacco/meeting/'.$m->id.'/show') }}"
                           class="btn btn-sm btn-outline-primary" style="font-size:12px;padding:4px 14px;border-radius:20px">
                            View <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        {{-- ── All meetings table ────────────────────────── --}}
        <div class="section-heading mt-4">
            <i class="fas fa-list" style="color:#636e72"></i>
            All meetings
        </div>
        <div class="card" style="border-radius:12px;border:1px solid #e8eaf0">
            <div class="card-body p-0">
                <table id="data-table" class="table table-hover mb-0" style="border-radius:12px;overflow:hidden">
                    <thead style="background:#f8f9fc">
                        <tr>
                            <th>Title</th><th>Type</th><th>Date</th><th>Venue</th>
                            <th>Agenda</th><th>Resolutions</th><th>Status</th><th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

    </div>{{-- /container-fluid --}}
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ url("sacco/meeting/get_meetings") }}',
            columns: [
                { data: 'title',              name: 'title' },
                { data: 'type_label',         name: 'meeting_type' },
                { data: 'scheduled_date',     name: 'scheduled_date' },
                { data: 'venue',              name: 'venue', defaultContent: '—' },
                { data: 'agenda_items_count', name: 'agenda_items_count', searchable: false },
                { data: 'resolutions_count',  name: 'resolutions_count',  searchable: false },
                { data: 'status_badge',       name: 'status', orderable: false },
                { data: 'action',             name: 'action', orderable: false, searchable: false }
            ],
            order: [[2, 'desc']],
            pageLength: 25,
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

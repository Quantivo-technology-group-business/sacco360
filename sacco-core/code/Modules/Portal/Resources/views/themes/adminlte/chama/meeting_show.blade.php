@extends('core::layouts.master')
@section('title') {{ $meeting->title }} @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1>
            <i class="fas fa-calendar-check mr-2"></i>{{ $meeting->title }}
            @php $sc = ['scheduled' => 'primary', 'held' => 'success', 'postponed' => 'warning', 'cancelled' => 'danger']; @endphp
            <span class="badge badge-{{ $sc[$meeting->status] ?? 'secondary' }} ml-2">
                {{ ucfirst($meeting->status) }}
            </span>
        </h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        @if($attended)
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-1"></i>
            Your attendance has been recorded for this meeting.
        </div>
        @endif

        <div class="row">

            <div class="col-md-4">

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Meeting Details</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5">Type</dt>
                            <dd class="col-7">{{ strtoupper(str_replace('_', ' ', $meeting->meeting_type)) }}</dd>

                            <dt class="col-5">Date</dt>
                            <dd class="col-7">
                                {{ \Carbon\Carbon::parse($meeting->scheduled_date)->format('d M Y') }}
                                @if($meeting->scheduled_time)
                                    at {{ $meeting->scheduled_time }}
                                @endif
                            </dd>

                            <dt class="col-5">Venue</dt>
                            <dd class="col-7">{{ $meeting->venue ?? '—' }}</dd>

                            <dt class="col-5">Quorum</dt>
                            <dd class="col-7">{{ $meeting->quorum_required ?? '—' }}</dd>

                            @if($meeting->actual_date)
                            <dt class="col-5">Held On</dt>
                            <dd class="col-7">{{ \Carbon\Carbon::parse($meeting->actual_date)->format('d M Y') }}</dd>
                            @endif

                            @if($meeting->members_present)
                            <dt class="col-5">Present</dt>
                            <dd class="col-7">{{ $meeting->members_present }}</dd>
                            @endif

                            @if($meeting->members_apology)
                            <dt class="col-5">Apologies</dt>
                            <dd class="col-7">{{ $meeting->members_apology }}</dd>
                            @endif
                        </dl>
                    </div>
                </div>

                @if($meeting->notice)
                <div class="card card-outline card-warning">
                    <div class="card-header"><h3 class="card-title">Notice</h3></div>
                    <div class="card-body small">{{ $meeting->notice }}</div>
                </div>
                @endif

                @if($meeting->minutes)
                <div class="card card-outline card-success">
                    <div class="card-header"><h3 class="card-title">Minutes Summary</h3></div>
                    <div class="card-body small">{{ $meeting->minutes }}</div>
                </div>
                @endif

            </div>

            <div class="col-md-8">

                {{-- Agenda --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Agenda
                            <span class="badge badge-secondary ml-1">{{ $meeting->agendaItems->count() }}</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        @forelse($meeting->agendaItems->sortBy('order') as $item)
                            <div class="mb-3">
                                <strong>{{ $item->order }}. {{ $item->title }}</strong>
                                @if($item->presenter)
                                    <small class="text-muted ml-2">— Presenter: {{ $item->presenter }}</small>
                                @endif
                                @if($item->duration_minutes)
                                    <small class="text-muted ml-2">({{ $item->duration_minutes }} min)</small>
                                @endif
                                @if($item->description)
                                    <p class="mb-0 ml-3 text-muted small mt-1">{{ $item->description }}</p>
                                @endif
                                @if($item->outcome)
                                    <p class="mb-0 ml-3 text-success small mt-1">
                                        <i class="fas fa-check mr-1"></i>{{ $item->outcome }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted">No agenda items listed.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Resolutions --}}
                @if($meeting->resolutions->count())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Resolutions
                            <span class="badge badge-secondary ml-1">{{ $meeting->resolutions->count() }}</span>
                        </h3>
                    </div>
                    <div class="card-body">
                        @foreach($meeting->resolutions as $res)
                            <div class="border-left border-{{ $res->result === 'passed' ? 'success' : ($res->result === 'rejected' ? 'danger' : 'warning') }} pl-3 mb-3">
                                <div>
                                    @if($res->resolution_number)
                                        <strong>{{ $res->resolution_number }}</strong>
                                    @endif
                                    <span class="badge badge-{{ $res->result === 'passed' ? 'success' : ($res->result === 'rejected' ? 'danger' : 'warning') }} ml-1">
                                        {{ ucfirst($res->result) }}
                                    </span>
                                </div>
                                <p class="mb-1 mt-1">{{ $res->resolution_text }}</p>
                                @if($res->votes_for || $res->votes_against)
                                <small class="text-muted">
                                    For: {{ $res->votes_for ?? 0 }} &nbsp;|&nbsp;
                                    Against: {{ $res->votes_against ?? 0 }} &nbsp;|&nbsp;
                                    Abstentions: {{ $res->abstentions ?? 0 }}
                                </small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Attendance --}}
                @if($meeting->attendances->count())
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Attendance
                            <span class="badge badge-primary ml-1">
                                {{ $meeting->attendances->where('attended', 1)->count() }} present
                            </span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr><th>Member</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                            @foreach($meeting->attendances as $att)
                                <tr @if($client && $att->client_id === $client->id) style="background:#f0fff0" @endif>
                                    <td>
                                        {{ $att->client->full_name ?? '—' }}
                                        @if($client && $att->client_id === $client->id)
                                            <span class="badge badge-secondary ml-1">You</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $att->attended ? 'success' : 'warning' }}">
                                            {{ $att->attended ? 'Present' : 'Apology' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>
        </div>

        <a href="{{ url('portal/sacco/chama/meetings') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Back to Meetings
        </a>

    </div>
</section>

@endsection

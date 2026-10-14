@extends('core::layouts.master')
@section('title') {{ $meeting->title }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>{{ $meeting->title }}
        <span class="badge badge-{{ ['scheduled'=>'primary','held'=>'success','postponed'=>'warning','cancelled'=>'danger'][$meeting->status]??'secondary' }}">{{ ucfirst($meeting->status) }}</span>
    </h1>
</div></section>
<section class="content">
    <div class="row">
        <div class="col-md-5">
            {{-- Details --}}
            <div class="card"><div class="card-header"><h3 class="card-title">Meeting Details</h3></div><div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Type</dt><dd class="col-7">{{ strtoupper($meeting->meeting_type) }}</dd>
                    <dt class="col-5">Scheduled</dt><dd class="col-7">{{ $meeting->scheduled_date }} {{ $meeting->scheduled_time }}</dd>
                    <dt class="col-5">Venue</dt><dd class="col-7">{{ $meeting->venue ?? '—' }}</dd>
                    <dt class="col-5">Quorum</dt><dd class="col-7">{{ $meeting->quorum_required ?? '—' }}</dd>
                    <dt class="col-5">Members Present</dt><dd class="col-7">{{ $meeting->members_present ?? '—' }}</dd>
                    <dt class="col-5">Apologies</dt><dd class="col-7">{{ $meeting->members_apology ?? '—' }}</dd>
                    @if($meeting->actual_date)<dt class="col-5">Actual Date</dt><dd class="col-7">{{ $meeting->actual_date }}</dd>@endif
                </dl>
            </div></div>

            {{-- Mark Held --}}
            @if($meeting->status === 'scheduled')
            @can('sacco.meetings.create')
            <div class="card card-success"><div class="card-header"><h3 class="card-title">Mark as Held</h3></div><div class="card-body">
                <form method="POST" action="{{ url('sacco/meeting/'.$meeting->id.'/held') }}">@csrf
                    <div class="form-group"><label>Actual Date</label><input type="date" name="actual_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="row">
                        <div class="col-6"><div class="form-group"><label>Members Present</label><input type="number" name="members_present" class="form-control" min="0"></div></div>
                        <div class="col-6"><div class="form-group"><label>Apologies</label><input type="number" name="members_apology" class="form-control" min="0"></div></div>
                    </div>
                    <div class="form-group"><label>Minutes Summary</label><textarea name="minutes" class="form-control" rows="4"></textarea></div>
                    <button class="btn btn-success btn-block">Mark as Held</button>
                </form>
            </div></div>
            @endcan
            @endif

            {{-- Add Agenda Item --}}
            @can('sacco.meetings.create')
            <div class="card"><div class="card-header"><h3 class="card-title">Add Agenda Item</h3></div><div class="card-body">
                <form method="POST" action="{{ url('sacco/meeting/'.$meeting->id.'/agenda') }}">@csrf
                    <div class="form-group"><label>Title <span class="text-danger">*</span></label><input type="text" name="title" class="form-control" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                    <button class="btn btn-info btn-block">Add Item</button>
                </form>
            </div></div>
            @endcan
        </div>

        <div class="col-md-7">
            {{-- Agenda --}}
            <div class="card"><div class="card-header"><h3 class="card-title">Agenda</h3></div><div class="card-body">
                @forelse($meeting->agendaItems as $item)
                    <div class="mb-2">
                        <strong>{{ $item->order }}. {{ $item->title }}</strong>
                        @if($item->presenter)<small class="text-muted"> — {{ $item->presenter }}</small>@endif
                        @if($item->description)<p class="mb-1 ml-3 text-muted small">{{ $item->description }}</p>@endif
                        @if($item->outcome)<p class="ml-3 text-success small"><i class="fas fa-check"></i> {{ $item->outcome }}</p>@endif
                    </div>
                @empty
                    <p class="text-muted">No agenda items.</p>
                @endforelse
            </div></div>

            {{-- Resolutions --}}
            <div class="card"><div class="card-header"><h3 class="card-title">Resolutions</h3></div><div class="card-body">
                @can('sacco.meetings.create')
                <form method="POST" action="{{ url('sacco/meeting/'.$meeting->id.'/resolution') }}" class="mb-3">@csrf
                    <div class="form-group"><label>Resolution Text <span class="text-danger">*</span></label><textarea name="resolution_text" class="form-control" rows="2" required></textarea></div>
                    <div class="row">
                        <div class="col-3"><div class="form-group"><label>Votes For</label><input type="number" name="votes_for" class="form-control" min="0" value="0"></div></div>
                        <div class="col-3"><div class="form-group"><label>Against</label><input type="number" name="votes_against" class="form-control" min="0" value="0"></div></div>
                        <div class="col-3"><div class="form-group"><label>Abstentions</label><input type="number" name="abstentions" class="form-control" min="0" value="0"></div></div>
                        <div class="col-3"><div class="form-group"><label>Result</label>
                            <select name="result" class="form-control"><option value="passed">Passed</option><option value="rejected">Rejected</option><option value="deferred">Deferred</option></select>
                        </div></div>
                    </div>
                    <button class="btn btn-primary btn-sm">Add Resolution</button>
                </form>
                @endcan
                @forelse($meeting->resolutions as $res)
                    <div class="border-left border-{{ $res->result==='passed'?'success':($res->result==='rejected'?'danger':'warning') }} pl-3 mb-2">
                        <strong>{{ $res->resolution_number }}</strong>
                        <span class="badge badge-{{ $res->result==='passed'?'success':($res->result==='rejected'?'danger':'warning') }}">{{ $res->result }}</span>
                        <p class="mb-1">{{ $res->resolution_text }}</p>
                        <small class="text-muted">For: {{ $res->votes_for }} | Against: {{ $res->votes_against }} | Abstentions: {{ $res->abstentions }}</small>
                    </div>
                @empty
                    <p class="text-muted">No resolutions recorded.</p>
                @endforelse
            </div></div>

            {{-- Attendance --}}
            <div class="card"><div class="card-header"><h3 class="card-title">Attendance ({{ $meeting->attendances->count() }})</h3></div><div class="card-body">
                @can('sacco.meetings.attendance')
                <form method="POST" action="{{ url('sacco/meeting/'.$meeting->id.'/attendance') }}" class="mb-3">@csrf
                    <div class="input-group">
                        <select name="attendee_client_ids[]" class="form-control select2" multiple>
                            @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }}</option>@endforeach
                        </select>
                        <div class="input-group-append"><button class="btn btn-info">Record</button></div>
                    </div>
                </form>
                @endcan
                @if($meeting->attendances->count())
                    <p class="text-muted">{{ $meeting->attendances->where('attended',1)->count() }} attended · {{ $meeting->attendances->where('attended',0)->count() }} apology</p>
                @endif
            </div></div>
        </div>
    </div>
    <a href="{{ url('sacco/meeting') }}" class="btn btn-default">← Back</a>
</section>
@endsection

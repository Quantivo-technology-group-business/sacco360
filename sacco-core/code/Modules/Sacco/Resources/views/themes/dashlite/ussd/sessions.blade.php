@extends('core::layouts.master')
@section('title') USSD Session Log @endsection
@section('content')

<div class="nk-block-head nk-block-head-sm mb-4">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">USSD Session Log</h3>
            <div class="nk-block-des text-soft"><p>Full audit trail of USSD sessions.</p></div>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ url('sacco/ussd/settings') }}" class="btn btn-outline-light bg-white mr-1">
                <em class="icon ni ni-setting"></em> Settings
            </a>
            <a href="{{ url('sacco/ussd/members') }}" class="btn btn-outline-light bg-white">
                <em class="icon ni ni-users"></em> Members
            </a>
        </div>
    </div>
</div>

<div class="nk-block mb-3">
    <form method="get">
        <div class="card card-bordered">
            <div class="card-inner py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Search</label>
                        <input type="text" name="s" class="form-control form-control-sm"
                               placeholder="Phone or session ID..." value="{{ request('s') }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label form-label-sm">Date</label>
                        <input type="date" name="date" class="form-control form-control-sm"
                               value="{{ request('date') }}">
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ url('sacco/ussd/sessions') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="nk-block">
    <div class="card card-bordered">
        <div class="card-inner p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Phone</th>
                            <th>Member</th>
                            <th>Session ID</th>
                            <th>Last Menu</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>Started</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sessions as $s)
                            <tr>
                                <td class="text-soft">{{ $s->id }}</td>
                                <td>{{ $s->phone_number }}</td>
                                <td>
                                    @if($s->client)
                                        <a href="{{ url('client/'.$s->client_id.'/show') }}">
                                            {{ $s->client->first_name }} {{ $s->client->last_name }}
                                        </a>
                                    @else
                                        <span class="text-soft">Unknown</span>
                                    @endif
                                </td>
                                <td><code class="text-soft small">{{ Str::limit($s->session_id, 20) }}</code></td>
                                <td>{{ $s->last_menu ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $s->status === 'completed' ? 'success' :
                                        ($s->status === 'error' ? 'danger' :
                                        ($s->status === 'timeout' ? 'warning' : 'info'))
                                    }}">{{ $s->status }}</span>
                                </td>
                                <td class="text-soft small">
                                    @if($s->started_at && $s->ended_at)
                                        {{ $s->started_at->diffInSeconds($s->ended_at) }}s
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-soft small">{{ $s->created_at->format('d M y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-soft py-4">No sessions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($sessions->hasPages())
            <div class="card-inner border-top">{{ $sessions->links() }}</div>
        @endif
    </div>
</div>
@endsection

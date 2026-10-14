@extends('core::layouts.master')
@section('title') USSD Session Log @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>USSD Session Log</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('sacco/ussd/settings') }}">USSD Settings</a></li>
                    <li class="breadcrumb-item active">Sessions</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex" style="gap:.5rem">
                    <input type="text" name="s" class="form-control form-control-sm" style="max-width:200px"
                           placeholder="Phone or session ID..." value="{{ request('s') }}">
                    <input type="date" name="date" class="form-control form-control-sm" style="max-width:160px"
                           value="{{ request('date') }}">
                    <button class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ url('sacco/ussd/sessions') }}" class="btn btn-default btn-sm">Reset</a>
                </form>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead>
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
                                <td class="text-muted">{{ $s->id }}</td>
                                <td>{{ $s->phone_number }}</td>
                                <td>
                                    @if($s->client)
                                        <a href="{{ url('client/'.$s->client_id.'/show') }}">
                                            {{ $s->client->first_name }} {{ $s->client->last_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">Unknown</span>
                                    @endif
                                </td>
                                <td><code class="small">{{ Str::limit($s->session_id, 20) }}</code></td>
                                <td>{{ $s->last_menu ?? '—' }}</td>
                                <td>
                                    <span class="badge badge-{{
                                        $s->status === 'completed' ? 'success' :
                                        ($s->status === 'error' ? 'danger' :
                                        ($s->status === 'timeout' ? 'warning' : 'info'))
                                    }}">{{ $s->status }}</span>
                                </td>
                                <td class="text-muted small">
                                    @if($s->started_at && $s->ended_at)
                                        {{ $s->started_at->diffInSeconds($s->ended_at) }}s
                                    @else —
                                    @endif
                                </td>
                                <td class="text-muted small">{{ $s->created_at->format('d M y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-3">No sessions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($sessions->hasPages())
                <div class="card-footer">{{ $sessions->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection

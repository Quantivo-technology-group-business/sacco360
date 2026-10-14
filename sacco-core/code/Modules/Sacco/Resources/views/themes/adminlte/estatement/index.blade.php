@extends('core::layouts.master')
@section('title') E-Statement Schedules @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>E-Statement Delivery</h1></div></section>
<section class="content">
    <div class="mb-3">
        @can('sacco.estatements.create')
            <a href="{{ url('sacco/estatement/create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Schedule</a>
            <a href="{{ url('sacco/estatement/send-demand') }}" class="btn btn-info">Send On-Demand</a>
        @endcan
        @can('sacco.estatements.run')
            <a href="{{ url('sacco/estatement/run') }}" class="btn btn-warning" onclick="return confirm('Run all due statement schedules now?')">
                <i class="fas fa-play"></i> Run Due Schedules
            </a>
        @endcan
        <a href="{{ url('sacco/estatement/logs') }}" class="btn btn-secondary">Delivery Logs</a>
    </div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Name</th><th>Type</th><th>Frequency</th><th>Channel</th><th>Member</th><th>Next Run</th><th>Active</th></tr></thead>
            <tbody>
            @forelse($schedules as $s)
                <tr>
                    <td>{{ $s->name }}</td>
                    <td>{{ ucfirst($s->statement_type) }}</td>
                    <td>{{ ucfirst($s->frequency) }}</td>
                    <td>{{ ucfirst($s->delivery_channel) }}</td>
                    <td>{{ $s->client ? $s->client->full_name : '<em class="text-muted">All Members</em>' }}</td>
                    <td>{{ $s->next_run_date ?? '—' }}</td>
                    <td><span class="badge badge-{{ $s->is_active?'success':'secondary' }}">{{ $s->is_active?'Yes':'No' }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No statement schedules configured.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $schedules->links() }}
    </div></div>
</section>
@endsection

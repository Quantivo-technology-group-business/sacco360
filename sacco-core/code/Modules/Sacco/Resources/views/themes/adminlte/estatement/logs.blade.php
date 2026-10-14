@extends('core::layouts.master')
@section('title') Statement Delivery Logs @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Statement Delivery Logs</h1></div></section>
<section class="content">
    <a href="{{ url('sacco/estatement') }}" class="btn btn-default mb-3">← Back</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Date</th><th>Member</th><th>Type</th><th>Period</th><th>Channel</th><th>Recipient</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($logs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d H:i') }}</td>
                    <td>{{ $log->client->full_name ?? '—' }}</td>
                    <td>{{ ucfirst($log->statement_type) }}</td>
                    <td>{{ $log->period_from }} to {{ $log->period_to }}</td>
                    <td>{{ ucfirst($log->delivery_channel) }}</td>
                    <td>{{ $log->recipient_email ?? $log->recipient_phone ?? '—' }}</td>
                    <td><span class="badge badge-{{ ['queued'=>'secondary','sent'=>'success','failed'=>'danger','opened'=>'primary'][$log->status]??'secondary' }}">{{ $log->status }}</span>
                    @if($log->status==='failed' && $log->error_message)
                        <i class="fas fa-exclamation-circle text-danger" title="{{ $log->error_message }}" data-toggle="tooltip"></i>
                    @endif</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No delivery logs.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $logs->links() }}
    </div></div>
</section>
@endsection

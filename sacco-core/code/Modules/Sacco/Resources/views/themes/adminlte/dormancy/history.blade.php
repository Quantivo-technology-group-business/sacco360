@extends('core::layouts.master')
@section('title') Dormancy History — {{ $client->full_name }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Dormancy History: {{ $client->full_name }}</h1>
    </div>
</section>
<section class="content">
    <div class="mb-3">
        <a href="{{ url('sacco/dormancy/members') }}" class="btn btn-default">← Back to Dormant Members</a>
        <a href="{{ url('sacco/dormancy') }}" class="btn btn-default">← Dormancy Dashboard</a>
    </div>

    {{-- Member info card --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Member Info</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Name</dt>
                        <dd class="col-7">{{ $client->full_name }}</dd>
                        <dt class="col-5">Member No.</dt>
                        <dd class="col-7">{{ $client->member_number ?? $client->id }}</dd>
                        <dt class="col-5">Status</dt>
                        <dd class="col-7">
                            <span class="badge badge-{{ $client->member_status_detail === 'active' ? 'success' : 'warning' }}">
                                {{ ucfirst($client->member_status_detail ?? 'unknown') }}
                            </span>
                        </dd>
                        <dt class="col-5">Email</dt>
                        <dd class="col-7">{{ $client->email ?? '—' }}</dd>
                        <dt class="col-5">Phone</dt>
                        <dd class="col-7">{{ $client->mobile ?? '—' }}</dd>
                    </dl>
                </div>
                @if($client->member_status_detail === 'dormant')
                <div class="card-footer">
                    @can('sacco.dormancy.manage')
                    <form method="POST" action="{{ url('sacco/dormancy/'.$client->id.'/reactivate') }}">
                        @csrf
                        <div class="form-group mb-2">
                            <input type="text" name="notes" class="form-control form-control-sm"
                                   placeholder="Reason for reactivation (optional)">
                        </div>
                        <button class="btn btn-success btn-block btn-sm"
                                onclick="return confirm('Reactivate this member?')">
                            Reactivate Member
                        </button>
                    </form>
                    @endcan
                </div>
                @endif
            </div>
        </div>

        {{-- History table --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Dormancy Activity Log</h3></div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Rule</th>
                                <th>Fee Charged</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($history as $log)
                            <tr>
                                <td>{{ $log->action_date }}</td>
                                <td>
                                    @php
                                        $badge = [
                                            'flagged_dormant' => 'warning',
                                            'reactivated'     => 'success',
                                            'fee_charged'     => 'info',
                                            'notified'        => 'secondary',
                                        ][$log->action] ?? 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badge }}">
                                        {{ ucwords(str_replace('_', ' ', $log->action)) }}
                                    </span>
                                </td>
                                <td>{{ $log->rule->name ?? ($log->actioned_by_id ? 'Manual' : 'System') }}</td>
                                <td>{{ $log->fee_charged ? number_format($log->fee_charged, 2) : '—' }}</td>
                                <td>{{ $log->notes ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No dormancy history for this member.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

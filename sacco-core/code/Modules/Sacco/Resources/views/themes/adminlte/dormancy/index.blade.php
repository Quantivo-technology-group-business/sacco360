@extends('core::layouts.master')
@section('title') Member Dormancy Management @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Member Dormancy Management</h1></div></section>
<section class="content">
    <div class="row mb-3">
        <div class="col-md-4"><div class="info-box bg-warning"><span class="info-box-icon"><i class="fas fa-user-clock"></i></span>
            <div class="info-box-content"><span class="info-box-text">Dormant Members</span>
            <span class="info-box-number">{{ $dormantCount }}</span></div></div></div>
        @if($rule)
        <div class="col-md-8"><div class="callout callout-info">
            <h5>Active Rule: {{ $rule->name }}</h5>
            <p class="mb-0">Flag members with no transaction for <strong>{{ $rule->inactivity_months }} months</strong>.
            @if($rule->block_withdrawals) Block withdrawals. @endif
            @if($rule->block_new_loans) Block new loans. @endif
            @if($rule->charge_dormancy_fee) Charge <strong>{{ number_format($rule->dormancy_fee_amount,2) }}</strong> {{ $rule->fee_frequency }} fee. @endif
            </p>
        </div></div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card"><div class="card-header"><h3 class="card-title">Dormancy Rule Configuration</h3></div><div class="card-body">
                <form method="POST" action="{{ url('sacco/dormancy/rule/store') }}">@csrf
                    <div class="form-group"><label>Rule Name</label><input type="text" name="name" class="form-control" value="{{ $rule->name ?? 'Standard Dormancy Rule' }}" required></div>
                    <div class="form-group"><label>Inactivity Threshold (Months) <span class="text-danger">*</span></label>
                        <input type="number" name="inactivity_months" class="form-control" min="1" value="{{ $rule->inactivity_months ?? 12 }}" required>
                        <small class="text-muted">Flag member if no transactions for this many months.</small></div>
                    <div class="form-group"><label>Minimum Balance (below = dormant)</label>
                        <input type="number" name="minimum_balance" class="form-control" step="0.01" min="0" value="{{ $rule->minimum_balance ?? 0 }}"></div>
                    <div class="form-check mb-2"><input type="checkbox" name="block_withdrawals" value="1" class="form-check-input" id="bw" {{ ($rule->block_withdrawals??1)?'checked':'' }}>
                        <label class="form-check-label" for="bw">Block withdrawals when dormant</label></div>
                    <div class="form-check mb-2"><input type="checkbox" name="block_new_loans" value="1" class="form-check-input" id="bl" {{ ($rule->block_new_loans??1)?'checked':'' }}>
                        <label class="form-check-label" for="bl">Block new loans when dormant</label></div>
                    <div class="form-check mb-2"><input type="checkbox" name="charge_dormancy_fee" value="1" class="form-check-input" id="cf" {{ ($rule->charge_dormancy_fee??0)?'checked':'' }}>
                        <label class="form-check-label" for="cf">Charge dormancy fee</label></div>
                    <div class="row">
                        <div class="col-6"><div class="form-group"><label>Fee Amount</label><input type="number" name="dormancy_fee_amount" class="form-control" step="0.01" value="{{ $rule->dormancy_fee_amount ?? 0 }}"></div></div>
                        <div class="col-6"><div class="form-group"><label>Frequency</label>
                            <select name="fee_frequency" class="form-control"><option value="monthly" {{ ($rule->fee_frequency??'annually')==='monthly'?'selected':'' }}>Monthly</option>
                            <option value="quarterly" {{ ($rule->fee_frequency??'')==='quarterly'?'selected':'' }}>Quarterly</option>
                            <option value="annually" {{ ($rule->fee_frequency??'annually')==='annually'?'selected':'' }}>Annually</option></select></div></div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Rule</button>
                </form>
            </div></div>
        </div>
        <div class="col-md-8">
            <div class="mb-3">
                @can('sacco.dormancy.manage')
                <a href="{{ url('sacco/dormancy/run-check') }}" class="btn btn-warning" onclick="return confirm('Run dormancy check against all active members?')">
                    <i class="fas fa-play"></i> Run Dormancy Check Now
                </a>
                <a href="{{ url('sacco/dormancy/members') }}" class="btn btn-secondary">View All Dormant Members</a>
                @endcan
            </div>
            <div class="card"><div class="card-header"><h3 class="card-title">Recent Dormancy Activity</h3></div><div class="card-body">
                <table id="dormancy-logs" class="table table-sm table-bordered table-hover">
                    <thead><tr><th>Date</th><th>Member</th><th>Action</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse($recentLogs as $log)
                        <tr>
                            <td>{{ $log->action_date }}</td>
                            <td>{{ $log->client->full_name ?? '—' }}</td>
                            <td><span class="badge badge-{{ $log->action==='reactivated'?'success':($log->action==='flagged_dormant'?'warning':'info') }}">{{ str_replace('_',' ',$log->action) }}</span></td>
                            <td>{{ $log->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No dormancy activity recorded.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#dormancy-logs').DataTable({
            pageLength: 10,
            order: [[0, 'desc']],
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

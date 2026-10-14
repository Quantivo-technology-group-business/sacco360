@extends('core::layouts.master')
@section('title') {{ $group->name }} — Contributions @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>{{ $group->name }} — Contributions</h1>
    </div>
</section>
<section class="content">
    <div class="mb-3">
        <a href="{{ url('sacco/group/'.$group->id.'/show') }}" class="btn btn-default">← Back to Group</a>
    </div>

    {{-- Summary cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="info-box bg-primary">
                <span class="info-box-icon"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Contributions</span>
                    <span class="info-box-number">{{ number_format($contributions->total()) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Amount</span>
                    <span class="info-box-number">{{ number_format($contributions->sum('amount'), 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Monthly Contribution</span>
                    <span class="info-box-number">{{ number_format($group->monthly_contribution, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Contribution Records</h3>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Member</th>
                        <th>Period</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($contributions as $c)
                    <tr>
                        <td>{{ $c->contribution_date }}</td>
                        <td>{{ $c->client->full_name ?? '—' }}</td>
                        <td>{{ $c->period ?? '—' }}</td>
                        <td>{{ number_format($c->amount, 2) }}</td>
                        <td>
                            <span class="badge badge-{{ $c->status === 'posted' ? 'success' : ($c->status === 'reversed' ? 'danger' : 'secondary') }}">
                                {{ ucfirst($c->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No contributions posted for this group yet.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            {{ $contributions->links() }}
        </div>
    </div>
</section>
@endsection

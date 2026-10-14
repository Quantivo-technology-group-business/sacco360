@extends('core::layouts.master')
@section('title') Investment: {{ $investment->name }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ $investment->name }}
    <span class="badge badge-{{ $investment->status==='active'?'success':($investment->status==='matured'?'info':'secondary') }}">{{ $investment->status }}</span>
</h1></div></section>
<section class="content">
    <div class="row">
        <div class="col-md-5">
            <div class="card"><div class="card-header"><h3 class="card-title">Details</h3></div><div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-5">Type</dt><dd class="col-7">{{ ucfirst(str_replace('_',' ',$investment->type)) }}</dd>
                    <dt class="col-5">Issuer</dt><dd class="col-7">{{ $investment->issuer ?? '—' }}</dd>
                    <dt class="col-5">Reference</dt><dd class="col-7">{{ $investment->reference_number ?? '—' }}</dd>
                    <dt class="col-5">Principal</dt><dd class="col-7">{{ number_format($investment->principal_amount,2) }}</dd>
                    <dt class="col-5">Current Value</dt><dd class="col-7">{{ number_format($investment->current_value ?? $investment->principal_amount,2) }}</dd>
                    <dt class="col-5">Rate</dt><dd class="col-7">{{ $investment->interest_rate ? $investment->interest_rate.'%' : '—' }}</dd>
                    <dt class="col-5">Start</dt><dd class="col-7">{{ $investment->investment_date }}</dd>
                    <dt class="col-5">Maturity</dt><dd class="col-7">{{ $investment->maturity_date ?? '—' }}</dd>
                    <dt class="col-5">Expected Return</dt><dd class="col-7">{{ $investment->expected_return ? number_format($investment->expected_return,2) : '—' }}</dd>
                    <dt class="col-5">Actual Return</dt><dd class="col-7">{{ $investment->actual_return ? number_format($investment->actual_return,2) : '—' }}</dd>
                </dl>
            </div></div>

            @if($investment->status === 'active')
            @can('sacco.investments.create')
            <div class="card"><div class="card-header"><h3 class="card-title">Add Valuation</h3></div><div class="card-body">
                <form method="POST" action="{{ url('sacco/investment/'.$investment->id.'/valuation') }}">@csrf
                    <div class="form-group"><label>Date</label><input type="date" name="valuation_date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                    <div class="form-group"><label>Market Value</label><input type="number" name="market_value" class="form-control" step="0.01" value="{{ $investment->current_value ?? $investment->principal_amount }}" required></div>
                    <div class="form-group"><label>Accrued Interest</label><input type="number" name="accrued_interest" class="form-control" step="0.01" value="0"></div>
                    <div class="form-group"><label>Notes</label><input type="text" name="notes" class="form-control"></div>
                    <button class="btn btn-info btn-block">Record Valuation</button>
                </form>
            </div></div>
            <div class="card"><div class="card-header"><h3 class="card-title">Mark Matured</h3></div><div class="card-body">
                <form method="POST" action="{{ url('sacco/investment/'.$investment->id.'/mature') }}">@csrf
                    <div class="form-group"><label>Actual Return</label><input type="number" name="actual_return" class="form-control" step="0.01" min="0" required></div>
                    <button class="btn btn-success btn-block">Mark as Matured</button>
                </form>
            </div></div>
            @endcan
            @endif
        </div>
        <div class="col-md-7">
            <div class="card"><div class="card-header"><h3 class="card-title">Valuation History</h3></div><div class="card-body">
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Date</th><th>Market Value</th><th>Accrued Interest</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse($investment->valuations->sortByDesc('valuation_date') as $v)
                        <tr>
                            <td>{{ $v->valuation_date }}</td>
                            <td>{{ number_format($v->market_value,2) }}</td>
                            <td>{{ number_format($v->accrued_interest,2) }}</td>
                            <td>{{ $v->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted">No valuations yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
    <a href="{{ url('sacco/investment') }}" class="btn btn-default">← Back</a>
</section>
@endsection

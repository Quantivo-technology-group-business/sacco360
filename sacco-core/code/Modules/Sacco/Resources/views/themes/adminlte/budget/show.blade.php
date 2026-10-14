@extends('core::layouts.master')
@section('title') Budget: {{ $period->name }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>{{ $period->name }} ({{ $period->fiscal_year }})
        <span class="badge badge-{{ ['draft'=>'secondary','approved'=>'info','active'=>'success','closed'=>'dark'][$period->status]??'secondary' }}">{{ ucfirst($period->status) }}</span>
    </h1>
</div></section>
<section class="content">
    <div class="mb-3">
        <a href="{{ url('sacco/budget/'.$period->id.'/vs-actual') }}" class="btn btn-secondary">Budget vs Actual Report</a>
        <a href="{{ url('sacco/budget') }}" class="btn btn-default">← Back</a>
    </div>

    @if($period->status !== 'closed')
    @can('sacco.budget.create')
    <div class="card"><div class="card-header"><h3 class="card-title">Add / Update Budget Line</h3></div><div class="card-body">
        <form method="POST" action="{{ url('sacco/budget/'.$period->id.'/line/store') }}">@csrf
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>GL Account <span class="text-danger">*</span></label>
                    <select name="chart_of_account_id" class="form-control select2" required>
                        <option value="">— Select Account —</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->account_code }} — {{ $a->name }}</option>
                        @endforeach
                    </select></div></div>
                <div class="col-md-2"><div class="form-group"><label>Annual Budget <span class="text-danger">*</span></label>
                    <input type="number" name="budget_amount" class="form-control" step="0.01" min="0" required></div></div>
                <div class="col-md-1"><div class="form-group"><label>Q1</label><input type="number" name="q1_amount" class="form-control" step="0.01" min="0" value="0"></div></div>
                <div class="col-md-1"><div class="form-group"><label>Q2</label><input type="number" name="q2_amount" class="form-control" step="0.01" min="0" value="0"></div></div>
                <div class="col-md-1"><div class="form-group"><label>Q3</label><input type="number" name="q3_amount" class="form-control" step="0.01" min="0" value="0"></div></div>
                <div class="col-md-1"><div class="form-group"><label>Q4</label><input type="number" name="q4_amount" class="form-control" step="0.01" min="0" value="0"></div></div>
                <div class="col-md-2"><div class="form-group"><label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">Save Line</button></div></div>
            </div>
        </form>
    </div></div>
    @endcan
    @endif

    <div class="card"><div class="card-header"><h3 class="card-title">Budget Lines</h3>
        <div class="card-tools"><strong>Total: {{ number_format($period->lines->sum('budget_amount'),2) }}</strong></div>
    </div><div class="card-body">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Account</th><th>Annual Budget</th><th>Q1</th><th>Q2</th><th>Q3</th><th>Q4</th><th>Notes</th></tr></thead>
            <tbody>
            @forelse($period->lines as $line)
                <tr>
                    <td>{{ $line->account->account_code ?? '' }} — {{ $line->account->name ?? '—' }}</td>
                    <td>{{ number_format($line->budget_amount,2) }}</td>
                    <td>{{ number_format($line->q1_amount,2) }}</td>
                    <td>{{ number_format($line->q2_amount,2) }}</td>
                    <td>{{ number_format($line->q3_amount,2) }}</td>
                    <td>{{ number_format($line->q4_amount,2) }}</td>
                    <td>{{ $line->notes ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No budget lines yet. Add one above.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

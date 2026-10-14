@extends('core::layouts.master')
@section('title') Budget vs Actual — {{ $period->name }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>Budget vs Actual — {{ $period->name }} ({{ $period->start_date }} to {{ $period->end_date }})</h1>
</div></section>
<section class="content">
    <a href="{{ url('sacco/budget/'.$period->id.'/show') }}" class="btn btn-default mb-3">← Back to Budget</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <thead class="thead-dark">
                <tr><th>Account</th><th>Budget</th><th>Actual</th><th>Variance</th><th>% Used</th></tr>
            </thead>
            <tbody>
            @php $totalBudget=0; $totalActual=0; @endphp
            @forelse($results as $row)
                @php
                    $totalBudget += $row['budget'];
                    $totalActual += $row['actual'];
                    $varClass = $row['variance'] > 0 ? 'text-danger' : ($row['variance'] < 0 ? 'text-success' : '');
                    $pctBadge = $row['variance_pct'] > 100 ? 'danger' : ($row['variance_pct'] > 80 ? 'warning' : 'success');
                @endphp
                <tr>
                    <td>{{ $row['account']->account_code ?? '' }} {{ $row['account']->name ?? '—' }}</td>
                    <td>{{ number_format($row['budget'],2) }}</td>
                    <td>{{ number_format($row['actual'],2) }}</td>
                    <td class="{{ $varClass }}">{{ number_format($row['variance'],2) }}</td>
                    <td>@if($row['variance_pct']!==null)<span class="badge badge-{{ $pctBadge }}">{{ $row['variance_pct'] }}%</span>@else—@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No budget lines defined.</td></tr>
            @endforelse
            </tbody>
            @if(count($results))
            <tfoot class="font-weight-bold">
                <tr>
                    <td>TOTAL</td>
                    <td>{{ number_format($totalBudget,2) }}</td>
                    <td>{{ number_format($totalActual,2) }}</td>
                    <td class="{{ $totalActual>$totalBudget?'text-danger':'text-success' }}">{{ number_format($totalActual-$totalBudget,2) }}</td>
                    <td>@if($totalBudget>0){{ round($totalActual/$totalBudget*100,1) }}%@endif</td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div></div>
</section>
@endsection

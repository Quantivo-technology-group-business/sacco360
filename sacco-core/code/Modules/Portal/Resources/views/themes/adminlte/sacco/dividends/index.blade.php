@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.dividend_history') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <table class="table table-bordered"><thead><tr><th>Period</th><th>Shares</th><th>Amount</th><th>Status</th></tr></thead>
    <tbody>@forelse($allocations as $a)<tr><td>{{ optional($a->dividend)->name ?? $a->sacco_dividend_id }}</td><td>{{ $a->shares_held }}</td><td>{{ number_format($a->amount, 2) }}</td><td>{{ $a->status }}</td></tr>@empty<tr><td colspan="4" class="text-center">{{ trans('sacco::general.no_dividends') }}</td></tr>@endforelse</tbody></table>
    {{ $allocations->links() }}
</div></div></section>
@endsection

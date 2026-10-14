@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.checkoff_deductions') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <table class="table table-bordered"><thead><tr><th>Batch</th><th>Employee</th><th>Amount</th><th>Loan</th><th>Status</th></tr></thead>
    <tbody>@forelse($items as $i)<tr><td>{{ optional($i->batch)->reference ?? $i->sacco_checkoff_batch_id }}</td><td>{{ $i->employee_name }}</td><td>{{ number_format($i->amount, 2) }}</td><td>{{ $i->loan_id }}</td><td>{{ $i->status }}</td></tr>@empty<tr><td colspan="5" class="text-center">{{ trans('sacco::general.no_checkoff_items') }}</td></tr>@endforelse</tbody></table>
    {{ $items->links() }}
</div></div></section>
@endsection

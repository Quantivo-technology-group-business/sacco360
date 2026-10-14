@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.atm_history') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <table class="table table-bordered"><thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Terminal</th><th>Status</th><th>Message</th></tr></thead>
    <tbody>@forelse($transactions as $t)<tr><td>{{ $t->created_at }}</td><td>{{ $t->transaction_type }}</td><td>{{ number_format($t->amount, 2) }}</td><td>{{ $t->terminal_id }}</td><td>{{ $t->status }}</td><td>{{ $t->response_message }}</td></tr>@empty<tr><td colspan="6" class="text-center">{{ trans('sacco::general.no_atm_transactions') }}</td></tr>@endforelse</tbody></table>
    {{ $transactions->links() }}
</div></div></section>
@endsection

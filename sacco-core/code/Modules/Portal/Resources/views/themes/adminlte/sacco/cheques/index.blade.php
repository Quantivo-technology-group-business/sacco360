@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.cheque_deposits') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.cheque_deposits') }}</h1>
<a href="{{ url('portal/sacco/cheques/create') }}" class="btn btn-primary btn-sm float-right">{{ trans('sacco::general.deposit_cheque') }}</a></div></section>
<section class="content"><div class="card"><div class="card-body">
    <table class="table table-bordered"><thead><tr><th>Cheque #</th><th>Bank</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>@forelse($cheques as $c)<tr><td>{{ $c->cheque_number }}</td><td>{{ $c->bank_name }}</td><td>{{ number_format($c->amount, 2) }}</td><td>{{ $c->cheque_date }}</td><td>{{ $c->status }}</td></tr>@empty<tr><td colspan="5" class="text-center">{{ trans('sacco::general.no_cheques') }}</td></tr>@endforelse</tbody></table>
    {{ $cheques->links() }}
</div></div></section>
@endsection

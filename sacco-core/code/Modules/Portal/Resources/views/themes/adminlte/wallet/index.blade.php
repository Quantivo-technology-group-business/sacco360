@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.wallet') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($wallet)
        <h4>Balance: {{ number_format($wallet->balance, 2) }}</h4>
        <a href="{{ url('portal/wallet/deposit/create') }}" class="btn btn-success btn-sm">Deposit</a>
        <a href="{{ url('portal/wallet/withdraw/create') }}" class="btn btn-warning btn-sm">Withdraw</a>
        <table class="table table-bordered mt-3"><thead><tr><th>Date</th><th>Type</th><th>Amount</th></tr></thead>
        <tbody>@foreach($transactions as $t)<tr><td>{{ $t->submitted_on }}</td><td>{{ $t->transaction_type }}</td><td>{{ number_format($t->amount, 2) }}</td></tr>@endforeach</tbody></table>
    @else
        <div class="alert alert-info">No active wallet linked to your profile.</div>
    @endif
</div></div></section>
@endsection

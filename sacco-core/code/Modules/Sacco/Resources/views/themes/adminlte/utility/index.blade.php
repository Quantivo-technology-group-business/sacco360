@extends('core::layouts.master')
@section('title') Utility Payments @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Utility Payments</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <table class="table table-bordered"><thead><tr><th>Date</th><th>Client</th><th>Provider</th><th>Account</th><th>Amount</th><th>Channel</th><th>Ref</th></tr></thead>
    <tbody>@foreach($payments as $p)<tr><td>{{ $p->created_at }}</td><td>{{ $p->client_id }}</td><td>{{ optional($p->provider)->name }}</td><td>{{ $p->account_number }}</td><td>{{ number_format($p->amount,2) }}</td><td>{{ $p->channel }}</td><td>{{ $p->reference }}</td></tr>@endforeach</tbody></table>
    {{ $payments->links() }}
</div></div></section>
@endsection

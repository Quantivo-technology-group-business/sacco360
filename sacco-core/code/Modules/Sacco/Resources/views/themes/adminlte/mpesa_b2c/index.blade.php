@extends('core::layouts.master')
@section('title') M-Pesa B2C Transactions @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>M-Pesa B2C Outbound</h1></div></section>
<section class="content">
    @if($simulation)<div class="alert alert-warning">{{ trans('sacco::general.mpesa_b2c_simulation_notice') }}</div>@endif
    <div class="alert alert-info">
        <strong>Result URL:</strong> POST {{ url('api/v1/sacco/mpesa/b2c/result') }}<br>
        <strong>Timeout URL:</strong> POST {{ url('api/v1/sacco/mpesa/b2c/timeout') }}
    </div>
    <form method="get" class="mb-3">
        <select name="status" class="form-control d-inline-block w-auto">
            <option value="">All statuses</option>
            @foreach(['pending','submitted','completed','failed','timeout'] as $s)
                <option value="{{ $s }}" @if($status===$s) selected @endif>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-default">Filter</button>
    </form>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Ref</th><th>Member</th><th>Type</th><th>Source</th><th>Phone</th><th>Amount</th><th>Status</th><th>Receipt</th><th>Time</th></tr></thead>
        <tbody>@foreach($transactions as $t)
        <tr>
            <td>{{ $t->reference }}</td>
            <td>{{ $t->client_id }}</td>
            <td>{{ $t->transaction_type }}</td>
            <td>{{ $t->source_type }}</td>
            <td>{{ $t->recipient_phone }}</td>
            <td>{{ number_format($t->amount, 2) }}</td>
            <td>{{ $t->status }}</td>
            <td>{{ $t->mpesa_receipt }}</td>
            <td>{{ $t->created_at }}</td>
        </tr>
        @endforeach</tbody>
    </table>{{ $transactions->links() }}</div></div>
</section>
@endsection

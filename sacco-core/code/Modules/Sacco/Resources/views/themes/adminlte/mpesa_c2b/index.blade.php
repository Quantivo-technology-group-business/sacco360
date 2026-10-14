@extends('core::layouts.master')
@section('title') M-Pesa C2B Transactions @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>M-Pesa PayBill to FOSA</h1></div></section>
<section class="content">
    <div class="alert alert-info">
        <strong>Validation URL:</strong> POST {{url('api/v1/sacco/mpesa/c2b/validate')}}<br>
        <strong>Confirmation URL:</strong> POST {{url('api/v1/sacco/mpesa/c2b/confirm')}}<br>
        <strong>Bill Ref:</strong> Member number, external ID, or account number
    </div>
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>Trans ID</th><th>Amount</th><th>Bill Ref</th><th>MSISDN</th><th>Client</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>@foreach($transactions as $t)
        <tr><td>{{$t->trans_id}}</td><td>{{number_format($t->trans_amount,2)}}</td><td>{{$t->bill_ref_number}}</td>
        <td>{{$t->msisdn}}</td><td>{{$t->client_id}}</td><td>{{$t->status}}</td><td>{{$t->created_at}}</td></tr>
        @endforeach</tbody>
    </table>{{$transactions->links()}}</div></div>
</section>
@endsection

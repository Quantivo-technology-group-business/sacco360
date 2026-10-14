@extends('core::layouts.master')
@section('title') Pesalink @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Pesalink Transfers</h1>
<a href="{{url('sacco/pesalink/create')}}" class="btn btn-primary btn-sm">New Transfer</a></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{session('error')}}</div>@endif
    <div class="card"><div class="card-body"><table class="table table-sm">
    <thead><tr><th>Ref</th><th>Direction</th><th>Amount</th><th>Beneficiary</th><th>Bank</th><th>Status</th><th>IPS Ref</th></tr></thead>
    <tbody>@foreach($transactions as $t)<tr><td>{{$t->reference}}</td><td>{{$t->direction}}</td><td>{{number_format($t->amount,2)}}</td>
    <td>{{$t->beneficiary_name}}</td><td>{{$t->beneficiary_bank}}</td><td>{{$t->status}}</td><td>{{$t->ips_reference}}</td></tr>@endforeach</tbody>
    </table>{{$transactions->links()}}</div></div>
</section>
@endsection

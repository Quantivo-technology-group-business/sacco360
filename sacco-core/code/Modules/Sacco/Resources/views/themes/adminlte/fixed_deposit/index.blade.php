@extends('core::layouts.master')
@section('title') Fixed Deposits @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Fixed Deposits</h1>
<a href="{{url('sacco/fixed_deposit/create')}}" class="btn btn-primary btn-sm">New FD</a></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>ID</th><th>Client</th><th>Principal</th><th>Rate</th><th>Tenure</th><th>Maturity</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($deposits as $d)
        <tr><td>{{$d->id}}</td><td>{{$d->client_id}}</td><td>{{number_format($d->principal,2)}}</td>
        <td>{{$d->interest_rate}}%</td><td>{{$d->tenure_months}} mo</td><td>{{$d->maturity_date}}</td>
        <td>{{$d->status}}</td><td><a href="{{url('sacco/fixed_deposit/'.$d->id.'/show')}}">View</a></td></tr>
        @endforeach</tbody>
    </table>{{$deposits->links()}}</div></div>
</section>
@endsection

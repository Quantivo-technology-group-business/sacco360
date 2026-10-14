@extends('core::layouts.master')
@section('title') Fit & Proper Records @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>Fit & Proper Records</h1></div></section>
<section class="content">
    <div class="card mb-3"><div class="card-body">
        <form method="post" action="{{url('sacco/governance/fit-proper/store')}}">@csrf
            <div class="row"><div class="col-md-3"><input name="person_name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-2"><select name="role" class="form-control"><option value="director">Director</option><option value="ceo">CEO</option><option value="cfo">CFO</option><option value="manager">Manager</option></select></div>
            <div class="col-md-2"><input type="date" name="appointment_date" class="form-control"></div>
            <div class="col-md-2"><select name="status" class="form-control"><option value="pending">Pending</option><option value="approved">Approved</option></select></div>
            <div class="col-md-2"><button class="btn btn-primary">Save</button></div></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Name</th><th>Role</th><th>Appointed</th><th>Status</th></tr></thead>
    <tbody>@foreach($records as $r)<tr><td>{{$r->person_name}}</td><td>{{$r->role}}</td><td>{{$r->appointment_date}}</td><td>{{$r->status}}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

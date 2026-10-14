@extends('core::layouts.master')
@section('title') License Renewals @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>SASRA License Renewals</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card mb-3"><div class="card-header">Add License Record</div><div class="card-body">
        <form method="post" action="{{url('sacco/governance/licenses/store')}}">@csrf
            <div class="row">
                <div class="col-md-3"><input name="license_number" class="form-control" placeholder="License Number"></div>
                <div class="col-md-2"><input type="date" name="issue_date" class="form-control"></div>
                <div class="col-md-2"><input type="date" name="expiry_date" class="form-control" required></div>
                <div class="col-md-2"><select name="status" class="form-control"><option value="active">Active</option><option value="pending_renewal">Pending Renewal</option></select></div>
                <div class="col-md-2"><button class="btn btn-primary">Save</button></div>
            </div>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>License #</th><th>Issue</th><th>Expiry</th><th>Status</th><th>Days to Expiry</th></tr></thead>
    <tbody>@foreach($licenses as $l)<tr><td>{{$l->license_number}}</td><td>{{$l->issue_date}}</td><td>{{$l->expiry_date}}</td>
    <td><span class="badge badge-{{$l->status==='active'?'success':'warning'}}">{{$l->status}}</span></td>
    <td>{{max(0,(strtotime($l->expiry_date)-time())/86400)}} days</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

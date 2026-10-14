@extends('core::layouts.master')
@section('title') Board Resolutions @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>Board Resolutions</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card mb-3"><div class="card-header">Record Resolution</div><div class="card-body">
        <form method="post" action="{{url('sacco/governance/resolutions/store')}}">@csrf
            <div class="row mb-2">
                <div class="col-md-2"><input name="resolution_number" class="form-control" placeholder="Resolution #" required></div>
                <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
                <div class="col-md-2"><select name="meeting_type" class="form-control"><option value="board">Board</option><option value="agm">AGM</option><option value="special">Special</option></select></div>
                <div class="col-md-2"><input type="date" name="meeting_date" class="form-control" required></div>
                <div class="col-md-2"><select name="status" class="form-control"><option value="draft">Draft</option><option value="approved">Approved</option></select></div>
            </div>
            <textarea name="resolution_text" class="form-control mb-2" rows="3" placeholder="Resolution text" required></textarea>
            <button class="btn btn-primary">Save Resolution</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>#</th><th>Title</th><th>Type</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>@foreach($resolutions as $r)<tr><td>{{$r->resolution_number}}</td><td>{{$r->title}}</td><td>{{$r->meeting_type}}</td><td>{{$r->meeting_date}}</td><td>{{$r->status}}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

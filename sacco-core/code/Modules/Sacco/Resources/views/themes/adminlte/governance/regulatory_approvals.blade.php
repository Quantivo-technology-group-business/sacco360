@extends('core::layouts.master')
@section('title') Regulatory Approvals @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>Regulatory Approvals</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card mb-3"><div class="card-header">Request Approval</div><div class="card-body">
        <form method="post" action="{{url('sacco/governance/regulatory-approvals/store')}}">@csrf
            <div class="row mb-2">
                <div class="col-md-2"><select name="approval_type" class="form-control" required>
                    <option value="branch_open">Branch Open</option><option value="branch_close">Branch Close</option>
                    <option value="agency_open">Agency Open</option><option value="relocation">Relocation</option>
                    <option value="business_closure">Business Closure</option><option value="land_purchase">Land Purchase</option>
                    <option value="insider_lending">Insider Lending</option>
                </select></div>
                <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
                <div class="col-md-2"><input type="date" name="requested_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
                <div class="col-md-3"><select name="board_resolution_id" class="form-control"><option value="">Board Resolution (optional)</option>
                    @foreach($resolutions as $r)<option value="{{$r->id}}">{{$r->resolution_number}} - {{$r->title}}</option>@endforeach</select></div>
                <div class="col-md-2"><button class="btn btn-primary">Submit</button></div>
            </div>
            <textarea name="description" class="form-control" rows="2" placeholder="Description"></textarea>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Type</th><th>Title</th><th>Requested</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($approvals as $a)<tr><td>{{$a->approval_type}}</td><td>{{$a->title}}</td><td>{{$a->requested_date}}</td><td>{{$a->status}}</td>
    <td>@if($a->status==='pending')<a href="{{url('sacco/governance/regulatory-approvals/'.$a->id.'/approve')}}" class="btn btn-sm btn-success">Approve</a>@endif</td></tr>@endforeach</tbody></table>
    {{$approvals->links()}}</div></div>
</section>
@endsection

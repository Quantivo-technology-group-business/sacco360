@extends('core::layouts.master')
@section('title') Inspection Action Points @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>SASRA Inspection Action Points</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card mb-3"><div class="card-header">Add Action Point</div><div class="card-body">
        <form method="post" action="{{url('sacco/governance/inspections/store')}}">@csrf
            <div class="row mb-2">
                <div class="col-md-2"><input name="inspection_reference" class="form-control" placeholder="Reference"></div>
                <div class="col-md-2"><input type="date" name="inspection_date" class="form-control"></div>
                <div class="col-md-2"><input type="date" name="deadline" class="form-control" required></div>
                <div class="col-md-2"><select name="status" class="form-control"><option value="open">Open</option><option value="in_progress">In Progress</option></select></div>
            </div>
            <textarea name="action_point" class="form-control mb-2" rows="2" placeholder="Action point description" required></textarea>
            <button class="btn btn-primary">Save</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Reference</th><th>Action</th><th>Deadline</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($actions as $a)<tr class="{{$a->status==='overdue'?'table-danger':''}}"><td>{{$a->inspection_reference}}</td><td>{{strlen($a->action_point)>60?substr($a->action_point,0,60).'...':$a->action_point}}</td><td>{{$a->deadline}}</td><td>{{$a->status}}</td>
    <td>@if($a->status!=='completed')<a href="{{url('sacco/governance/inspections/'.$a->id.'/complete')}}" class="btn btn-sm btn-success">Complete</a>@endif</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

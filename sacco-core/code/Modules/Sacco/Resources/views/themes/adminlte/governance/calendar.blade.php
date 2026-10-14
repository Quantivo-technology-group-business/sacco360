@extends('core::layouts.master')
@section('title') Compliance Calendar @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>SASRA Compliance Calendar</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Form</th><th>Name</th><th>Frequency</th><th>Due Date</th><th>Status</th><th></th></tr></thead>
    <tbody>@foreach($entries as $e)<tr class="{{$e->status==='overdue'?'table-danger':($e->status==='due'?'table-warning':'')}}">
    <td>{{$e->form_code}}</td><td>{{$e->form_name}}</td><td>{{$e->frequency}}</td><td>{{$e->due_date}}</td><td>{{$e->status}}</td>
    <td>@if($e->status!=='submitted')<form method="post" action="{{url('sacco/governance/calendar/'.$e->id.'/submit')}}" style="display:inline">@csrf<button class="btn btn-sm btn-primary">Mark Submitted</button></form>@endif</td>
    </tr>@endforeach</tbody></table></div></div>
</section>
@endsection

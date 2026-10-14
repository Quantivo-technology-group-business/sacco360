@extends('core::layouts.master')
@section('title') Auditor Appointments @endsection
@section('content')
@include('sacco::governance._nav')
<section class="content-header"><div class="container-fluid"><h1>External Auditor Appointments</h1></div></section>
<section class="content">
    <div class="card mb-3"><div class="card-body">
        <form method="post" action="{{url('sacco/governance/auditors/store')}}">@csrf
            <div class="row"><div class="col-md-4"><input name="auditor_name" class="form-control" placeholder="Auditor Name" required></div>
            <div class="col-md-2"><input type="date" name="agm_date" class="form-control" required></div>
            <div class="col-md-2"><input type="date" name="appointment_date" class="form-control" required></div>
            <div class="col-md-2"><button class="btn btn-primary">Save</button></div></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Auditor</th><th>AGM Date</th><th>SASRA Deadline</th><th>Status</th></tr></thead>
    <tbody>@foreach($auditors as $a)<tr class="{{$a->status==='overdue'?'table-danger':''}}"><td>{{$a->auditor_name}}</td><td>{{$a->agm_date}}</td><td>{{$a->sasra_submission_deadline}}</td><td>{{$a->status}}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') Report Templates @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>SASRA Report Templates</h1></div></section>
<section class="content">
    <div class="card mb-3"><div class="card-body">
        <form method="post" action="{{url('sacco/regulatory/templates/store')}}">@csrf
            <div class="row"><div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
            <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
            <div class="col-md-2"><input name="form_type" class="form-control" placeholder="Type"></div>
            <div class="col-md-2"><select name="frequency" class="form-control"><option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="annual">Annual</option><option value="adhoc">Adhoc</option></select></div>
            <div class="col-md-2"><button class="btn btn-primary">Add</button></div></div>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Code</th><th>Name</th><th>Type</th><th>Frequency</th><th>Active</th></tr></thead>
    <tbody>@foreach($templates as $t)<tr><td>{{$t->code}}</td><td>{{$t->name}}</td><td>{{$t->form_type}}</td><td>{{$t->frequency}}</td><td>{{$t->active?'Yes':'No'}}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

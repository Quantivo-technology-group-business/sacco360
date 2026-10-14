@extends('core::layouts.master')
@section('title') {{ trans_choice('core::general.add',1) }} {{ trans_choice('payroll::general.run',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('core::general.add',1) }} {{ trans_choice('payroll::general.run',1) }}</h1></div></section>
<section class="content">
<form method="post" action="{{ url('payroll/run/store') }}">@csrf
<div class="card"><div class="card-body">
    <div class="row">
        <div class="col-md-6"><div class="form-group"><label>{{ trans_choice('core::general.name',1) }}</label><input name="name" class="form-control" placeholder="June 2024 Payroll" required></div></div>
        <div class="col-md-6"><div class="form-group"><label>{{ trans_choice('payroll::general.period',1) }}</label><input name="period" class="form-control" placeholder="2024-06" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>Period Start</label><input type="date" name="period_start" class="form-control" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>Period End</label><input type="date" name="period_end" class="form-control" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('core::general.currency',1) }}</label>
            <select name="currency_id" class="form-control" required>@foreach($currencies as $c)<option value="{{$c->id}}">{{$c->name}}</option>@endforeach</select></div></div>
        <div class="col-md-6"><div class="form-group"><label>{{ trans_choice('branch::general.branch',1) }}</label>
            <select name="branch_id" class="form-control"><option value="">All Branches</option>@foreach($branches as $b)<option value="{{$b->id}}">{{$b->name}}</option>@endforeach</select></div></div>
        <div class="col-md-12"><div class="form-group"><label>{{ trans_choice('core::general.description',1) }}</label><textarea name="description" class="form-control"></textarea></div></div>
    </div>
</div><div class="card-footer"><button class="btn btn-primary">{{ trans_choice('core::general.save',1) }}</button></div></div>
</form>
</section>
@endsection

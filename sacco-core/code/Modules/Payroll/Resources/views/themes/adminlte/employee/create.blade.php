@extends('core::layouts.master')
@section('title') {{ trans_choice('core::general.add',1) }} {{ trans_choice('payroll::general.employee',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('core::general.add',1) }} {{ trans_choice('payroll::general.employee',1) }}</h1></div></section>
<section class="content">
<form method="post" action="{{ url('payroll/employee/store') }}">@csrf
<div class="card"><div class="card-body">
    <div class="row">
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('user::general.user',1) }}</label>
            <select name="user_id" class="form-control" required>@foreach($users as $u)<option value="{{$u->id}}">{{$u->full_name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.employee_number',1) }}</label><input name="employee_number" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('branch::general.branch',1) }}</label>
            <select name="branch_id" class="form-control"><option value=""></option>@foreach($branches as $b)<option value="{{$b->id}}">{{$b->name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.department',1) }}</label><input name="department" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.job_title',1) }}</label><input name="job_title" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>KRA PIN</label><input name="kra_pin" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.salary_type',1) }}</label>
            <select name="salary_type" class="form-control"><option value="monthly">{{ trans_choice('payroll::general.monthly',1) }}</option><option value="hourly">{{ trans_choice('payroll::general.hourly',1) }}</option><option value="daily">{{ trans_choice('payroll::general.daily',1) }}</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.basic_salary',1) }}</label><input name="basic_salary" class="form-control numeric" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.hourly',1) }} Rate</label><input name="hourly_rate" class="form-control numeric" value="0"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.daily',1) }} Rate</label><input name="daily_rate" class="form-control numeric" value="0"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.template',1) }}</label>
            <select name="payroll_template_id" class="form-control"><option value=""></option>@foreach($templates as $t)<option value="{{$t->id}}">{{$t->name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.bank_name',1) }}</label><input name="bank_name" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.account_number',1) }}</label><input name="account_number" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>NSSF Number</label><input name="nssf_number" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>SHIF Number</label><input name="shif_number" class="form-control"></div></div>
        <div class="col-md-4"><div class="form-group"><label>Date Hired</label><input type="date" name="date_hired" class="form-control"></div></div>
    </div>
</div><div class="card-footer"><button class="btn btn-primary">{{ trans_choice('core::general.save',1) }}</button></div></div>
</form>
</section>
@endsection

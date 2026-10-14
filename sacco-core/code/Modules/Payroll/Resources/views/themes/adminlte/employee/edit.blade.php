@extends('core::layouts.master')
@section('title') {{ trans_choice('core::general.edit',1) }} {{ trans_choice('payroll::general.employee',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('core::general.edit',1) }} {{ trans_choice('payroll::general.employee',1) }}</h1></div></section>
<section class="content">
<form method="post" action="{{ url('payroll/employee/'.$employee->id.'/update') }}">@csrf
<div class="card"><div class="card-body">
    <div class="row">
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('user::general.user',1) }}</label>
            <select name="user_id" class="form-control" required>@foreach($users as $u)<option value="{{$u->id}}" @if($employee->user_id==$u->id) selected @endif>{{$u->full_name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.employee_number',1) }}</label><input name="employee_number" class="form-control" value="{{$employee->employee_number}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('branch::general.branch',1) }}</label>
            <select name="branch_id" class="form-control">@foreach($branches as $b)<option value="{{$b->id}}" @if($employee->branch_id==$b->id) selected @endif>{{$b->name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.department',1) }}</label><input name="department" class="form-control" value="{{$employee->department}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.job_title',1) }}</label><input name="job_title" class="form-control" value="{{$employee->job_title}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>KRA PIN</label><input name="kra_pin" class="form-control" value="{{$employee->kra_pin}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.salary_type',1) }}</label>
            <select name="salary_type" class="form-control">
                <option value="monthly" @if($employee->salary_type=='monthly') selected @endif>{{ trans_choice('payroll::general.monthly',1) }}</option>
                <option value="hourly" @if($employee->salary_type=='hourly') selected @endif>{{ trans_choice('payroll::general.hourly',1) }}</option>
                <option value="daily" @if($employee->salary_type=='daily') selected @endif>{{ trans_choice('payroll::general.daily',1) }}</option>
            </select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.basic_salary',1) }}</label><input name="basic_salary" class="form-control numeric" value="{{$employee->basic_salary}}" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.template',1) }}</label>
            <select name="payroll_template_id" class="form-control"><option value=""></option>@foreach($templates as $t)<option value="{{$t->id}}" @if($employee->payroll_template_id==$t->id) selected @endif>{{$t->name}}</option>@endforeach</select></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.bank_name',1) }}</label><input name="bank_name" class="form-control" value="{{$employee->bank_name}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>{{ trans_choice('payroll::general.account_number',1) }}</label><input name="account_number" class="form-control" value="{{$employee->account_number}}"></div></div>
        <div class="col-md-4"><div class="form-group"><label>Active</label><select name="is_active" class="form-control"><option value="1" @if($employee->is_active) selected @endif>Yes</option><option value="0" @if(!$employee->is_active) selected @endif>No</option></select></div></div>
    </div>
</div><div class="card-footer"><button class="btn btn-primary">{{ trans_choice('core::general.save',1) }}</button></div></div>
</form>
</section>
@endsection

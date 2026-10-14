@extends('core::layouts.master')
@section('title') {{ trans_choice('payroll::general.employee',1) }} #{{ $employee->id }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ $employee->user->full_name ?? 'Employee' }}</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <table class="table">
        <tr><td>{{ trans_choice('payroll::general.employee_number',1) }}</td><td>{{ $employee->employee_number }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.department',1) }}</td><td>{{ $employee->department }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.job_title',1) }}</td><td>{{ $employee->job_title }}</td></tr>
        <tr><td>KRA PIN</td><td>{{ $employee->kra_pin }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.basic_salary',1) }}</td><td>{{ number_format($employee->basic_salary, 2) }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.salary_type',1) }}</td><td>{{ ucfirst($employee->salary_type) }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.bank_name',1) }}</td><td>{{ $employee->bank_name }}</td></tr>
        <tr><td>{{ trans_choice('payroll::general.account_number',1) }}</td><td>{{ $employee->account_number }}</td></tr>
    </table>
    @can('payroll.payroll.employees.edit')
    <a href="{{url('payroll/employee/'.$employee->id.'/edit')}}" class="btn btn-primary">{{ trans_choice('core::general.edit',2) }}</a>
    @endcan
</div></div></section>
@endsection

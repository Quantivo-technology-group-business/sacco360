@extends('core::layouts.master')
@section('title') {{ trans_choice('payroll::general.employee', 2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>{{ trans_choice('payroll::general.employee', 2) }}
        @can('payroll.payroll.employees.create')
        <a href="{{url('payroll/employee/create')}}" class="btn btn-primary btn-sm">{{ trans_choice('core::general.add',1) }}</a>
        @endcan
    </h1>
</div></section>
<section class="content">
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-striped">
            <thead><tr>
                <th>#</th><th>{{ trans_choice('user::general.user',1) }}</th>
                <th>{{ trans_choice('payroll::general.employee_number',1) }}</th>
                <th>{{ trans_choice('payroll::general.department',1) }}</th>
                <th>{{ trans_choice('payroll::general.basic_salary',1) }}</th>
                <th>{{ trans_choice('payroll::general.salary_type',1) }}</th>
                <th></th>
            </tr></thead>
            <tbody>
            @foreach($data as $row)
                <tr>
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->user->full_name ?? '-' }}</td>
                    <td>{{ $row->employee_number }}</td>
                    <td>{{ $row->department }}</td>
                    <td>{{ number_format($row->basic_salary, 2) }}</td>
                    <td>{{ ucfirst($row->salary_type) }}</td>
                    <td>
                        <a href="{{url('payroll/employee/'.$row->id.'/show')}}" class="btn btn-info btn-xs">{{ trans_choice('core::general.detail',2) }}</a>
                        @can('payroll.payroll.employees.edit')
                        <a href="{{url('payroll/employee/'.$row->id.'/edit')}}" class="btn btn-default btn-xs">{{ trans_choice('core::general.edit',2) }}</a>
                        @endcan
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $data->links() }}
    </div></div>
</section>
@endsection

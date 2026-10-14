@extends('core::layouts.master')
@section('title') {{ trans_choice('payroll::general.run', 2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>{{ trans_choice('payroll::general.run', 2) }}
        @can('payroll.payroll.runs.create')
        <a href="{{url('payroll/run/create')}}" class="btn btn-primary btn-sm">{{ trans_choice('core::general.add',1) }}</a>
        @endcan
    </h1>
</div></section>
<section class="content">
<div class="card"><div class="card-body table-responsive">
    <table class="table table-striped">
        <thead><tr>
            <th>#</th><th>{{ trans_choice('core::general.name',1) }}</th><th>{{ trans_choice('payroll::general.period',1) }}</th>
            <th>Employees</th><th>{{ trans_choice('payroll::general.gross_pay',1) }}</th><th>{{ trans_choice('payroll::general.net_pay',1) }}</th>
            <th>{{ trans_choice('payroll::general.status',1) }}</th><th></th>
        </tr></thead>
        <tbody>
        @foreach($data as $row)
            <tr>
                <td>{{ $row->id }}</td>
                <td>{{ $row->name }}</td>
                <td>{{ $row->period }}</td>
                <td>{{ $row->employee_count }}</td>
                <td>{{ number_format($row->total_gross, 2) }}</td>
                <td>{{ number_format($row->total_net, 2) }}</td>
                <td><span class="badge badge-{{ $row->status=='approved'?'success':($row->status=='draft'?'secondary':'info') }}">{{ ucfirst($row->status) }}</span></td>
                <td><a href="{{url('payroll/run/'.$row->id.'/show')}}" class="btn btn-info btn-xs">{{ trans_choice('core::general.detail',2) }}</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $data->links() }}
</div></div>
</section>
@endsection

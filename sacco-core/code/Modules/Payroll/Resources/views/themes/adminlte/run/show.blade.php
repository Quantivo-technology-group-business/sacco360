@extends('core::layouts.master')
@section('title') {{ trans_choice('payroll::general.run',1) }} #{{ $run->id }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <h1>{{ $run->name }} <small>({{ $run->period }})</small></h1>
</div></section>
<section class="content">
<div class="row">
    <div class="col-md-4">
        <div class="card"><div class="card-header"><h3 class="card-title">Summary</h3></div><div class="card-body">
            <table class="table table-sm">
                <tr><td>Status</td><td><span class="badge badge-{{ $run->status=='approved'?'success':'info' }}">{{ ucfirst($run->status) }}</span></td></tr>
                <tr><td>Employees</td><td>{{ $run->employee_count }}</td></tr>
                <tr><td>{{ trans_choice('payroll::general.gross_pay',1) }}</td><td>{{ number_format($run->total_gross, 2) }}</td></tr>
                <tr><td>{{ trans_choice('payroll::general.net_pay',1) }}</td><td><b>{{ number_format($run->total_net, 2) }}</b></td></tr>
                <tr><td>{{ trans_choice('payroll::general.paye',1) }}</td><td>{{ number_format($run->total_paye, 2) }}</td></tr>
                <tr><td>{{ trans_choice('payroll::general.nssf',1) }}</td><td>{{ number_format($run->total_nssf, 2) }}</td></tr>
                <tr><td>{{ trans_choice('payroll::general.shif',1) }}</td><td>{{ number_format($run->total_shif, 2) }}</td></tr>
                <tr><td>{{ trans_choice('payroll::general.housing_levy',1) }}</td><td>{{ number_format($run->total_housing_levy, 2) }}</td></tr>
            </table>
            @can('payroll.payroll.runs.create')
            @if($run->status == 'draft')
                <a href="{{url('payroll/run/'.$run->id.'/generate')}}" class="btn btn-primary btn-block confirm" data-message="Generate payslips for all active employees?">{{ trans_choice('payroll::general.generate',1) }} Payslips</a>
            @endif
            @if(in_array($run->status, ['processing', 'draft']) && $run->employee_count > 0)
                <a href="{{url('payroll/run/'.$run->id.'/approve')}}" class="btn btn-success btn-block confirm" data-message="Approve this payroll run?">Approve Run</a>
            @endif
            @endcan
        </div></div>
    </div>
    <div class="col-md-8">
        <div class="card"><div class="card-header"><h3 class="card-title">Payslips</h3></div><div class="card-body table-responsive">
            <table class="table table-striped table-sm">
                <thead><tr>
                    <th>#</th><th>{{ trans_choice('payroll::general.employee',1) }}</th><th>{{ trans_choice('payroll::general.basic_salary',1) }}</th>
                    <th>{{ trans_choice('payroll::general.gross_pay',1) }}</th><th>{{ trans_choice('payroll::general.paye',1) }}</th><th>{{ trans_choice('payroll::general.net_pay',1) }}</th><th></th>
                </tr></thead>
                <tbody>
                @foreach($run->payrolls as $p)
                    <tr>
                        <td>{{ $p->id }}</td>
                        <td>{{ $p->employee_name }}</td>
                        <td>{{ number_format($p->basic_salary ?? $p->total_duration_amount, 2) }}</td>
                        <td>{{ number_format($p->gross_pay ?? 0, 2) }}</td>
                        <td>{{ number_format($p->paye ?? 0, 2) }}</td>
                        <td><b>{{ number_format($p->net_pay ?? $p->gross_amount, 2) }}</b></td>
                        <td>
                            <a href="{{url('payroll/'.$p->id.'/show')}}" class="btn btn-info btn-xs">View</a>
                            <a href="{{url('payroll/'.$p->id.'/pdf')}}" class="btn btn-default btn-xs">PDF</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div></div>
    </div>
</div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') Cash Flow Statement @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Cash Flow Statement</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('report/accounting')}}">Accounting Reports</a></li>
                    <li class="breadcrumb-item active">Cash Flow</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Cash Flow Statement @if(!empty($start_date)) &mdash; <b>{{$start_date}} to {{$end_date}}</b> @endif</h3>
            @if(!empty($start_date))
            <div class="card-tools">
                <div class="dropdown">
                    <a href="#" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">Download</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="{{url('report/accounting/cash_flow?download=1&type=csv&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">CSV</a>
                        <a href="{{url('report/accounting/cash_flow?download=1&type=excel_2007&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">Excel (.xlsx)</a>
                        <a href="{{url('report/accounting/cash_flow?download=1&type=pdf&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">PDF</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="card-body">
            <form method="get" action="{{Request::url()}}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>
                            <select class="form-control select2" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{$b->id}}" @if($branch_id==$b->id) selected @endif>{{$b->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date</label>
                            <flat-pickr value="{{$start_date}}" class="form-control" name="start_date" required></flat-pickr>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date</label>
                            <flat-pickr value="{{$end_date}}" class="form-control" name="end_date" required></flat-pickr>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Generate</button>
                        <a href="{{Request::url()}}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($start_date))
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Setup Tip:</strong> To appear in this report, each Chart of Account must have a
        <strong>Cash Flow Category</strong> (Operating / Investing / Financing) set.
        Go to <a href="{{url('accounting/chart_of_account')}}">Chart of Accounts</a> → Edit each account.
    </div>

    @php
        $categories = ['operating'=>'Operating Activities','investing'=>'Investing Activities','financing'=>'Financing Activities'];
    @endphp

    @foreach($categories as $catKey => $catLabel)
    @php $catRows = $data->where('cash_flow_category', $catKey); @endphp
    @if($catRows->count())
    <div class="card">
        <div class="card-header" style="background:#f4f4f4">
            <h5 class="mb-0"><strong>{{$catLabel}}</strong></h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr style="background:#e9ecef">
                        <th>GL Code</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Net Flow</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($catRows as $row)
                @php
                    if(in_array($row->account_type,['income','liability','equity'])){
                        $net = $row->credit - $row->debit;
                    } else {
                        $net = $row->debit - $row->credit;
                    }
                @endphp
                <tr>
                    <td>{{$row->gl_code}}</td>
                    <td>{{$row->name}}</td>
                    <td>{{ucfirst($row->account_type)}}</td>
                    <td class="text-right">{{number_format($row->debit,2)}}</td>
                    <td class="text-right">{{number_format($row->credit,2)}}</td>
                    <td class="text-right @if($net<0) text-danger @else text-success @endif">
                        {{number_format($net,2)}}
                    </td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                <tr style="font-weight:bold;background:#f8f9fa">
                    <td colspan="5" class="text-right">Net {{$catLabel}}</td>
                    <td class="text-right @if(($summary[$catKey]??0)<0) text-danger @else text-success @endif">
                        {{number_format($summary[$catKey]??0,2)}}
                    </td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
    @endforeach

    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <tr><td><strong>Net Cash from Operating Activities</strong></td>
                    <td class="text-right"><strong>{{number_format($summary['operating']??0,2)}}</strong></td></tr>
                <tr><td><strong>Net Cash from Investing Activities</strong></td>
                    <td class="text-right"><strong>{{number_format($summary['investing']??0,2)}}</strong></td></tr>
                <tr><td><strong>Net Cash from Financing Activities</strong></td>
                    <td class="text-right"><strong>{{number_format($summary['financing']??0,2)}}</strong></td></tr>
                <tr style="background:#d4edda">
                    <td><strong>Net Change in Cash</strong></td>
                    <td class="text-right"><strong>{{number_format($summary['total']??0,2)}}</strong></td>
                </tr>
            </table>
        </div>
    </div>
    @endif
</section>
@endsection

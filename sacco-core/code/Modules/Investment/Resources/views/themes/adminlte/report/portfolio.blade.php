@extends('core::layouts.master')
@section('title') Investment Portfolio @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>Investment Portfolio Report</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('investment/report')}}">Investment Reports</a></li>
            <li class="breadcrumb-item active">Portfolio</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Portfolio Summary</h3>
        <div class="card-tools">
            <a href="{{url('investment/report/portfolio?'.http_build_query(array_merge(request()->all(),['download'=>'1'])))}}" class="btn btn-sm btn-info">
                <i class="fas fa-download mr-1"></i> PDF
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="plan_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Plans</option>
                        @foreach($plans as $p) <option value="{{$p->id}}" @if(request('plan_id')==$p->id) selected @endif>{{$p->name}}</option> @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="branch_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Branches</option>
                        @foreach($branches as $b) <option value="{{$b->id}}" @if(request('branch_id')==$b->id) selected @endif>{{$b->name}}</option> @endforeach
                    </select>
                </div>
            </div>
        </form>

        {{-- Summary cards --}}
        <div class="row mb-4">
            <div class="col-md-2"><div class="info-box bg-primary mb-0"><span class="info-box-icon"><i class="fas fa-hashtag"></i></span><div class="info-box-content"><span class="info-box-text">Investments</span><span class="info-box-number">{{$summary['count']}}</span></div></div></div>
            <div class="col-md-2"><div class="info-box bg-info mb-0"><span class="info-box-icon"><i class="fas fa-wallet"></i></span><div class="info-box-content"><span class="info-box-text">Total Invested</span><span class="info-box-number">{{number_format($summary['total_invested'],2)}}</span></div></div></div>
            <div class="col-md-3"><div class="info-box bg-success mb-0"><span class="info-box-icon"><i class="fas fa-chart-line"></i></span><div class="info-box-content"><span class="info-box-text">Profit Accrued</span><span class="info-box-number">{{number_format($summary['total_profit_accrued'],2)}}</span></div></div></div>
            <div class="col-md-2"><div class="info-box bg-secondary mb-0"><span class="info-box-icon"><i class="fas fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Profit Paid</span><span class="info-box-number">{{number_format($summary['total_profit_paid'],2)}}</span></div></div></div>
            <div class="col-md-3"><div class="info-box bg-warning mb-0"><span class="info-box-icon"><i class="fas fa-clock"></i></span><div class="info-box-content"><span class="info-box-text">Profit Due</span><span class="info-box-number">{{number_format($summary['profit_due'],2)}}</span></div></div></div>
        </div>

        {{-- By plan --}}
        <h5>By Plan</h5>
        <table class="table table-bordered table-sm mb-4">
            <thead><tr><th>Plan</th><th class="text-right">Count</th><th class="text-right">Invested</th><th class="text-right">Profit Accrued</th><th class="text-right">Profit Paid</th></tr></thead>
            <tbody>
            @foreach($byPlan as $row)
            <tr>
                <td>{{$row['plan']}}</td>
                <td class="text-right">{{$row['count']}}</td>
                <td class="text-right">{{number_format($row['invested'],2)}}</td>
                <td class="text-right text-success">{{number_format($row['profit_accrued'],2)}}</td>
                <td class="text-right">{{number_format($row['profit_paid'],2)}}</td>
            </tr>
            @endforeach
            </tbody>
        </table>

        {{-- Detailed list --}}
        <h5>Detailed List</h5>
        <table class="table table-sm table-bordered table-hover">
            <thead><tr><th>Account #</th><th>Member</th><th>Plan</th><th>Branch</th><th class="text-right">Principal</th><th class="text-right">Rate</th><th>Maturity</th><th class="text-right">Profit Accrued</th><th class="text-right">Profit Paid</th><th class="text-right">Due</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($data as $inv)
            <tr>
                <td><a href="{{url('investment/'.$inv->id.'/show')}}">{{$inv->account_number}}</a></td>
                <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
                <td>{{optional($inv->plan)->name}}</td>
                <td>{{optional($inv->branch)->name}}</td>
                <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
                <td class="text-right">{{$inv->effective_rate}}%</td>
                <td>{{$inv->maturity_date?->format('d M Y') ?? '—'}}</td>
                <td class="text-right text-success">{{number_format($inv->total_profit_accrued,2)}}</td>
                <td class="text-right">{{number_format($inv->total_profit_paid,2)}}</td>
                <td class="text-right @if($inv->profit_due>0) text-warning font-weight-bold @endif">{{number_format($inv->profit_due,2)}}</td>
                <td>{!! $inv->status_badge !!}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</section>
@endsection

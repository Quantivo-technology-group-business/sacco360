@extends('core::layouts.master')
@section('title') Member Investments @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1><i class="fas fa-wallet mr-2"></i>Member Investments</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item active">Investments</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

{{-- Summary tiles --}}
<div class="row">
    @php
        $activeInv = $investments->getCollection()->where('status','active');
    @endphp
    <div class="col-md-3">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Invested</span>
                <span class="info-box-number">{{number_format($investments->getCollection()->sum('principal_amount'),2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Profit Accrued</span>
                <span class="info-box-number">{{number_format($investments->getCollection()->sum('total_profit_accrued'),2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Profit Due (Unpaid)</span>
                <span class="info-box-number">{{number_format($investments->getCollection()->sum(fn($i) => $i->profit_due),2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-teal">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Active Investments</span>
                <span class="info-box-number">{{$activeInv->count()}}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Investments</h3>
        <div class="card-tools">
            @can('investment.investments.create')
            <a href="{{url('investment/create')}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Investment</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        {{-- Filters --}}
        <form method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $s)
                        <option value="{{$s}}" @if(request('status')==$s) selected @endif>{{ucfirst($s)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="plan_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Plans</option>
                        @foreach($plans as $p)
                        <option value="{{$p->id}}" @if(request('plan_id')==$p->id) selected @endif>{{$p->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{url('investment')}}" class="btn btn-sm btn-secondary">Reset</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th>Account #</th><th>Member</th><th>Plan</th><th>Principal</th>
                        <th>Profit Accrued</th><th>Profit Due</th><th>Start</th>
                        <th>Maturity</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($investments as $inv)
                <tr>
                    <td><a href="{{url('investment/'.$inv->id.'/show')}}"><strong>{{$inv->account_number}}</strong></a></td>
                    <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
                    <td>{{optional($inv->plan)->name}}</td>
                    <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
                    <td class="text-right text-success">{{number_format($inv->total_profit_accrued,2)}}</td>
                    <td class="text-right @if($inv->profit_due>0) text-warning font-weight-bold @endif">
                        {{number_format($inv->profit_due,2)}}
                    </td>
                    <td>{{$inv->start_date?->format('d M Y')}}</td>
                    <td>
                        @if($inv->maturity_date)
                            @if($inv->maturity_date->isPast() && $inv->status==='active')
                                <span class="text-danger font-weight-bold">{{$inv->maturity_date->format('d M Y')}} ⚠</span>
                            @else
                                {{$inv->maturity_date->format('d M Y')}}
                            @endif
                        @else <span class="text-muted">Open</span> @endif
                    </td>
                    <td>{!! $inv->status_badge !!}</td>
                    <td>
                        <a href="{{url('investment/'.$inv->id.'/show')}}" class="btn btn-xs btn-info">View</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-3">No investments found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{$investments->appends(request()->all())->links()}}
    </div>
</div>
</section>
@endsection

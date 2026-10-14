@extends('core::layouts.master')
@section('title') My Investments @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1><i class="fas fa-wallet mr-2" style="color:#7c3aed"></i>My Investments</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('portal')}}">Dashboard</a></li>
            <li class="breadcrumb-item active">My Investments</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

{{-- Summary --}}
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box" style="border-left:4px solid #7c3aed">
            <span class="info-box-icon" style="background:linear-gradient(135deg,#6d28d9,#7c3aed)"><i class="fas fa-wallet"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Invested</span>
                <span class="info-box-number">{{number_format($summary['total_invested'],2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Profit Accrued</span>
                <span class="info-box-number">{{number_format($summary['total_profit_accrued'],2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Profit Due</span>
                <span class="info-box-number">{{number_format($summary['profit_due'],2)}}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box">
            <span class="info-box-icon" style="background:linear-gradient(135deg,#0f766e,#14b8a6)"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Current Value</span>
                <span class="info-box-number">{{number_format($summary['current_value'],2)}}</span>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Investment Accounts</h5>
        <div class="card-tools">
            <a href="{{url('portal/investment/plans')}}" class="btn btn-sm"
               style="border:1.5px solid #7c3aed;color:#7c3aed">
                <i class="fas fa-star mr-1"></i>Browse Plans & Invest
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead><tr>
                <th>Account #</th><th>Plan</th><th class="text-right">Principal</th>
                <th class="text-right">Profit</th><th class="text-right">Current Value</th>
                <th>Maturity</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($investments as $inv)
            <tr>
                <td><a href="{{url('portal/investment/'.$inv->id)}}" style="color:#7c3aed;font-weight:600">{{$inv->account_number}}</a></td>
                <td>{{optional($inv->plan)->name}}</td>
                <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
                <td class="text-right text-success">{{number_format($inv->total_profit_accrued,2)}}</td>
                <td class="text-right font-weight-bold">{{number_format((float)$inv->principal_amount+(float)$inv->total_profit_accrued,2)}}</td>
                <td>
                    @if($inv->maturity_date)
                        @if($inv->maturity_date->isPast())<span class="text-danger font-weight-bold">{{$inv->maturity_date->format('d M Y')}} ⚠</span>
                        @else {{$inv->maturity_date->format('d M Y')}} @endif
                    @else <span class="text-muted">Open</span> @endif
                </td>
                <td>{!! $inv->status_badge !!}</td>
                <td><a href="{{url('portal/investment/'.$inv->id)}}" class="btn btn-xs btn-outline-secondary">Details</a></td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center py-4 text-muted">
                    You have no investments yet.<br>
                    <a href="{{url('portal/investment/plans')}}" class="btn btn-sm mt-2" style="background:#7c3aed;color:#fff">
                        <i class="fas fa-star mr-1"></i>Explore Investment Plans
                    </a>
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-3 py-2">{{$investments->links()}}</div>
    </div>
</div>
</section>
@endsection

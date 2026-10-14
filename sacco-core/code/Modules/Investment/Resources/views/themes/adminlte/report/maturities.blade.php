@extends('core::layouts.master')
@section('title') Maturing Investments @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Maturing Investments</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('investment/report')}}">Investment Reports</a></li>
            <li class="breadcrumb-item active">Maturities</li>
        </ol></div>
    </div>
</div></section>
<section class="content">
<div class="card">
    <div class="card-header"><h3 class="card-title">Investments Maturing in Period</h3></div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3"><label>Start Date</label>
                    <input type="date" name="start_date" value="{{$start_date}}" class="form-control form-control-sm"></div>
                <div class="col-md-3"><label>End Date</label>
                    <input type="date" name="end_date" value="{{$end_date}}" class="form-control form-control-sm"></div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                </div>
            </div>
        </form>
        <table class="table table-bordered table-hover table-sm">
            <thead><tr>
                <th>Account #</th><th>Member</th><th>Plan</th><th class="text-right">Principal</th>
                <th class="text-right">Projected Payout</th><th>Maturity Date</th><th>Status</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($data as $inv)
            @php $calc = app(\Modules\Investment\Services\ProfitCalculatorService::class)->maturityPayout($inv); @endphp
            <tr class="@if($inv->maturity_date->isPast()) table-warning @endif">
                <td><a href="{{url('investment/'.$inv->id.'/show')}}">{{$inv->account_number}}</a></td>
                <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
                <td>{{optional($inv->plan)->name}}</td>
                <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
                <td class="text-right text-primary font-weight-bold">{{number_format($calc['payout_total'],2)}}</td>
                <td>
                    {{$inv->maturity_date->format('d M Y')}}
                    @if($inv->maturity_date->isPast()) <span class="badge badge-danger ml-1">OVERDUE</span> @endif
                </td>
                <td>{!! $inv->status_badge !!}</td>
                <td><a href="{{url('investment/'.$inv->id.'/show')}}" class="btn btn-xs btn-primary">Process</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-3">No investments maturing in this period.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</section>
@endsection

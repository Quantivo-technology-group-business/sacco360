@extends('core::layouts.master')
@section('title') {{$plan->name}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>{{$plan->name}} <small class="text-muted">{{$plan->code}}</small></h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('investment/plan')}}">Plans</a></li>
            <li class="breadcrumb-item active">{{$plan->name}}</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="row">
<div class="col-md-4">
    <div class="card card-primary card-outline">
        <div class="card-body">
            <table class="table table-sm table-borderless">
                <tr><td>Type</td>        <td><strong>{{$plan->plan_type_label}}</strong></td></tr>
                <tr><td>Rate</td>        <td><strong>{{$plan->return_rate_type==='tiered'?'Tiered':$plan->return_rate.'% p.a.'}}</strong></td></tr>
                <tr><td>Method</td>      <td>{{ucfirst($plan->interest_method)}}</td></tr>
                <tr><td>Compounding</td> <td>{{ucfirst($plan->compounding_frequency)}}</td></tr>
                <tr><td>Profit Posting</td><td>{{ucfirst(str_replace('_',' ',$plan->profit_posting_frequency))}}</td></tr>
                <tr><td>Min Amount</td>  <td>{{number_format($plan->min_amount,2)}}</td></tr>
                <tr><td>Max Amount</td>  <td>{{$plan->max_amount ? number_format($plan->max_amount,2) : 'Unlimited'}}</td></tr>
                <tr><td>Duration</td>    <td>{{$plan->min_duration_months}}@if($plan->max_duration_months)–{{$plan->max_duration_months}}@endif months</td></tr>
                <tr><td>Early Withdrawal</td><td>{{$plan->allow_early_withdrawal?'Yes (Penalty: '.$plan->early_withdrawal_penalty_rate.'%)':'No'}}</td></tr>
                <tr><td>Status</td>      <td>@if($plan->active)<span class="badge badge-success">Active</span>@else<span class="badge badge-secondary">Inactive</span>@endif</td></tr>
            </table>
        </div>
        <div class="card-footer">
            @can('investment.plans.edit')
            <a href="{{url('investment/plan/'.$plan->id.'/edit')}}" class="btn btn-warning btn-sm">Edit Plan</a>
            @endcan
        </div>
    </div>

    @if($plan->tiers->count())
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Rate Tiers</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Label</th><th>Min</th><th>Max</th><th>Rate</th></tr></thead>
                <tbody>
                @foreach($plan->tiers as $t)
                <tr><td>{{$t->label}}</td><td>{{number_format($t->min_amount,2)}}</td>
                    <td>{{$t->max_amount?number_format($t->max_amount,2):'—'}}</td>
                    <td><strong>{{$t->return_rate}}%</strong></td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card">
        <div class="card-header"><h6 class="mb-0">GL Account Linkage</h6></div>
        <div class="card-body">
            <table class="table table-sm table-borderless">
                <tr><td>Investment Control</td><td>{{optional($plan->investment_control_account)->name ?? '<span class="text-warning">Not set</span>'}}</td></tr>
                <tr><td>Interest Expense</td>  <td>{{optional($plan->interest_expense_account)->name   ?? '<span class="text-warning">Not set</span>'}}</td></tr>
                <tr><td>Cash / Bank</td>        <td>{{optional($plan->cash_account)->name               ?? '<span class="text-warning">Not set</span>'}}</td></tr>
                <tr><td>Profit Payable</td>     <td>{{optional($plan->profit_payable_account)->name     ?? '<span class="text-warning">Not set</span>'}}</td></tr>
            </table>
        </div>
    </div>
</div>
<div class="col-md-8">
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Active Investments under this Plan</h5>
            <div class="card-tools">
                @can('investment.investments.create')
                <a href="{{url('investment/create')}}" class="btn btn-primary btn-sm">+ New Investment</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead><tr>
                    <th>Account #</th><th>Member</th><th>Principal</th>
                    <th>Profit Accrued</th><th>Profit Paid</th><th>Status</th>
                </tr></thead>
                <tbody>
                @forelse($investments as $inv)
                <tr>
                    <td><a href="{{url('investment/'.$inv->id.'/show')}}">{{$inv->account_number}}</a></td>
                    <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
                    <td>{{number_format($inv->principal_amount,2)}}</td>
                    <td class="text-success">{{number_format($inv->total_profit_accrued,2)}}</td>
                    <td>{{number_format($inv->total_profit_paid,2)}}</td>
                    <td>{!! $inv->status_badge !!}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">No investments yet.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{$investments->links()}}
        </div>
    </div>
</div>
</div>
</section>
@endsection

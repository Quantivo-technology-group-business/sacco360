@extends('core::layouts.master')
@section('title') Investment Plans @endsection
@section('styles')
<style>
.plan-card { border-radius:16px !important; transition:all .25s; overflow:hidden; }
.plan-card:hover { transform:translateY(-6px); box-shadow:0 16px 40px rgba(0,0,0,.14) !important; }
.plan-hero-rate { font-size:2.8rem; font-weight:900; line-height:1; }
.plan-badge { font-size:.68rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; }
.plan-stat { font-size:.72rem; color:#64748b; }
.plan-stat strong { color:#1e293b; }
</style>
@endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6">
            <h1><i class="fas fa-star mr-2" style="color:#d97706"></i>Investment Plans</h1>
            <p class="text-muted mb-0">Choose a plan that matches your goals and start growing your money.</p>
        </div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('portal')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('portal/investment')}}">My Investments</a></li>
            <li class="breadcrumb-item active">Plans</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

@if($plans->isEmpty())
    <div class="card"><div class="card-body text-center py-5 text-muted">
        <i class="fas fa-chart-line fa-4x mb-3 d-block" style="opacity:.3;color:#7c3aed"></i>
        No investment plans available at this time.<br>Please check back later.
    </div></div>
@else
<div class="row">
@foreach($plans as $plan)
@php
    $colours = ['fixed_deposit'=>['#3b82f6','#1d4ed8'],'bond'=>['#0891b2','#0e7490'],
                'unit_trust'=>['#16a34a','#166534'],'equity'=>['#d97706','#92400e'],
                'real_estate'=>['#dc2626','#991b1b'],'money_market'=>['#14b8a6','#0f766e'],'other'=>['#7c3aed','#5b21b6']];
    [$c1,$c2] = $colours[$plan->plan_type] ?? ['#7c3aed','#5b21b6'];
    $icons = ['fixed_deposit'=>'fa-piggy-bank','bond'=>'fa-file-contract','unit_trust'=>'fa-layer-group',
              'equity'=>'fa-chart-bar','real_estate'=>'fa-building','money_market'=>'fa-money-bill-wave','other'=>'fa-star'];
    $icon  = $icons[$plan->plan_type] ?? 'fa-star';
@endphp
<div class="col-lg-4 col-md-6 mb-4">
    <div class="card plan-card mb-0" style="border:none;box-shadow:0 4px 16px rgba(0,0,0,.09)">
        {{-- Coloured header --}}
        <div style="background:linear-gradient(135deg,{{$c1}},{{$c2}});padding:1.4rem 1.4rem 2.5rem;color:#fff;position:relative">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div style="background:rgba(255,255,255,.18);border-radius:10px;width:44px;height:44px;
                            display:flex;align-items:center;justify-content:center;font-size:1.2rem">
                    <i class="fas {{$icon}}"></i>
                </div>
                <span class="plan-badge" style="background:rgba(255,255,255,.22);padding:.25rem .7rem;border-radius:20px">
                    {{$plan->plan_type_label}}
                </span>
            </div>
            <h5 style="font-weight:800;margin:0 0 .25rem;font-size:1.05rem">{{$plan->name}}</h5>
            @if($plan->description)
            <p style="font-size:.75rem;opacity:.85;margin:0">{{Str::limit($plan->description,80)}}</p>
            @endif
        </div>

        {{-- Rate badge overlapping --}}
        <div style="display:flex;justify-content:center;margin-top:-1.6rem;position:relative;z-index:2">
            <div style="background:#fff;border-radius:16px;padding:.6rem 1.6rem;
                        box-shadow:0 4px 16px rgba(0,0,0,.12);text-align:center;min-width:140px">
                <div class="plan-hero-rate" style="color:{{$c1}}">
                    @if($plan->return_rate_type==='tiered') Tiered @else {{$plan->return_rate}}% @endif
                </div>
                <div style="font-size:.65rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.06em">
                    Annual Return
                </div>
            </div>
        </div>

        <div class="card-body pt-2">
            {{-- Stats grid --}}
            <div class="row text-center mt-1 mb-3">
                <div class="col-4">
                    <div class="plan-stat">Min Amount<br><strong>{{number_format($plan->min_amount,0)}}</strong></div>
                </div>
                <div class="col-4">
                    <div class="plan-stat">Duration<br>
                        <strong>{{$plan->min_duration_months}}@if($plan->max_duration_months)–{{$plan->max_duration_months}}@endif mo</strong>
                    </div>
                </div>
                <div class="col-4">
                    <div class="plan-stat">Method<br><strong>{{ucfirst($plan->interest_method)}}</strong></div>
                </div>
            </div>

            @if($plan->return_rate_type==='tiered' && $plan->tiers->count())
            <div class="mb-3 p-2" style="background:#f8fafc;border-radius:8px">
                <div style="font-size:.68rem;font-weight:700;color:#94a3b8;text-transform:uppercase;margin-bottom:.4rem">Rate Tiers</div>
                @foreach($plan->tiers as $tier)
                <div class="d-flex justify-content-between" style="font-size:.72rem">
                    <span class="text-muted">{{number_format($tier->min_amount,0)}} – {{$tier->max_amount?number_format($tier->max_amount,0):'∞'}}</span>
                    <strong style="color:{{$c1}}">{{$tier->return_rate}}%</strong>
                </div>
                @endforeach
            </div>
            @endif

            @if($plan->allow_early_withdrawal)
            <div class="mb-2" style="font-size:.72rem;color:#64748b">
                <i class="fas fa-check-circle text-success mr-1"></i>Early withdrawal allowed
                @if($plan->early_withdrawal_penalty_rate > 0)
                    ({{$plan->early_withdrawal_penalty_rate}}% penalty)
                @endif
            </div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <a href="{{url('portal/investment/plans/'.$plan->id)}}"
                   class="btn btn-block font-weight-bold"
                   style="background:{{$c1}};color:#fff;border-radius:10px;padding:.55rem">
                    <i class="fas fa-calculator mr-1"></i>Calculate & Invest
                </a>
            </div>
        </div>
    </div>
</div>
@endforeach
</div>
@endif
</section>
@endsection

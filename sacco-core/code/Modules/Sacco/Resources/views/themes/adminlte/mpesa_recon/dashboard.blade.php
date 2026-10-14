@extends('core::layouts.master')
@section('title') M-Pesa Reconciliation @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1><i class="fab fa-safari mr-2 text-success"></i>M-Pesa Reconciliation</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item active">M-Pesa Recon</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

{{-- KPI tiles --}}
<div class="row">
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">C2B Today</span>
                <span class="info-box-number">{{number_format($stats['c2b_today_amount'],2)}}</span>
                <span class="progress-description">{{$stats['c2b_today']}} transactions</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">B2C Today</span>
                <span class="info-box-number">{{number_format($stats['b2c_today_amount'],2)}}</span>
                <span class="progress-description">{{$stats['b2c_today']}} transactions</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Unreconciled C2B</span>
                <span class="info-box-number">{{$stats['c2b_unreconciled']}}</span>
                <span class="progress-description">Pending reconciliation</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-times-circle"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Failed Today</span>
                <span class="info-box-number">{{$stats['c2b_failed_today'] + $stats['b2c_failed_today']}}</span>
                <span class="progress-description">C2B: {{$stats['c2b_failed_today']}} | B2C: {{$stats['b2c_failed_today']}}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Float accounts --}}
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="fas fa-wallet mr-1"></i>Float Accounts</h5>
                <div class="card-tools">
                    <a href="{{url('sacco/mpesa-recon/float')}}" class="btn btn-xs btn-outline-secondary">Manage</a>
                </div>
            </div>
            <div class="card-body p-0">
                @forelse($stats['float_accounts'] as $fa)
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <div>
                        <div class="font-weight-bold" style="font-size:.85rem">{{$fa->account_name ?? $fa->short_code}}</div>
                        <div style="font-size:.72rem;color:#64748b">{{strtoupper($fa->provider)}} • {{$fa->short_code}}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-weight-bold @if($fa->isLowFloat()) text-danger @else text-success @endif">
                            {{number_format($fa->current_balance,2)}}
                        </div>
                        @if($fa->isLowFloat())
                        <span class="badge badge-danger" style="font-size:.65rem">LOW FLOAT</span>
                        @endif
                    </div>
                </div>
                @empty
                <div class="text-center text-muted py-3">No float accounts. <a href="{{url('sacco/mpesa-recon/float')}}">Add one.</a></div>
                @endforelse
            </div>
        </div>

        {{-- Month summary --}}
        <div class="card">
            <div class="card-header"><h5 class="card-title mb-0">This Month</h5></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><td class="text-muted">C2B Received</td><td class="text-right font-weight-bold text-success">{{number_format($stats['c2b_month_amount'],2)}}</td></tr>
                    <tr><td class="text-muted">B2C Paid Out</td><td class="text-right font-weight-bold text-danger">{{number_format($stats['b2c_month_amount'],2)}}</td></tr>
                    <tr><td class="text-muted">Open Sessions</td><td class="text-right"><span class="badge badge-warning">{{$stats['open_sessions']}}</span></td></tr>
                    <tr><td class="text-muted">With Discrepancy</td><td class="text-right"><span class="badge badge-danger">{{$stats['sessions_with_variance']}}</span></td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Recent sessions --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Reconciliation Sessions</h5>
                <div class="card-tools">
                    <a href="{{url('sacco/mpesa-recon/create')}}" class="btn btn-sm btn-primary">+ New Session</a>
                    <a href="{{url('sacco/mpesa-recon')}}" class="btn btn-sm btn-outline-secondary ml-1">All Sessions</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr>
                        <th>Reference</th><th>Provider</th><th>Period</th>
                        <th class="text-right">Statement In</th><th class="text-right">Variance</th>
                        <th>Matched</th><th>Status</th><th></th>
                    </tr></thead>
                    <tbody>
                    @forelse($recentSessions as $s)
                    @php
                        $statusColors=['draft'=>'secondary','importing'=>'info','pending_match'=>'warning',
                            'matched'=>'primary','under_review'=>'warning','approved'=>'success',
                            'discrepancy'=>'danger','archived'=>'dark'];
                    @endphp
                    <tr>
                        <td><a href="{{url('sacco/mpesa-recon/'.$s->id.'/show')}}"><strong>{{$s->reference}}</strong></a></td>
                        <td>{{strtoupper($s->provider)}}</td>
                        <td style="font-size:.8rem">{{$s->period_from}} → {{$s->period_to}}</td>
                        <td class="text-right">{{number_format($s->statement_total_in,2)}}</td>
                        <td class="text-right @if(abs($s->net_variance)>0) text-danger font-weight-bold @else text-success @endif">
                            {{number_format($s->net_variance,2)}}
                        </td>
                        <td>
                            <div class="progress" style="height:16px;min-width:60px">
                                @php $total = $s->statement_count_in + $s->statement_count_out; @endphp
                                @php $pct = $total > 0 ? round($s->matched_count/$total*100) : 0; @endphp
                                <div class="progress-bar bg-success" style="width:{{$pct}}%">{{$pct}}%</div>
                            </div>
                        </td>
                        <td><span class="badge badge-{{$statusColors[$s->status]??'secondary'}}">{{ucfirst(str_replace('_',' ',$s->status))}}</span></td>
                        <td><a href="{{url('sacco/mpesa-recon/'.$s->id.'/show')}}" class="btn btn-xs btn-info">Open</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-3">No sessions yet. <a href="{{url('sacco/mpesa-recon/create')}}">Create one.</a></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent C2B transactions --}}
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent C2B Transactions</h5>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead><tr><th>Receipt</th><th>Phone</th><th>Account Ref</th><th class="text-right">Amount</th><th>Time</th><th>Reconciled</th></tr></thead>
                    <tbody>
                    @forelse($recentC2b as $t)
                    <tr>
                        <td><small>{{$t->trans_id}}</small></td>
                        <td>{{$t->msisdn}}</td>
                        <td>{{$t->bill_ref_number}}</td>
                        <td class="text-right text-success font-weight-bold">{{number_format($t->trans_amount,2)}}</td>
                        <td><small>{{$t->created_at}}</small></td>
                        <td>
                            @if($t->is_reconciled) <span class="badge badge-success">✓</span>
                            @else <span class="badge badge-warning">Pending</span> @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">No C2B transactions.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</section>
@endsection

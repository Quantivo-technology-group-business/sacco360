@extends('core::layouts.master')
@section('title') Reconciliation Sessions @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>M-Pesa Reconciliation Sessions</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item active">Sessions</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Sessions</h3>
        <div class="card-tools">
            @can('sacco.mpesa_recon.create')
            <a href="{{url('sacco/mpesa-recon/create')}}" class="btn btn-primary btn-sm">+ New Session</a>
            @endcan
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="mb-3">
            <div class="row">
                <div class="col-md-3">
                    <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Statuses</option>
                        @foreach(['draft','importing','pending_match','matched','under_review','approved','discrepancy','archived'] as $s)
                        <option value="{{$s}}" @if(request('status')==$s) selected @endif>{{ucfirst(str_replace('_',' ',$s))}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="provider" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">All Providers</option>
                        @foreach(['mpesa','airtel_money','tkash','mtn','other'] as $p)
                        <option value="{{$p}}" @if(request('provider')==$p) selected @endif>{{strtoupper($p)}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <a href="{{url('sacco/mpesa-recon')}}" class="btn btn-sm btn-secondary">Reset</a>
                </div>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr>
                        <th>Reference</th><th>Provider</th><th>Period</th><th>Account Type</th>
                        <th class="text-right">Stmt In</th><th class="text-right">Stmt Out</th>
                        <th class="text-right">Net Variance</th>
                        <th>Match Progress</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($sessions as $s)
                @php
                    $sc=['draft'=>'secondary','importing'=>'info','pending_match'=>'warning',
                         'matched'=>'primary','under_review'=>'warning','approved'=>'success',
                         'discrepancy'=>'danger','archived'=>'dark'];
                    $total = $s->statement_count_in + $s->statement_count_out;
                    $pct   = $total > 0 ? round($s->matched_count/$total*100) : 0;
                @endphp
                <tr>
                    <td><a href="{{url('sacco/mpesa-recon/'.$s->id.'/show')}}"><strong>{{$s->reference}}</strong></a></td>
                    <td><span class="badge badge-success">{{strtoupper($s->provider)}}</span></td>
                    <td style="font-size:.8rem">{{$s->period_from}} → {{$s->period_to}}</td>
                    <td>{{strtoupper($s->account_type)}}</td>
                    <td class="text-right">{{number_format($s->statement_total_in,2)}}</td>
                    <td class="text-right">{{number_format($s->statement_total_out,2)}}</td>
                    <td class="text-right @if(abs($s->net_variance)>0.01) text-danger font-weight-bold @else text-success @endif">
                        {{number_format($s->net_variance,2)}}
                    </td>
                    <td style="min-width:100px">
                        <div class="progress" style="height:18px">
                            <div class="progress-bar bg-success" style="width:{{$pct}}%">{{$pct}}%</div>
                        </div>
                        <small class="text-muted">{{$s->matched_count}}/{{$total}} | {{$s->unmatched_statement}} unmatched</small>
                    </td>
                    <td><span class="badge badge-{{$sc[$s->status]??'secondary'}}">{{ucfirst(str_replace('_',' ',$s->status))}}</span></td>
                    <td>
                        <div class="btn-group">
                            <a href="{{url('sacco/mpesa-recon/'.$s->id.'/show')}}" class="btn btn-xs btn-info">Open</a>
                            <a href="{{url('sacco/mpesa-recon/'.$s->id.'/audit-log')}}" class="btn btn-xs btn-secondary">Log</a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-4">No sessions found. <a href="{{url('sacco/mpesa-recon/create')}}">Create one.</a></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{$sessions->appends(request()->all())->links()}}
    </div>
</div>
</section>
@endsection

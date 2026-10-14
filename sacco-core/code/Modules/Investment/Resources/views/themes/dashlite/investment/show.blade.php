@extends('core::layouts.master')
@section('title') {{$inv->account_number}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6">
            <h1>{{$inv->account_number}}
                <a href="#" onclick="window.history.back()" class="btn btn-sm btn-outline-secondary ml-2">← Back</a>
            </h1>
        </div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('investment')}}">Investments</a></li>
            <li class="breadcrumb-item active">{{$inv->account_number}}</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="row">

{{-- Left: details + actions --}}
<div class="col-md-4">
<div class="card card-primary card-outline">
    <div class="card-header">
        <h5 class="card-title mb-0">
            {!! $inv->status_badge !!}
            {{optional($inv->plan)->name}}
        </h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-borderless mb-0">
            <tr><td>Member</td>           <td><strong>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</strong></td></tr>
            <tr><td>Account #</td>        <td>{{$inv->account_number}}</td></tr>
            <tr><td>Plan</td>             <td>{{optional($inv->plan)->name}}</td></tr>
            <tr><td>Principal</td>        <td><strong>{{number_format($inv->principal_amount,2)}}</strong></td></tr>
            <tr><td>Effective Rate</td>   <td>{{$inv->effective_rate}}% p.a.</td></tr>
            <tr><td>Duration</td>         <td>{{$inv->duration_months}} months</td></tr>
            <tr><td>Start Date</td>       <td>{{$inv->start_date?->format('d M Y')}}</td></tr>
            <tr><td>Maturity Date</td>    <td>{{$inv->maturity_date?->format('d M Y') ?? '—'}}</td></tr>
            <tr><td>Units Held</td>       <td>{{$inv->units_held ? number_format($inv->units_held) : '—'}}</td></tr>
            <tr style="background:#d4edda"><td><strong>Profit Accrued</strong></td><td class="text-success"><strong>{{number_format($inv->total_profit_accrued,2)}}</strong></td></tr>
            <tr><td>Profit Paid</td>      <td>{{number_format($inv->total_profit_paid,2)}}</td></tr>
            <tr style="background:#fff3cd"><td><strong>Profit Due</strong></td>
                <td class="text-warning"><strong>{{number_format($inv->profit_due,2)}}</strong></td></tr>
            <tr style="background:#cce5ff"><td><strong>Current Value</strong></td>
                <td><strong>{{number_format($inv->current_value,2)}}</strong></td></tr>
            <tr><td>Next Posting</td>     <td>{{$inv->next_profit_posting_date?->format('d M Y') ?? '—'}}</td></tr>
        </table>
    </div>
</div>

{{-- Action buttons --}}
@if($inv->status === 'pending')
<div class="card card-success card-outline">
    <div class="card-header"><h6 class="mb-0">Activate Investment</h6></div>
    <div class="card-body">
        <p class="text-muted small">Activating will lock in the start date and begin accruing profit. GL entries will be posted.</p>
        @can('investment.investments.approve')
        <form method="post" action="{{url('investment/'.$inv->id.'/activate')}}">@csrf
            <button type="submit" class="btn btn-success btn-block confirm">
                <i class="fas fa-check mr-1"></i> Activate Investment
            </button>
        </form>
        @endcan
    </div>
</div>
@endif

@if($inv->status === 'active')
{{-- Post Profit --}}
@can('investment.investments.profit')
<div class="card card-warning card-outline">
    <div class="card-header"><h6 class="mb-0">Post Profit</h6></div>
    <div class="card-body">
        <form method="post" action="{{url('investment/'.$inv->id.'/post-profit')}}">@csrf
            <div class="form-group">
                <label>Profit Amount <span class="text-danger">*</span></label>
                <input type="number" step="0.0001" min="0.01" name="profit_amount" class="form-control" required placeholder="0.00">
                <small class="text-muted">Next scheduled: {{$inv->next_profit_posting_date?->format('d M Y') ?? 'N/A'}}</small>
            </div>
            <div class="form-group">
                <label>Date <span class="text-danger">*</span></label>
                <input type="date" name="date" class="form-control" value="{{date('Y-m-d')}}" required>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="pay_now" value="1" class="form-check-input" id="payNow">
                <label class="form-check-label" for="payNow">Also pay out to member immediately</label>
            </div>
            <button type="submit" class="btn btn-warning btn-block">Post Profit</button>
        </form>
    </div>
</div>

@if($inv->profit_due > 0)
<div class="card">
    <div class="card-header"><h6 class="mb-0">Pay Outstanding Profit</h6></div>
    <div class="card-body">
        <form method="post" action="{{url('investment/'.$inv->id.'/pay-profit')}}">@csrf
            <div class="form-group">
                <label>Amount to Pay (max: {{number_format($inv->profit_due,2)}})</label>
                <input type="number" step="0.0001" name="amount" class="form-control"
                       value="{{$inv->profit_due}}" max="{{$inv->profit_due}}" required>
            </div>
            <div class="form-group">
                <input type="date" name="date" class="form-control" value="{{date('Y-m-d')}}" required>
            </div>
            <button type="submit" class="btn btn-info btn-block">Pay Profit to Member</button>
        </form>
    </div>
</div>
@endif
@endcan

{{-- Maturity payout --}}
@can('investment.investments.close')
<div class="card card-primary card-outline">
    <div class="card-header"><h6 class="mb-0">Maturity Payout</h6></div>
    <div class="card-body">
        <p class="text-muted small">Payout: <strong>{{number_format($maturityCalc['payout_total'],2)}}</strong>
            (Principal: {{number_format($maturityCalc['principal'],2)}} + Remaining Profit: {{number_format($maturityCalc['remaining_profit'],2)}})
        </p>
        <form method="post" action="{{url('investment/'.$inv->id.'/mature')}}">@csrf
            <div class="form-group">
                <label>Payout Date</label>
                <input type="date" name="date" class="form-control" value="{{$inv->maturity_date?->format('Y-m-d') ?? date('Y-m-d')}}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block confirm">Process Maturity Payout</button>
        </form>
    </div>
</div>

@if(optional($inv->plan)->allow_early_withdrawal)
<div class="card card-danger card-outline">
    <div class="card-header"><h6 class="mb-0">Early Withdrawal</h6></div>
    <div class="card-body">
        @php $wd = app(\Modules\Investment\Services\ProfitCalculatorService::class)->earlyWithdrawalPayout($inv); @endphp
        <p class="text-muted small">
            Net Payout: <strong>{{number_format($wd['net_payout'],2)}}</strong>
            (Penalty: {{number_format($wd['penalty'],2)}})
        </p>
        <form method="post" action="{{url('investment/'.$inv->id.'/withdraw')}}">@csrf
            <input type="date" name="date" class="form-control mb-2" value="{{date('Y-m-d')}}" required>
            <button type="submit" class="btn btn-danger btn-block confirm">Confirm Early Withdrawal</button>
        </form>
    </div>
</div>
@endif
@endcan
@endif
</div>

{{-- Right: schedule + transactions --}}
<div class="col-md-8">
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-schedule">Projected Schedule</a></li>
            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-transactions">Transactions</a></li>
        </ul>
    </div>
    <div class="card-body tab-content">
        <div id="tab-schedule" class="tab-pane active">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr>
                        <th>#</th><th>Date</th><th class="text-right">Days</th>
                        <th class="text-right">Profit</th><th class="text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($schedule as $row)
                @php
                    $isPast = \Carbon\Carbon::parse($row['date'])->isPast();
                @endphp
                <tr class="@if($isPast) table-light @endif">
                    <td>{{$row['period']}}</td>
                    <td>{{$row['date']}} @if($isPast)<i class="fas fa-check text-success ml-1" title="Past"></i>@endif</td>
                    <td class="text-right">{{$row['days']}}</td>
                    <td class="text-right text-success">{{number_format($row['profit'],4)}}</td>
                    <td class="text-right"><strong>{{number_format($row['balance'],2)}}</strong></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No schedule calculated.</td></tr>
                @endforelse
                </tbody>
                @if(count($schedule))
                <tfoot>
                    <tr class="table-success font-weight-bold">
                        <td colspan="3" class="text-right">Total Projected Profit</td>
                        <td class="text-right">{{number_format(collect($schedule)->sum('profit'),2)}}</td>
                        <td class="text-right">{{number_format(collect($schedule)->last()['balance']??0,2)}}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div id="tab-transactions" class="tab-pane">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Reference</th><th class="text-right">Amount</th><th>Notes</th></tr>
                </thead>
                <tbody>
                @forelse($inv->transactions->sortByDesc('date') as $txn)
                <tr>
                    <td>{{$txn->date}}</td>
                    <td>
                        @php $colours=['investment'=>'primary','profit_accrual'=>'success','profit_payment'=>'info','withdrawal'=>'danger','maturity_payout'=>'primary','penalty'=>'warning','top_up'=>'secondary']; @endphp
                        <span class="badge badge-{{$colours[$txn->transaction_type]??'secondary'}}">{{$txn->type_label}}</span>
                    </td>
                    <td><small>{{$txn->transaction_number}}</small></td>
                    <td class="text-right font-weight-bold">{{number_format($txn->amount,2)}}</td>
                    <td><small>{{$txn->notes}}</small></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No transactions.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>
</div>
</section>
@endsection

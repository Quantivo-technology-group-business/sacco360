@extends('core::layouts.master')
@section('title') {{$inv->account_number}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6">
            <h1>{{$inv->account_number}}
                <a href="{{url('portal/investment')}}" class="btn btn-sm btn-outline-secondary ml-2">← My Investments</a>
            </h1>
        </div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('portal')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('portal/investment')}}">My Investments</a></li>
            <li class="breadcrumb-item active">{{$inv->account_number}}</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="row">

{{-- Left: summary --}}
<div class="col-md-4">
    <div class="card" style="border-top:4px solid #7c3aed">
        <div class="card-body">
            <div class="d-flex align-items-center mb-3">
                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#6d28d9,#7c3aed);
                            display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.3rem;margin-right:.8rem">
                    <i class="fas fa-wallet"></i>
                </div>
                <div>
                    <div style="font-size:.72rem;color:#94a3b8;font-weight:700;text-transform:uppercase">{{optional($inv->plan)->plan_type_label}}</div>
                    <div style="font-weight:800;font-size:.95rem">{{optional($inv->plan)->name}}</div>
                </div>
            </div>
            {!! $inv->status_badge !!}
            <hr>
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Account #</td>     <td><strong>{{$inv->account_number}}</strong></td></tr>
                <tr><td class="text-muted">Principal</td>     <td><strong style="font-size:1.1rem">{{number_format($inv->principal_amount,2)}}</strong></td></tr>
                <tr><td class="text-muted">Rate</td>          <td><strong style="color:#7c3aed">{{$inv->effective_rate}}% p.a.</strong></td></tr>
                <tr><td class="text-muted">Start Date</td>    <td>{{$inv->start_date?->format('d M Y')}}</td></tr>
                <tr><td class="text-muted">Maturity Date</td> <td>
                    @if($inv->maturity_date)
                        @if($inv->maturity_date->isPast()) <span class="text-danger font-weight-bold">{{$inv->maturity_date->format('d M Y')}} ⚠</span>
                        @else {{$inv->maturity_date->format('d M Y')}} @endif
                    @else <span class="text-muted">Open-ended</span> @endif
                </td></tr>
                <tr><td class="text-muted">Duration</td>      <td>{{$inv->duration_months}} months</td></tr>
            </table>
            <hr>
            <div class="row text-center">
                <div class="col-6">
                    <div style="font-size:.65rem;color:#94a3b8;font-weight:700;text-transform:uppercase">Profit Accrued</div>
                    <div style="font-size:1.2rem;font-weight:900;color:#16a34a">{{number_format($inv->total_profit_accrued,2)}}</div>
                </div>
                <div class="col-6">
                    <div style="font-size:.65rem;color:#94a3b8;font-weight:700;text-transform:uppercase">Profit Paid</div>
                    <div style="font-size:1.2rem;font-weight:900;color:#0891b2">{{number_format($inv->total_profit_paid,2)}}</div>
                </div>
            </div>
            @if($inv->profit_due > 0)
            <div class="alert alert-warning mt-2 py-2 mb-0" style="font-size:.82rem">
                <i class="fas fa-clock mr-1"></i>
                Profit Due: <strong>{{number_format($inv->profit_due,2)}}</strong> — contact us to receive your payment.
            </div>
            @endif
            <div class="mt-3 p-2 rounded text-center" style="background:#f0fdf4;border:2px solid #16a34a">
                <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase">Current Value</div>
                <div style="font-size:2rem;font-weight:900;color:#166534">{{number_format($inv->current_value,2)}}</div>
                <div style="font-size:.7rem;color:#94a3b8">Principal + Accrued Profit</div>
            </div>
        </div>
    </div>

    {{-- Maturity projection --}}
    @if($inv->status === 'active')
    <div class="card">
        <div class="card-header"><h6 class="mb-0">At Maturity</h6></div>
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Principal</td>          <td class="text-right">{{number_format($maturityCalc['principal'],2)}}</td></tr>
                <tr><td class="text-muted">Total Profit</td>        <td class="text-right text-success">{{number_format($maturityCalc['total_profit'],2)}}</td></tr>
                <tr><td class="text-muted">Already Paid</td>        <td class="text-right">{{number_format($maturityCalc['already_paid'],2)}}</td></tr>
                <tr style="border-top:2px solid #e5e7eb">
                    <td><strong>Projected Payout</strong></td>
                    <td class="text-right"><strong style="font-size:1.1rem;color:#1d4ed8">{{number_format($maturityCalc['payout_total'],2)}}</strong></td>
                </tr>
            </table>
        </div>
    </div>
    @endif
</div>

{{-- Right: schedule + transactions --}}
<div class="col-md-8">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-sched">Projected Schedule</a></li>
        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-txns">Transaction History</a></li>
    </ul>
    <div class="tab-content">
        <div id="tab-sched" class="tab-pane active">
            <div class="table-responsive" style="max-height:420px;overflow-y:auto">
                <table class="table table-sm table-bordered">
                    <thead style="position:sticky;top:0;background:#fff">
                        <tr>
                            <th>#</th><th>Date</th><th class="text-right">Days</th>
                            <th class="text-right">Profit</th><th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($schedule as $row)
                    @php $isPast = \Carbon\Carbon::parse($row['date'])->isPast(); @endphp
                    <tr @if($isPast) style="opacity:.6" @endif>
                        <td>{{$row['period']}}</td>
                        <td>{{$row['date']}} @if($isPast)<i class="fas fa-check text-success ml-1"></i>@endif</td>
                        <td class="text-right">{{$row['days']}}</td>
                        <td class="text-right text-success font-weight-bold">{{number_format($row['profit'],4)}}</td>
                        <td class="text-right font-weight-bold">{{number_format($row['balance'],2)}}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No schedule yet.</td></tr>
                    @endforelse
                    </tbody>
                    @if(count($schedule))
                    <tfoot>
                        <tr style="background:#f0fdf4;font-weight:bold">
                            <td colspan="3" class="text-right">Total Projected Profit</td>
                            <td class="text-right text-success">{{number_format(collect($schedule)->sum('profit'),2)}}</td>
                            <td class="text-right">{{number_format(collect($schedule)->last()['balance']??0,2)}}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        <div id="tab-txns" class="tab-pane">
            <table class="table table-sm table-bordered">
                <thead>
                    <tr><th>Date</th><th>Type</th><th>Reference</th><th class="text-right">Amount</th></tr>
                </thead>
                <tbody>
                @forelse($inv->transactions->sortByDesc('date') as $txn)
                @php
                    $badge = ['investment'=>'primary','profit_accrual'=>'success','profit_payment'=>'info',
                              'withdrawal'=>'danger','maturity_payout'=>'primary','penalty'=>'warning','top_up'=>'secondary'];
                @endphp
                <tr>
                    <td>{{$txn->date}}</td>
                    <td><span class="badge badge-{{$badge[$txn->transaction_type]??'secondary'}}">{{$txn->type_label}}</span></td>
                    <td><small class="text-muted">{{$txn->transaction_number}}</small></td>
                    <td class="text-right font-weight-bold">{{number_format($txn->amount,2)}}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-center text-muted py-3">No transactions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>
</section>
@endsection

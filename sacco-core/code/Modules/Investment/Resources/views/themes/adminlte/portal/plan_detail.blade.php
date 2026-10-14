@extends('core::layouts.master')
@section('title') {{$plan->name}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-7">
            <h1>{{$plan->name}}
                <a href="{{url('portal/investment/plans')}}" class="btn btn-sm btn-outline-secondary ml-2">← Plans</a>
            </h1>
        </div>
        <div class="col-sm-5"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('portal')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('portal/investment/plans')}}">Plans</a></li>
            <li class="breadcrumb-item active">{{$plan->name}}</li>
        </ol></div>
    </div></div>
</section>

<section class="content" id="planCalcApp">
<div class="row">

{{-- Plan details --}}
<div class="col-md-4">
    <div class="card card-primary card-outline">
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Type</td>            <td><strong>{{$plan->plan_type_label}}</strong></td></tr>
                <tr><td class="text-muted">Annual Return</td>   <td>
                    <strong style="color:#7c3aed;font-size:1.3rem">
                        @if($plan->return_rate_type==='tiered') Tiered @else {{$plan->return_rate}}% @endif
                    </strong>
                </td></tr>
                <tr><td class="text-muted">Method</td>          <td>{{ucfirst($plan->interest_method)}}</td></tr>
                <tr><td class="text-muted">Compounding</td>     <td>{{ucfirst($plan->compounding_frequency)}}</td></tr>
                <tr><td class="text-muted">Profit Posted</td>   <td>{{ucfirst(str_replace('_',' ',$plan->profit_posting_frequency))}}</td></tr>
                <tr><td class="text-muted">Min Amount</td>      <td><strong>{{number_format($plan->min_amount,2)}}</strong></td></tr>
                @if($plan->max_amount)
                <tr><td class="text-muted">Max Amount</td>      <td>{{number_format($plan->max_amount,2)}}</td></tr>
                @endif
                <tr><td class="text-muted">Min Duration</td>    <td>{{$plan->min_duration_months}} months</td></tr>
                @if($plan->max_duration_months)
                <tr><td class="text-muted">Max Duration</td>    <td>{{$plan->max_duration_months}} months</td></tr>
                @endif
                @if($plan->allow_early_withdrawal)
                <tr><td class="text-muted">Early Withdrawal</td><td class="text-success">✓ Allowed ({{$plan->early_withdrawal_penalty_rate}}% penalty)</td></tr>
                @else
                <tr><td class="text-muted">Early Withdrawal</td><td class="text-danger">✗ Not allowed</td></tr>
                @endif
            </table>
        </div>
    </div>

    @if($plan->tiers->count())
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Rate Tiers</h6></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Label</th><th>Range</th><th class="text-right">Rate</th></tr></thead>
                <tbody>
                @foreach($plan->tiers as $t)
                <tr>
                    <td>{{$t->label ?? '—'}}</td>
                    <td style="font-size:.8rem">{{number_format($t->min_amount,0)}} – {{$t->max_amount ? number_format($t->max_amount,0) : '∞'}}</td>
                    <td class="text-right"><strong style="color:#7c3aed">{{$t->return_rate}}%</strong></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    @if($plan->terms_conditions)
    <div class="card">
        <div class="card-header"><h6 class="mb-0">Terms & Conditions</h6></div>
        <div class="card-body"><p style="font-size:.82rem;white-space:pre-line">{{$plan->terms_conditions}}</p></div>
    </div>
    @endif
</div>

{{-- Live Calculator --}}
<div class="col-md-8">
    <div class="card card-success card-outline">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-calculator mr-2"></i>Returns Calculator</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Investment Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="100" min="{{$plan->min_amount}}"
                               @if($plan->max_amount) max="{{$plan->max_amount}}" @endif
                               v-model="amount" @input="calculate"
                               class="form-control form-control-lg" placeholder="{{number_format($plan->min_amount,0)}}">
                    </div>
                    <small class="text-muted">Min: {{number_format($plan->min_amount,2)}}@if($plan->max_amount) | Max: {{number_format($plan->max_amount,2)}}@endif</small>
                </div>
                <div class="col-md-4">
                    <label>Duration (months) <span class="text-danger">*</span></label>
                    <input type="number" min="{{$plan->min_duration_months}}"
                           @if($plan->max_duration_months) max="{{$plan->max_duration_months}}" @endif
                           v-model="duration" @input="calculate"
                           class="form-control form-control-lg" placeholder="{{$plan->min_duration_months}}">
                    <small class="text-muted">Min: {{$plan->min_duration_months}}@if($plan->max_duration_months) | Max: {{$plan->max_duration_months}}@endif months</small>
                </div>
                <div class="col-md-4">
                    <label>Start Date</label>
                    <input type="date" v-model="startDate" @change="calculate" class="form-control form-control-lg">
                </div>
            </div>

            {{-- Result summary --}}
            <div v-if="result" class="row text-center mb-4">
                <div class="col-4">
                    <div class="p-3 rounded" style="background:#f0fdf4;border:2px solid #16a34a">
                        <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase">Principal</div>
                        <div style="font-size:1.5rem;font-weight:900;color:#1e293b">@{{ fmt(amount) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded" style="background:#fdf4ff;border:2px solid #7c3aed">
                        <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase">Total Profit</div>
                        <div style="font-size:1.5rem;font-weight:900;color:#7c3aed">@{{ fmt(result.total_profit) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 rounded" style="background:#eff6ff;border:2px solid #3b82f6">
                        <div style="font-size:.68rem;font-weight:700;color:#64748b;text-transform:uppercase">Maturity Value</div>
                        <div style="font-size:1.5rem;font-weight:900;color:#1d4ed8">@{{ fmt(result.final_balance) }}</div>
                    </div>
                </div>
            </div>
            <div v-if="result" class="text-center mb-3">
                <small class="text-muted">
                    Effective rate: <strong>@{{ result.rate }}%</strong> &nbsp;|&nbsp;
                    Method: <strong>@{{ result.method }}</strong>
                </small>
            </div>

            {{-- Schedule table --}}
            <div v-if="result && result.schedule.length" class="table-responsive" style="max-height:300px;overflow-y:auto">
                <table class="table table-sm table-bordered mb-0">
                    <thead style="position:sticky;top:0;background:#fff;z-index:1">
                        <tr>
                            <th>#</th><th>Date</th><th class="text-right">Days</th>
                            <th class="text-right">Profit</th><th class="text-right">Running Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="r in result.schedule" :key="r.period">
                            <td>@{{r.period}}</td>
                            <td>@{{r.date}}</td>
                            <td class="text-right">@{{r.days}}</td>
                            <td class="text-right text-success font-weight-bold">@{{fmt(r.profit)}}</td>
                            <td class="text-right">@{{fmt(r.balance)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="loading" class="text-center py-3 text-muted">
                <i class="fas fa-spinner fa-spin mr-1"></i> Calculating...
            </div>
            <div v-if="!result && !loading" class="text-center py-3 text-muted">
                <i class="fas fa-calculator fa-2x mb-2 d-block"></i>
                Enter an amount and duration to see your projected returns.
            </div>
        </div>
    </div>
</div>

</div>
</section>
@endsection
@section('scripts')
<script>
var planId    = {{$plan->id}};
var minAmount = {{$plan->min_duration_months}};
var csrf      = '{{csrf_token()}}';
var calcTimer = null;

new Vue({
    el: '#planCalcApp',
    data: {
        amount:    '',
        duration:  '{{$plan->min_duration_months}}',
        startDate: '{{date("Y-m-d")}}',
        result:    null,
        loading:   false,
    },
    methods: {
        calculate() {
            if (!this.amount || !this.duration || parseFloat(this.amount) < {{$plan->min_amount}}) return;
            clearTimeout(calcTimer);
            var self = this;
            calcTimer = setTimeout(function() {
                self.loading = true;
                fetch('{{url("investment/calculate")}}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
                    body: JSON.stringify({
                        plan_id: planId, principal: self.amount,
                        duration_months: self.duration, start_date: self.startDate
                    })
                }).then(r => r.json()).then(d => {
                    self.loading = false;
                    if (d.total_profit !== undefined) self.result = d;
                }).catch(() => { self.loading = false; });
            }, 500);
        },
        fmt(n) {
            return parseFloat(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2});
        }
    }
});
</script>
@endsection

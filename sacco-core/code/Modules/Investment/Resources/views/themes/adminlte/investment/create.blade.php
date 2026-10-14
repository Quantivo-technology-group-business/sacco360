@extends('core::layouts.master')
@section('title') New Investment @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>New Member Investment</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{url('investment')}}">Investments</a></li>
            <li class="breadcrumb-item active">New</li>
        </ol></div>
    </div></div>
</section>
<section class="content" id="investApp">
@if($errors->any())
<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>
@endif
<form method="post" action="{{url('investment/store')}}">
@csrf
<div class="row">
<div class="col-md-7">
<div class="card">
    <div class="card-header"><h5 class="mb-0">Investment Details</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Member / Client <span class="text-danger">*</span></label>
                    <select name="client_id" class="form-control select2" required>
                        <option value="">— Search member —</option>
                        @foreach($clients as $c)
                        <option value="{{$c->id}}" @if(old('client_id')==$c->id) selected @endif>
                            {{$c->first_name}} {{$c->last_name}} @if($c->mobile)— {{$c->mobile}}@endif
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Investment Plan <span class="text-danger">*</span></label>
                    <select name="investment_plan_id" class="form-control" v-model="planId" @change="onPlanChange" required>
                        <option value="">— Select plan —</option>
                        @foreach($plans as $p)
                        <option value="{{$p->id}}"
                            data-rate="{{$p->return_rate}}"
                            data-rate-type="{{$p->return_rate_type}}"
                            data-method="{{$p->interest_method}}"
                            data-min-amount="{{$p->min_amount}}"
                            data-max-amount="{{$p->max_amount}}"
                            data-min-dur="{{$p->min_duration_months}}"
                            data-max-dur="{{$p->max_duration_months}}"
                            data-unit-price="{{$p->unit_price}}"
                            data-posting="{{$p->profit_posting_frequency}}"
                            @if(old('investment_plan_id')==$p->id) selected @endif>
                            {{$p->name}} ({{$p->plan_type_label}} — {{$p->return_rate_type==='tiered'?'Tiered':$p->return_rate.'% p.a.'}})
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6" v-if="planId">
                <div class="form-group">
                    <label>Principal Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" name="principal_amount"
                           class="form-control form-control-lg" v-model="principal"
                           @input="runCalculator" required>
                    <small class="text-muted" v-if="planInfo">
                        Min: @{{ formatNum(planInfo.minAmount) }}
                        <span v-if="planInfo.maxAmount"> | Max: @{{ formatNum(planInfo.maxAmount) }}</span>
                    </small>
                </div>
            </div>
            <div class="col-md-6" v-if="planId">
                <div class="form-group">
                    <label>Duration (months) <span class="text-danger">*</span></label>
                    <input type="number" min="1" name="duration_months"
                           class="form-control form-control-lg" v-model="duration"
                           @input="runCalculator" required>
                    <small class="text-muted" v-if="planInfo">
                        Min: @{{ planInfo.minDur }} months
                        <span v-if="planInfo.maxDur"> | Max: @{{ planInfo.maxDur }} months</span>
                    </small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Investment Date <span class="text-danger">*</span></label>
                    <flat-pickr v-model="startDate" class="form-control" name="start_date"
                                @change="runCalculator" required></flat-pickr>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control">
                        <option value="">— Select branch —</option>
                        @foreach($branches as $b)
                        <option value="{{$b->id}}" @if(old('branch_id')==$b->id) selected @endif>{{$b->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Currency</label>
                    <select name="currency_id" class="form-control">
                        @foreach($currencies as $c)
                        <option value="{{$c->id}}" @if(old('currency_id')==$c->id) selected @endif>{{$c->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{old('notes')}}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary btn-lg float-right">
            <i class="fas fa-save mr-1"></i> Create Investment
        </button>
        <a href="{{url('investment')}}" class="btn btn-secondary">Cancel</a>
    </div>
</div>
</div>

{{-- Live calculator panel --}}
<div class="col-md-5">
<div class="card card-success card-outline">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-calculator mr-1"></i>Projected Returns Calculator</h5></div>
    <div class="card-body">
        <div v-if="!planId" class="text-center text-muted py-4">
            <i class="fas fa-chart-line fa-3x mb-2"></i><br>Select a plan and enter amount to see projections.
        </div>
        <div v-if="planId && !calcResult && !calcLoading" class="text-muted small">
            Enter amount and duration to calculate.
        </div>
        <div v-if="calcLoading" class="text-center py-3">
            <i class="fas fa-spinner fa-spin fa-2x"></i><br>Calculating...
        </div>
        <div v-if="calcResult">
            <div class="row text-center mb-3">
                <div class="col-4">
                    <div class="bg-light rounded p-2">
                        <div class="text-muted small">Principal</div>
                        <div class="font-weight-bold">@{{ formatNum(principal) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-success text-white rounded p-2">
                        <div class="small">Total Profit</div>
                        <div class="font-weight-bold">@{{ formatNum(calcResult.total_profit) }}</div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="bg-primary text-white rounded p-2">
                        <div class="small">Maturity Value</div>
                        <div class="font-weight-bold">@{{ formatNum(calcResult.final_balance) }}</div>
                    </div>
                </div>
            </div>
            <p class="text-muted small mb-2">
                Rate: <strong>@{{ calcResult.rate }}% p.a.</strong> &nbsp;|&nbsp;
                Method: <strong>@{{ calcResult.method }}</strong>
            </p>
            <div class="table-responsive" style="max-height:280px;overflow-y:auto">
                <table class="table table-sm table-bordered mb-0">
                    <thead style="position:sticky;top:0;background:#fff">
                        <tr><th>#</th><th>Date</th><th class="text-right">Profit</th><th class="text-right">Balance</th></tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in calcResult.schedule" :key="row.period">
                            <td>@{{row.period}}</td>
                            <td>@{{row.date}}</td>
                            <td class="text-right text-success">@{{formatNum(row.profit)}}</td>
                            <td class="text-right">@{{formatNum(row.balance)}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
</div>
</form>
</section>
@endsection
@section('scripts')
<script>
var csrfToken = '{{csrf_token()}}';
var calcTimer = null;
var app = new Vue({
    el: '#investApp',
    data: {
        planId:     '{{old('investment_plan_id','')}}',
        principal:  '{{old('principal_amount','')}}',
        duration:   '{{old('duration_months','12')}}',
        startDate:  '{{old('start_date',date('Y-m-d'))}}',
        planInfo:   null,
        calcResult: null,
        calcLoading:false,
    },
    methods: {
        onPlanChange() {
            var sel = document.querySelector('select[name="investment_plan_id"]');
            var opt = sel ? sel.options[sel.selectedIndex] : null;
            if (!opt || !this.planId) { this.planInfo = null; this.calcResult = null; return; }
            this.planInfo = {
                minAmount: parseFloat(opt.dataset.minAmount) || 0,
                maxAmount: parseFloat(opt.dataset.maxAmount) || 0,
                minDur:    parseInt(opt.dataset.minDur) || 1,
                maxDur:    parseInt(opt.dataset.maxDur) || 0,
                unitPrice: parseFloat(opt.dataset.unitPrice) || 0,
            };
            if (!this.duration) this.duration = this.planInfo.minDur;
            this.runCalculator();
        },
        runCalculator() {
            if (!this.planId || !this.principal || !this.duration || !this.startDate) return;
            clearTimeout(calcTimer);
            var self = this;
            calcTimer = setTimeout(function() {
                self.calcLoading = true;
                fetch('{{url("investment/calculate")}}', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrfToken,'Accept':'application/json'},
                    body: JSON.stringify({plan_id:self.planId,principal:self.principal,duration_months:self.duration,start_date:self.startDate})
                }).then(r=>r.json()).then(d=>{
                    self.calcLoading = false;
                    if (d.total_profit !== undefined) self.calcResult = d;
                }).catch(()=>{ self.calcLoading = false; });
            }, 600);
        },
        formatNum(n) { return parseFloat(n||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2}); },
    },
    mounted() {
        if (this.planId) this.onPlanChange();
    }
});
</script>
@endsection

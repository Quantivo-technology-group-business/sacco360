<div class="grid-stack-item" gs-w="{{$config['width']??12}}" gs-h="{{$config['height']??4}}"
     gs-x="{{$config['x']??0}}" gs-y="{{$config['y']??0}}" gs-id="{{$config['id']??''}}">
    <div class="grid-stack-item-content">
        <div class="card h-100 mb-0">
            <div class="card-header">
                <span class="card-title">
                    <i class="fas fa-star mr-1" style="color:#d97706"></i> Available Investment Plans
                </span>
                <div class="card-tools">
                    @can('investment.plans.create')
                    <a href="{{url('investment/plan/create')}}" class="btn btn-xs btn-primary">+ Add Plan</a>
                    @endcan
                    <a href="{{url('investment/plan')}}" class="btn btn-xs btn-outline-secondary ml-1">Manage Plans</a>
                    @can('dashboard.destroy')
                    <a class="btn btn-xs btn-outline-danger ml-1 confirm"
                       href="{{url('dashboard/'.($config['id']??'').'/destroy')}}">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body overflow-auto p-2">
                @if($plans->isEmpty())
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-chart-line fa-2x mb-2 d-block opacity-50"></i>
                        No investment plans configured yet.
                        @can('investment.plans.create')
                        <br><a href="{{url('investment/plan/create')}}" class="btn btn-sm btn-primary mt-2">Create First Plan</a>
                        @endcan
                    </div>
                @else
                <div class="row">
                    @foreach($plans as $plan)
                    @php
                        $colours = ['fixed_deposit'=>'primary','bond'=>'info','unit_trust'=>'success',
                                    'equity'=>'warning','real_estate'=>'danger','money_market'=>'teal','other'=>'secondary'];
                        $colour  = $colours[$plan->plan_type] ?? 'secondary';
                        $icons   = ['fixed_deposit'=>'fa-piggy-bank','bond'=>'fa-file-contract',
                                    'unit_trust'=>'fa-layer-group','equity'=>'fa-chart-bar',
                                    'real_estate'=>'fa-building','money_market'=>'fa-money-bill-wave','other'=>'fa-star'];
                        $icon    = $icons[$plan->plan_type] ?? 'fa-star';
                    @endphp
                    <div class="col-md-4 col-sm-6 mb-3">
                        <div class="card card-{{$colour}} card-outline mb-0 h-100 investment-plan-card"
                             style="border-radius:12px;transition:all .2s;cursor:default">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <div style="width:40px;height:40px;border-radius:10px;background:var(--{{$colour}},#6c757d);
                                                display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1rem;flex-shrink:0">
                                        <i class="fas {{$icon}}"></i>
                                    </div>
                                    <span class="badge badge-{{$colour}} ml-2">{{$plan->plan_type_label}}</span>
                                </div>
                                <h6 class="font-weight-bold mb-1" style="font-size:.85rem">
                                    <a href="{{url('investment/plan/'.$plan->id.'/show')}}" class="text-dark">{{$plan->name}}</a>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-muted" style="font-size:.7rem">Annual Return</span>
                                    <strong class="text-{{$colour}}" style="font-size:1.1rem">
                                        @if($plan->return_rate_type==='tiered')
                                            Tiered
                                        @else
                                            {{$plan->return_rate}}%
                                        @endif
                                    </strong>
                                </div>
                                <div style="font-size:.7rem;color:#64748b" class="mb-2">
                                    <i class="fas fa-coins mr-1"></i> Min: {{number_format($plan->min_amount,2)}}
                                    @if($plan->max_amount) &nbsp;|&nbsp; Max: {{number_format($plan->max_amount,2)}} @endif
                                    <br>
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    {{$plan->min_duration_months}}@if($plan->max_duration_months)–{{$plan->max_duration_months}}@endif months
                                    &nbsp;|&nbsp;
                                    <i class="fas fa-sync-alt mr-1"></i>
                                    {{ucfirst(str_replace('_',' ',$plan->interest_method))}}
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <i class="fas fa-users mr-1"></i>{{$plan->investments_count}} active
                                    </small>
                                    @can('investment.investments.create')
                                    <a href="{{url('investment/create?plan_id='.$plan->id)}}"
                                       class="btn btn-xs btn-{{$colour}}">
                                        <i class="fas fa-plus mr-1"></i>Invest
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<style>
.investment-plan-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(0,0,0,.12) !important;
}
</style>

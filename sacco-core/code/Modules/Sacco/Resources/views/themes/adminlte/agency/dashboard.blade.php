@extends('core::layouts.master')
@section('title') Agency Banking Dashboard @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-chart-line mr-2"></i>Agency Banking Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    {{-- Overview Stats --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-info">
                <div class="inner">
                    <h3>{{$stats['total_agents']}}</h3>
                    <p>Total Agents</p>
                </div>
                <div class="icon"><i class="fas fa-users"></i></div>
                <a href="{{url('sacco/agency')}}" class="small-box-footer">
                    View all <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-success">
                <div class="inner">
                    <h3>{{$stats['active_agents']}}</h3>
                    <p>Active Agents</p>
                </div>
                <div class="icon"><i class="fas fa-user-check"></i></div>
                <a href="{{url('sacco/agency?status=active')}}" class="small-box-footer">
                    View active <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-warning">
                <div class="inner">
                    <h3>{{$stats['suspended_agents']}}</h3>
                    <p>Suspended Agents</p>
                </div>
                <div class="icon"><i class="fas fa-user-slash"></i></div>
                <a href="{{url('sacco/agency?status=suspended')}}" class="small-box-footer">
                    View suspended <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-gradient-primary">
                <div class="inner">
                    <h3>{{number_format($stats['total_float'], 0)}}</h3>
                    <p>Total Float (KES)</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
                <span class="small-box-footer">&nbsp;</span>
            </div>
        </div>
    </div>

    {{-- Today's Performance --}}
    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-day mr-2"></i>Today's Performance</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 text-center">
                            <div class="border-right">
                                <h4 class="text-info">{{$stats['today_transactions']}}</h4>
                                <p class="text-muted mb-0">Transactions</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center">
                            <div class="border-right">
                                <h4 class="text-success">{{number_format($stats['today_deposits'], 0)}}</h4>
                                <p class="text-muted mb-0">Deposits</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center">
                            <div class="border-right">
                                <h4 class="text-warning">{{number_format($stats['today_withdrawals'], 0)}}</h4>
                                <p class="text-muted mb-0">Withdrawals</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 text-center">
                            <h4 class="text-primary">{{number_format($stats['today_commissions'], 0)}}</h4>
                            <p class="text-muted mb-0">Commissions</p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6 text-center">
                            <h5>Total Volume Today</h5>
                            <h2 class="text-primary">KES {{number_format($stats['today_volume'], 2)}}</h2>
                        </div>
                        <div class="col-md-6 text-center">
                            <h5>Monthly Volume</h5>
                            <h2 class="text-success">KES {{number_format($stats['month_volume'], 2)}}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-danger card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-exclamation-triangle mr-2"></i>Low Float Alerts</h3>
                    <div class="card-tools">
                        <span class="badge badge-danger">{{count($lowFloatAgents)}}</span>
                    </div>
                </div>
                <div class="card-body p-0" style="max-height: 200px; overflow-y: auto;">
                    @if(count($lowFloatAgents) > 0)
                    <ul class="list-group list-group-flush">
                        @foreach($lowFloatAgents as $agent)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                            <div>
                                <a href="{{url('sacco/agency/'.$agent->id.'/show')}}">
                                    <strong>{{$agent->agent_code}}</strong>
                                </a>
                                <br><small class="text-muted">{{$agent->agent_name}}</small>
                            </div>
                            <span class="badge badge-danger badge-pill">
                                {{number_format($agent->float_balance, 0)}}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                    @else
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                        <p class="mb-0">All agents have sufficient float</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Top Performing Agents --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-gradient-success text-white">
                    <h3 class="card-title"><i class="fas fa-trophy mr-2"></i>Top Performing Agents (This Month)</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Agent</th>
                                <th class="text-center">Transactions</th>
                                <th class="text-right">Float</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topAgents as $index => $agent)
                            <tr>
                                <td>
                                    @if($index === 0)
                                    <i class="fas fa-medal text-warning"></i>
                                    @elseif($index === 1)
                                    <i class="fas fa-medal text-secondary"></i>
                                    @elseif($index === 2)
                                    <i class="fas fa-medal" style="color: #cd7f32;"></i>
                                    @else
                                    {{$index + 1}}
                                    @endif
                                </td>
                                <td>
                                    <a href="{{url('sacco/agency/'.$agent->id.'/show')}}">
                                        {{$agent->agent_name}}
                                    </a>
                                    <br><small class="text-muted">{{$agent->agent_code}}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{$agent->transaction_count}}</span>
                                </td>
                                <td class="text-right">{{number_format($agent->float_balance, 0)}}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No data available</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-gradient-info text-white">
                    <h3 class="card-title"><i class="fas fa-clock mr-2"></i>Recent Transactions</h3>
                    <div class="card-tools">
                        <a href="{{url('sacco/agency/transactions')}}" class="btn btn-tool text-white">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Reference</th>
                                <th>Agent</th>
                                <th>Type</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $txn)
                            <tr>
                                <td>
                                    <code class="small">{{$txn->reference}}</code>
                                    <br><small class="text-muted">{{$txn->created_at->diffForHumans()}}</small>
                                </td>
                                <td>
                                    @if($txn->agent)
                                    {{$txn->agent->agent_code}}
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>{!! $txn->type_badge !!}</td>
                                <td class="text-right">{{number_format($txn->amount, 0)}}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No transactions yet</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Summary --}}
    <div class="row">
        <div class="col-12">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-bar mr-2"></i>This Month's Summary</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-info"><i class="fas fa-list"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Transactions</span>
                                    <span class="info-box-number">{{$stats['month_transactions']}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-success"><i class="fas fa-money-bill-wave"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Volume</span>
                                    <span class="info-box-number">{{number_format($stats['month_volume'], 0)}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-warning"><i class="fas fa-percentage"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Total Commissions</span>
                                    <span class="info-box-number">{{number_format($stats['month_commissions'], 0)}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="info-box bg-light">
                                <span class="info-box-icon bg-primary"><i class="fas fa-calculator"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Avg per Transaction</span>
                                    <span class="info-box-number">
                                        @if($stats['month_transactions'] > 0)
                                        {{number_format($stats['month_volume'] / $stats['month_transactions'], 0)}}
                                        @else
                                        0
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{url('sacco/agency/create')}}" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-user-plus mr-2"></i>Register New Agent
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{url('sacco/agency')}}" class="btn btn-info btn-lg btn-block">
                                <i class="fas fa-exchange-alt mr-2"></i>Process Transaction
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{url('sacco/agency/transactions')}}" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-list mr-2"></i>View All Transactions
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{url('sacco/agency/transactions/export')}}" class="btn btn-secondary btn-lg btn-block">
                                <i class="fas fa-download mr-2"></i>Export Report
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

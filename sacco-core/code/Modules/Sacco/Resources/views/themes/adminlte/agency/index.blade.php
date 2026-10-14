@extends('core::layouts.master')
@section('title') Agency Banking @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-store-alt mr-2"></i>Agency Banking</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{url('sacco/agency/dashboard')}}" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-chart-line"></i> Dashboard
                </a>
                <a href="{{url('sacco/agency/create')}}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Register Agent
                </a>
                <a href="{{url('sacco/agency/transactions')}}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-list"></i> Transactions
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>{{session('success')}}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{session('error')}}
        </div>
    @endif

    {{-- Dashboard Stats Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{$stats['active_agents'] ?? 0}}</h3>
                    <p>Active Agents</p>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{number_format($stats['total_float'] ?? 0, 0)}}</h3>
                    <p>Total Float Balance</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{$stats['today_transactions'] ?? 0}}</h3>
                    <p>Today's Transactions</p>
                </div>
                <div class="icon"><i class="fas fa-exchange-alt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{$stats['low_float_agents'] ?? 0}}</h3>
                    <p>Low Float Alerts</p>
                </div>
                <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
        </div>
    </div>

    {{-- Quick Transaction Panel --}}
    <div class="card card-primary card-outline mb-4">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-bolt mr-2"></i>Quick Transactions</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <ul class="nav nav-tabs" id="transactionTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="deposit-tab" data-toggle="tab" href="#depositPanel" role="tab">
                        <i class="fas fa-arrow-down text-success mr-1"></i> Deposit
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="withdraw-tab" data-toggle="tab" href="#withdrawPanel" role="tab">
                        <i class="fas fa-arrow-up text-warning mr-1"></i> Withdrawal
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="float-tab" data-toggle="tab" href="#floatPanel" role="tab">
                        <i class="fas fa-plus text-info mr-1"></i> Float Management
                    </a>
                </li>
            </ul>
            <div class="tab-content pt-3" id="transactionTabsContent">
                {{-- Deposit Tab --}}
                <div class="tab-pane fade show active" id="depositPanel" role="tabpanel">
                    <form method="post" action="{{url('sacco/agency/deposit')}}" class="row align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label>Agent</label>
                            <select name="agent_id" class="form-control select2 agent-select" required>
                                <option value="">Select Agent</option>
                                @foreach($agents as $a)
                                    @if($a->status === 'active')
                                    <option value="{{$a->id}}" data-float="{{$a->float_balance}}">
                                        {{$a->agent_code}} - {{$a->agent_name}}
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Savings Account ID</label>
                            <input name="savings_id" type="number" class="form-control" placeholder="Enter Account ID" required>
                        </div>
                        <div class="col-md-2">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                <input name="amount" type="number" step="0.01" min="1" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Notes (Optional)</label>
                            <input name="notes" class="form-control" placeholder="Transaction notes">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-success btn-block">
                                <i class="fas fa-arrow-down mr-1"></i> Post Deposit
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Withdrawal Tab --}}
                <div class="tab-pane fade" id="withdrawPanel" role="tabpanel">
                    <form method="post" action="{{url('sacco/agency/withdraw')}}" class="row align-items-end">
                        @csrf
                        <div class="col-md-3">
                            <label>Agent</label>
                            <select name="agent_id" class="form-control select2 agent-select" required>
                                <option value="">Select Agent</option>
                                @foreach($agents as $a)
                                    @if($a->status === 'active')
                                    <option value="{{$a->id}}" data-float="{{$a->float_balance}}">
                                        {{$a->agent_code}} - {{$a->agent_name}} (Float: {{number_format($a->float_balance, 2)}})
                                    </option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Savings Account ID</label>
                            <input name="savings_id" type="number" class="form-control" placeholder="Enter Account ID" required>
                        </div>
                        <div class="col-md-2">
                            <label>Amount</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                <input name="amount" type="number" step="0.01" min="1" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label>Notes (Optional)</label>
                            <input name="notes" class="form-control" placeholder="Transaction notes">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-warning btn-block">
                                <i class="fas fa-arrow-up mr-1"></i> Post Withdrawal
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Float Management Tab --}}
                <div class="tab-pane fade" id="floatPanel" role="tabpanel">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card card-outline card-info mb-0">
                                <div class="card-header py-2">
                                    <h6 class="mb-0"><i class="fas fa-plus-circle mr-1"></i> Top-up Float</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="{{url('sacco/agency/topup')}}" class="row align-items-end">
                                        @csrf
                                        <div class="col-md-5">
                                            <label>Agent</label>
                                            <select name="agent_id" class="form-control select2" required>
                                                <option value="">Select Agent</option>
                                                @foreach($agents as $a)
                                                <option value="{{$a->id}}">{{$a->agent_code}} - {{$a->agent_name}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Amount</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                                <input name="amount" type="number" step="0.01" min="1" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-info btn-block">
                                                <i class="fas fa-plus"></i> Top Up
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-secondary mb-0">
                                <div class="card-header py-2">
                                    <h6 class="mb-0"><i class="fas fa-minus-circle mr-1"></i> Withdraw Float</h6>
                                </div>
                                <div class="card-body">
                                    <form method="post" action="{{url('sacco/agency/withdraw-float')}}" class="row align-items-end">
                                        @csrf
                                        <div class="col-md-5">
                                            <label>Agent</label>
                                            <select name="agent_id" class="form-control select2" required>
                                                <option value="">Select Agent</option>
                                                @foreach($agents as $a)
                                                <option value="{{$a->id}}">{{$a->agent_code}} ({{number_format($a->float_balance, 2)}})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label>Amount</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend"><span class="input-group-text">KES</span></div>
                                                <input name="amount" type="number" step="0.01" min="1" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <button type="submit" class="btn btn-secondary btn-block">
                                                <i class="fas fa-minus"></i> Withdraw
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Agents List --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users mr-2"></i>Registered Agents</h3>
            <div class="card-tools">
                <form method="get" action="{{url('sacco/agency')}}" class="form-inline">
                    <div class="input-group input-group-sm mr-2" style="width: 150px;">
                        <select name="status" class="form-control">
                            <option value="">All Status</option>
                            <option value="active" {{request('status') == 'active' ? 'selected' : ''}}>Active</option>
                            <option value="suspended" {{request('status') == 'suspended' ? 'selected' : ''}}>Suspended</option>
                            <option value="closed" {{request('status') == 'closed' ? 'selected' : ''}}>Closed</option>
                        </select>
                    </div>
                    <div class="input-group input-group-sm mr-2" style="width: 150px;">
                        <select name="branch_id" class="form-control">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                            <option value="{{$branch->id}}" {{request('branch_id') == $branch->id ? 'selected' : ''}}>{{$branch->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="input-group input-group-sm" style="width: 200px;">
                        <input type="text" name="search" class="form-control" placeholder="Search..." value="{{request('search')}}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Code / Agent #</th>
                        <th>Agent Name</th>
                        <th>Phone</th>
                        <th>Location</th>
                        <th>Branch</th>
                        <th class="text-right">Float Balance</th>
                        <th class="text-center">Commission</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agents as $agent)
                    <tr>
                        <td>
                            <a href="{{url('sacco/agency/'.$agent->id.'/show')}}" class="font-weight-bold">
                                {{$agent->agent_code}}
                            </a>
                            @if($agent->agent_number)
                            <br><span class="badge badge-primary" title="Customer Withdrawal Code">
                                <i class="fas fa-hashtag"></i>{{$agent->agent_number}}
                            </span>
                            @endif
                        </td>
                        <td>
                            <div>{{$agent->agent_name}}</div>
                            @if($agent->email)
                            <small class="text-muted">{{$agent->email}}</small>
                            @endif
                        </td>
                        <td>{{$agent->phone ?? '-'}}</td>
                        <td>{{$agent->location ?? '-'}}</td>
                        <td>{{$agent->branch?->name ?? '-'}}</td>
                        <td class="text-right">
                            <span class="{{$agent->float_balance < 5000 ? 'text-danger font-weight-bold' : ''}}">
                                {{number_format($agent->float_balance, 2)}}
                            </span>
                            @if($agent->float_limit)
                            <div class="progress progress-xs mt-1" style="height: 4px;">
                                <div class="progress-bar {{$agent->float_utilization > 80 ? 'bg-danger' : ($agent->float_utilization > 50 ? 'bg-warning' : 'bg-success')}}"
                                     style="width: {{min($agent->float_utilization, 100)}}%"></div>
                            </div>
                            @endif
                        </td>
                        <td class="text-center">{{number_format($agent->commission_rate, 2)}}%</td>
                        <td class="text-center">
                            {!! $agent->status_badge !!}
                            <br>{!! $agent->kyc_status_badge !!}
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{url('sacco/agency/'.$agent->id.'/show')}}" class="btn btn-info" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{url('sacco/agency/'.$agent->id.'/edit')}}" class="btn btn-warning" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if($agent->status === 'active')
                                <button type="button" class="btn btn-danger" title="Suspend"
                                        data-toggle="modal" data-target="#suspendModal{{$agent->id}}">
                                    <i class="fas fa-ban"></i>
                                </button>
                                @elseif($agent->status === 'suspended')
                                <a href="{{url('sacco/agency/'.$agent->id.'/reactivate')}}" class="btn btn-success" title="Reactivate">
                                    <i class="fas fa-check"></i>
                                </a>
                                @endif
                            </div>

                            {{-- Suspend Modal --}}
                            <div class="modal fade" id="suspendModal{{$agent->id}}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="{{url('sacco/agency/'.$agent->id.'/suspend')}}">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Suspend Agent</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Are you sure you want to suspend <strong>{{$agent->agent_name}}</strong>?</p>
                                                <div class="form-group">
                                                    <label>Reason for Suspension</label>
                                                    <textarea name="reason" class="form-control" rows="3" required
                                                              placeholder="Enter the reason for suspending this agent..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Suspend Agent</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No agents found</p>
                            <a href="{{url('sacco/agency/create')}}" class="btn btn-primary btn-sm mt-2">
                                <i class="fas fa-plus mr-1"></i> Register First Agent
                            </a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($agents->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                Showing {{$agents->firstItem()}} to {{$agents->lastItem()}} of {{$agents->total()}} agents
            </div>
            <div class="float-right">
                {{$agents->withQueryString()->links()}}
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>
@endpush

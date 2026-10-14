@extends('core::layouts.master')
@section('title') Agent - {{$agent->agent_name}} @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-tie mr-2"></i>{{$agent->agent_name}}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item active">{{$agent->agent_code}}</li>
                </ol>
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

    <div class="row">
        {{-- Agent Profile Card --}}
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 80px; height: 80px;">
                            <i class="fas fa-user-tie fa-2x text-white"></i>
                        </div>
                        <h3 class="profile-username">{{$agent->agent_name}}</h3>
                        <p class="text-muted mb-1">{{$agent->agent_code}}</p>
                        @if($agent->agent_number)
                        <div class="bg-light rounded p-2 mb-2">
                            <small class="text-muted d-block">Customer Withdrawal Code</small>
                            <h4 class="text-primary mb-0"><i class="fas fa-hashtag"></i>{{$agent->agent_number}}</h4>
                        </div>
                        @endif
                        <div class="mb-2">
                            {!! $agent->status_badge !!}
                            {!! $agent->kyc_status_badge !!}
                        </div>
                    </div>

                    <ul class="list-group list-group-unbordered mt-3">
                        <li class="list-group-item">
                            <b><i class="fas fa-phone mr-2 text-muted"></i>Phone</b>
                            <span class="float-right">{{$agent->phone ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-envelope mr-2 text-muted"></i>Email</b>
                            <span class="float-right">{{$agent->email ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-id-card mr-2 text-muted"></i>ID Number</b>
                            <span class="float-right">{{$agent->id_number ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-map-marker-alt mr-2 text-muted"></i>Location</b>
                            <span class="float-right">{{$agent->location ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-building mr-2 text-muted"></i>Branch</b>
                            <span class="float-right">{{$agent->branch?->name ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-calendar mr-2 text-muted"></i>Activated</b>
                            <span class="float-right">{{$agent->activated_at?->format('d M Y') ?? '-'}}</span>
                        </li>
                        <li class="list-group-item">
                            <b><i class="fas fa-clock mr-2 text-muted"></i>Last Transaction</b>
                            <span class="float-right">{{$agent->last_transaction_at?->diffForHumans() ?? 'Never'}}</span>
                        </li>
                    </ul>

                    <div class="mt-3">
                        <a href="{{url('sacco/agency/'.$agent->id.'/edit')}}" class="btn btn-primary btn-block">
                            <i class="fas fa-edit mr-2"></i>Edit Profile
                        </a>
                        @if($agent->status === 'active')
                        <button type="button" class="btn btn-danger btn-block" data-toggle="modal" data-target="#suspendModal">
                            <i class="fas fa-ban mr-2"></i>Suspend Agent
                        </button>
                        @elseif($agent->status === 'suspended')
                        <a href="{{url('sacco/agency/'.$agent->id.'/reactivate')}}" class="btn btn-success btn-block">
                            <i class="fas fa-check mr-2"></i>Reactivate Agent
                        </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Float Card --}}
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-wallet mr-2"></i>Float Balance</h3>
                </div>
                <div class="card-body">
                    <h2 class="text-center mb-3">
                        <span class="{{$agent->float_balance < 5000 ? 'text-danger' : 'text-success'}}">
                            KES {{number_format($agent->float_balance, 2)}}
                        </span>
                    </h2>
                    @if($agent->float_limit)
                    <div class="progress mb-2" style="height: 20px;">
                        <div class="progress-bar {{$agent->float_utilization > 80 ? 'bg-danger' : ($agent->float_utilization > 50 ? 'bg-warning' : 'bg-success')}}"
                             style="width: {{min($agent->float_utilization, 100)}}%">
                            {{$agent->float_utilization}}%
                        </div>
                    </div>
                    <p class="text-center text-muted mb-0">
                        Limit: KES {{number_format($agent->float_limit, 2)}}
                    </p>
                    @endif
                </div>
            </div>

            {{-- Commission Card --}}
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-percentage mr-2"></i>Commission</h3>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-right">
                            <h4>{{number_format($agent->commission_rate, 2)}}%</h4>
                            <p class="text-muted mb-0">Rate</p>
                        </div>
                        <div class="col-6">
                            <h4>KES {{number_format($performance['commissions_earned'] ?? 0, 2)}}</h4>
                            <p class="text-muted mb-0">This Month</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Portal Access --}}
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Portal Access</h3>
                </div>
                <div class="card-body">
                    @if($agent->user)
                        <div class="d-flex align-items-center">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center mr-3"
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-check text-white"></i>
                            </div>
                            <div>
                                <strong>Login Enabled</strong><br>
                                <span class="text-muted">{{$agent->user->email}}</span>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center">
                            <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center mr-3"
                                 style="width: 40px; height: 40px;">
                                <i class="fas fa-exclamation text-white"></i>
                            </div>
                            <div>
                                <strong>No Login Account</strong><br>
                                <span class="text-muted">Agent cannot access the portal</span>
                            </div>
                        </div>
                        <a href="{{url('sacco/agency/'.$agent->id.'/edit')}}" class="btn btn-sm btn-info mt-2">
                            <i class="fas fa-plus mr-1"></i>Create Login Account
                        </a>
                    @endif
                </div>
            </div>

            {{-- Accounting Integration --}}
            <div class="card card-dark card-outline">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-book mr-2"></i>GL Accounts</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="font-weight-bold" width="40%">Float Account</td>
                            <td>
                                @if($agent->floatAccount)
                                    <span class="badge badge-primary">{{$agent->floatAccount->gl_code}}</span>
                                    {{$agent->floatAccount->name}}
                                @else
                                    <span class="text-muted">Not linked</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="font-weight-bold">Commission Account</td>
                            <td>
                                @if($agent->commissionAccount)
                                    <span class="badge badge-warning">{{$agent->commissionAccount->gl_code}}</span>
                                    {{$agent->commissionAccount->name}}
                                @else
                                    <span class="text-muted">Not linked</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        {{-- Performance & Transactions --}}
        <div class="col-md-8">
            {{-- Performance Stats --}}
            <div class="row">
                <div class="col-md-3 col-6">
                    <div class="info-box bg-info">
                        <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Transactions</span>
                            <span class="info-box-number">{{$performance['total_transactions'] ?? 0}}</span>
                            <span class="info-box-text small">This Month</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box bg-success">
                        <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Deposits</span>
                            <span class="info-box-number">{{number_format($performance['deposits_amount'] ?? 0, 0)}}</span>
                            <span class="info-box-text small">{{$performance['deposits_count'] ?? 0}} txns</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box bg-warning">
                        <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Withdrawals</span>
                            <span class="info-box-number">{{number_format($performance['withdrawals_amount'] ?? 0, 0)}}</span>
                            <span class="info-box-text small">{{$performance['withdrawals_count'] ?? 0}} txns</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="info-box bg-primary">
                        <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Volume</span>
                            <span class="info-box-number">{{number_format($performance['total_volume'] ?? 0, 0)}}</span>
                            <span class="info-box-text small">This Month</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Recent Transactions --}}
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list mr-2"></i>Recent Transactions</h3>
                    <div class="card-tools">
                        <a href="{{url('sacco/agency/transactions?agent_id='.$agent->id)}}" class="btn btn-tool">
                            View All <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>Reference</th>
                                <th>Type</th>
                                <th>Client</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Commission</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $txn)
                            <tr>
                                <td><code>{{$txn->reference}}</code></td>
                                <td>{!! $txn->type_badge !!}</td>
                                <td>{{$txn->getClientName()}}</td>
                                <td class="text-right">{{number_format($txn->amount, 2)}}</td>
                                <td class="text-right">{{number_format($txn->commission, 2)}}</td>
                                <td>{!! $txn->status_badge !!}</td>
                                <td>
                                    <small>{{$txn->created_at->format('d M Y')}}</small><br>
                                    <small class="text-muted">{{$txn->created_at->format('H:i')}}</small>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i><br>
                                    No transactions yet
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($recentTransactions->hasPages())
                <div class="card-footer">
                    {{$recentTransactions->links()}}
                </div>
                @endif
            </div>

            {{-- Notes --}}
            @if($agent->notes)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Notes</h3>
                </div>
                <div class="card-body">
                    {{$agent->notes}}
                </div>
            </div>
            @endif

            @if($agent->address)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-map mr-2"></i>Address</h3>
                </div>
                <div class="card-body">
                    {{$agent->address}}
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Suspend Modal --}}
    <div class="modal fade" id="suspendModal" tabindex="-1">
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
                        <p class="text-warning"><i class="fas fa-exclamation-triangle mr-1"></i> The agent will not be able to process any transactions while suspended.</p>
                        <div class="form-group">
                            <label>Reason for Suspension <span class="text-danger">*</span></label>
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
</section>
@endsection

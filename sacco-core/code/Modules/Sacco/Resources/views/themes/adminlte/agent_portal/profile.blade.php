@extends('core::layouts.master')
@section('title') Agent Portal - Profile @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-cog mr-2"></i>My Profile</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Profile</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;font-size:40px;">
                            {{substr($agent->agent_name, 0, 2)}}
                        </div>
                    </div>

                    <h3 class="profile-username text-center">{{$agent->agent_name}}</h3>
                    <p class="text-muted text-center">Agent Code: <strong>{{$agent->agent_code}}</strong></p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Status</b> <span class="float-right">{!! $agent->status_badge !!}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Float Balance</b> <span class="float-right text-success">{{number_format($agent->float_balance, 2)}}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Commission Balance</b> <span class="float-right text-info">{{number_format($agent->commission_balance, 2)}}</span>
                        </li>
                        <li class="list-group-item">
                            <b>Total Earned</b> <span class="float-right">{{number_format($agent->total_commissions_earned, 2)}}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Agent Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Agent Name</label>
                                <p class="form-control-plaintext"><strong>{{$agent->agent_name}}</strong></p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Agent Code</label>
                                <p class="form-control-plaintext"><strong>{{$agent->agent_code}}</strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Phone</label>
                                <p class="form-control-plaintext">{{$agent->phone ?? 'N/A'}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Email</label>
                                <p class="form-control-plaintext">{{$agent->email ?? 'N/A'}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Location</label>
                                <p class="form-control-plaintext">{{$agent->location ?? 'N/A'}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Branch</label>
                                <p class="form-control-plaintext">{{$agent->branch->name ?? 'N/A'}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Float Limit</label>
                                <p class="form-control-plaintext">{{number_format($agent->float_limit, 2)}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Daily Transaction Limit</label>
                                <p class="form-control-plaintext">{{number_format($agent->daily_transaction_limit, 2)}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Single Transaction Limit</label>
                                <p class="form-control-plaintext">{{number_format($agent->single_transaction_limit, 2)}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Commission Rate</label>
                                <p class="form-control-plaintext">{{$agent->formatted_commission_rate ?? 'Tier-based'}}</p>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Activated At</label>
                                <p class="form-control-plaintext">{{$agent->activated_at ? $agent->activated_at->format('d M Y') : 'N/A'}}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-muted">Last Transaction</label>
                                <p class="form-control-plaintext">{{$agent->last_transaction_at ? $agent->last_transaction_at->diffForHumans() : 'Never'}}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-warning">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-key mr-2"></i>Change PIN</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{route('agent.profile.update_pin')}}">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Current PIN <span class="text-danger">*</span></label>
                                    <input type="password" name="current_pin" class="form-control" maxlength="4" pattern="\d{4}" required placeholder="****">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>New PIN <span class="text-danger">*</span></label>
                                    <input type="password" name="new_pin" class="form-control" maxlength="4" pattern="\d{4}" required placeholder="****">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Confirm New PIN <span class="text-danger">*</span></label>
                                    <input type="password" name="new_pin_confirmation" class="form-control" maxlength="4" pattern="\d{4}" required placeholder="****">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-key mr-1"></i> Update PIN
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

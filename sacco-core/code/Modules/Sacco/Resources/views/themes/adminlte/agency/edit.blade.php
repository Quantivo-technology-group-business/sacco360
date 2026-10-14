@extends('core::layouts.master')
@section('title') Edit Agent - {{$agent->agent_name}} @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-edit mr-2"></i>Edit Agent</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency/'.$agent->id.'/show')}}">{{$agent->agent_code}}</a></li>
                    <li class="breadcrumb-item active">Edit</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{session('error')}}
        </div>
    @endif

    <form method="post" action="{{url('sacco/agency/'.$agent->id.'/update')}}">
        @csrf
        <div class="row">
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>Agent Information</h3>
                        <div class="card-tools">
                            <span class="badge badge-lg {{$agent->status === 'active' ? 'badge-success' : ($agent->status === 'suspended' ? 'badge-warning' : 'badge-danger')}}">
                                {{ucfirst($agent->status)}}
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Agent Name <span class="text-danger">*</span></label>
                                    <input name="agent_name" class="form-control @error('agent_name') is-invalid @enderror"
                                           value="{{old('agent_name', $agent->agent_name)}}" required>
                                    @error('agent_name')
                                    <span class="invalid-feedback">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Agent Code</label>
                                    <input class="form-control" value="{{$agent->agent_code}}" disabled>
                                    <small class="form-text text-muted">Agent code cannot be changed</small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input name="phone" class="form-control" value="{{old('phone', $agent->phone)}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" name="email" class="form-control"
                                               value="{{old('email', $agent->email)}}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ID Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                        </div>
                                        <input name="id_number" class="form-control"
                                               value="{{old('id_number', $agent->id_number)}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Branch</label>
                                    <select name="branch_id" class="form-control select2">
                                        <option value="">-- Select Branch --</option>
                                        @foreach($branches as $branch)
                                        <option value="{{$branch->id}}" {{old('branch_id', $agent->branch_id) == $branch->id ? 'selected' : ''}}>
                                            {{$branch->name}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Location/Area</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                        </div>
                                        <input name="location" class="form-control"
                                               value="{{old('location', $agent->location)}}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Address</label>
                                    <textarea name="address" class="form-control" rows="2">{{old('address', $agent->address)}}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- User Account Settings --}}
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-shield mr-2"></i>Portal Login Account</h3>
                    </div>
                    <div class="card-body">
                        @if($agent->user)
                            <div class="alert alert-success mb-3">
                                <i class="fas fa-check-circle mr-2"></i>
                                <strong>Linked User:</strong> {{$agent->user->email}}
                            </div>
                            <div class="form-group mb-0">
                                <label>Change Linked User</label>
                                <select name="user_id" class="form-control select2">
                                    <option value="">-- Remove User Link --</option>
                                    @foreach($availableUsers as $user)
                                    <option value="{{$user->id}}" {{$agent->user_id == $user->id ? 'selected' : ''}}>
                                        {{$user->first_name}} {{$user->last_name}} ({{$user->email}})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="alert alert-warning mb-3">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                No login account linked. Agent cannot access the portal.
                            </div>
                            @if($availableUsers->count())
                            <div class="form-group">
                                <label>Link Existing User</label>
                                <select name="user_id" class="form-control select2">
                                    <option value="">-- Select User --</option>
                                    @foreach($availableUsers as $user)
                                    <option value="{{$user->id}}">
                                        {{$user->first_name}} {{$user->last_name}} ({{$user->email}})
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-center text-muted">— OR —</p>
                            @endif
                            <div class="form-group mb-0">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="create_user_account"
                                           name="create_user_account" value="1">
                                    <label class="custom-control-label" for="create_user_account">
                                        <strong>Create New Login Account</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    Creates a new user with the agent's email address
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Chart of Accounts Linkage --}}
                <div class="card card-dark card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-book mr-2"></i>Accounting Integration</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Float GL Account</label>
                            <select name="float_chart_of_account_id" class="form-control select2">
                                <option value="">-- Select Account --</option>
                                @foreach($chartOfAccounts as $account)
                                <option value="{{$account->id}}" {{old('float_chart_of_account_id', $agent->float_chart_of_account_id) == $account->id ? 'selected' : ''}}>
                                    {{$account->gl_code}} - {{$account->name}}
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Ledger account for tracking agent float balance</small>
                        </div>
                        <div class="form-group mb-0">
                            <label>Commission GL Account</label>
                            <select name="commission_chart_of_account_id" class="form-control select2">
                                <option value="">-- Select Account --</option>
                                @foreach($chartOfAccounts as $account)
                                <option value="{{$account->id}}" {{old('commission_chart_of_account_id', $agent->commission_chart_of_account_id) == $account->id ? 'selected' : ''}}>
                                    {{$account->gl_code}} - {{$account->name}}
                                </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Ledger account for tracking agent commissions</small>
                        </div>
                    </div>
                </div>

                <div class="card card-secondary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-sticky-note mr-2"></i>Additional Notes</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <textarea name="notes" class="form-control" rows="3">{{old('notes', $agent->notes)}}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-wallet mr-2"></i>Float & Limits</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Current Float Balance</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input type="text" class="form-control font-weight-bold"
                                       value="{{number_format($agent->float_balance, 2)}}" disabled>
                            </div>
                            <small class="form-text text-muted">Use float management to change balance</small>
                        </div>
                        <div class="form-group">
                            <label>Float Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="float_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('float_limit', $agent->float_limit)}}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Daily Transaction Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="daily_transaction_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('daily_transaction_limit', $agent->daily_transaction_limit)}}">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Single Transaction Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="single_transaction_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('single_transaction_limit', $agent->single_transaction_limit)}}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-percentage mr-2"></i>Commission Settings</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group mb-0">
                            <label>Commission Rate (%)</label>
                            <div class="input-group">
                                <input name="commission_rate" type="number" step="0.01" min="0" max="100"
                                       class="form-control" value="{{old('commission_rate', $agent->commission_rate)}}">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-light">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-save mr-2"></i>Save Changes
                        </button>
                        <a href="{{url('sacco/agency/'.$agent->id.'/show')}}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>

                @if($agent->status === 'suspended')
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-ban mr-2"></i>Suspension Info</h3>
                    </div>
                    <div class="card-body">
                        <p class="mb-2"><strong>Reason:</strong></p>
                        <p class="text-muted">{{$agent->suspended_reason ?? 'No reason provided'}}</p>
                        <a href="{{url('sacco/agency/'.$agent->id.'/reactivate')}}" class="btn btn-success btn-block">
                            <i class="fas fa-check mr-2"></i>Reactivate Agent
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </form>
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

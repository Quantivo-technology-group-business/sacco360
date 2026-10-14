@extends('core::layouts.master')
@section('title') Register Agent @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-plus mr-2"></i>Register New Agent</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item active">Register Agent</li>
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

    <form method="post" action="{{url('sacco/agency/store')}}">
        @csrf
        <div class="row">
            {{-- Basic Information --}}
            <div class="col-md-8">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user mr-2"></i>Agent Information</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Agent Name <span class="text-danger">*</span></label>
                                    <input name="agent_name" class="form-control @error('agent_name') is-invalid @enderror"
                                           value="{{old('agent_name')}}" required placeholder="Full name of the agent">
                                    @error('agent_name')
                                    <span class="invalid-feedback">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Agent Code</label>
                                    <input name="agent_code" class="form-control" value="{{old('agent_code')}}"
                                           placeholder="Auto-generated if left blank">
                                    <small class="form-text text-muted">Leave blank to auto-generate</small>
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
                                        <input name="phone" class="form-control @error('phone') is-invalid @enderror"
                                               value="{{old('phone')}}" placeholder="e.g., 0712345678">
                                    </div>
                                    @error('phone')
                                    <span class="text-danger small">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                               value="{{old('email')}}" placeholder="agent@example.com">
                                    </div>
                                    @error('email')
                                    <span class="text-danger small">{{$message}}</span>
                                    @enderror
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
                                        <input name="id_number" class="form-control" value="{{old('id_number')}}"
                                               placeholder="National ID or Passport">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Branch</label>
                                    <select name="branch_id" class="form-control select2">
                                        <option value="">-- Select Branch --</option>
                                        @foreach($branches as $branch)
                                        <option value="{{$branch->id}}" {{old('branch_id') == $branch->id ? 'selected' : ''}}>
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
                                        <input name="location" class="form-control" value="{{old('location')}}"
                                               placeholder="Operating area (e.g., Westlands, Nairobi)">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Full Address</label>
                                    <textarea name="address" class="form-control" rows="2"
                                              placeholder="Physical address of the agent">{{old('address')}}</textarea>
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
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="create_user_account"
                                       name="create_user_account" value="1" {{old('create_user_account') ? 'checked' : ''}}>
                                <label class="custom-control-label" for="create_user_account">
                                    <strong>Create Login Account</strong>
                                </label>
                            </div>
                            <small class="form-text text-muted">
                                Enable this to allow the agent to login to the Agent Portal. A user account will be created with the agent's email.
                            </small>
                        </div>
                        <div id="login_settings" style="{{old('create_user_account') ? '' : 'display:none;'}}">
                            <div class="form-group">
                                <label>Transaction PIN (4 digits)</label>
                                <input type="password" name="pin" class="form-control" maxlength="4" pattern="[0-9]{4}"
                                       placeholder="4-digit PIN for transaction security">
                                <small class="form-text text-muted">PIN required for processing transactions</small>
                            </div>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Note:</strong> Login credentials will be displayed after registration. 
                                The email address above will be used as the username.
                            </div>
                        </div>
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
                                <option value="{{$account->id}}" {{old('float_chart_of_account_id') == $account->id ? 'selected' : ''}}>
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
                                <option value="{{$account->id}}" {{old('commission_chart_of_account_id') == $account->id ? 'selected' : ''}}>
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
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Any additional notes about this agent...">{{old('notes')}}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Financial Settings --}}
            <div class="col-md-4">
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-wallet mr-2"></i>Float & Limits</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label>Initial Float Balance</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="float_balance" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('float_balance', 0)}}">
                            </div>
                            <small class="form-text text-muted">Starting float amount</small>
                        </div>
                        <div class="form-group">
                            <label>Float Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="float_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('float_limit', 100000)}}">
                            </div>
                            <small class="form-text text-muted">Maximum float this agent can hold</small>
                        </div>
                        <div class="form-group">
                            <label>Daily Transaction Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="daily_transaction_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('daily_transaction_limit', 500000)}}">
                            </div>
                        </div>
                        <div class="form-group mb-0">
                            <label>Single Transaction Limit</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">KES</span>
                                </div>
                                <input name="single_transaction_limit" type="number" step="0.01" min="0"
                                       class="form-control" value="{{old('single_transaction_limit', 100000)}}">
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
                                       class="form-control" value="{{old('commission_rate', 0.5)}}">
                                <div class="input-group-append">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Commission earned per transaction
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card bg-light">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-save mr-2"></i>Register Agent
                        </button>
                        <a href="{{url('sacco/agency')}}" class="btn btn-secondary btn-block">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </div>
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

    // Toggle login settings visibility
    $('#create_user_account').on('change', function() {
        if ($(this).is(':checked')) {
            $('#login_settings').slideDown();
        } else {
            $('#login_settings').slideUp();
        }
    });
});
</script>
@endpush

@extends('core::layouts.auth')
@section('title')
    {{ trans_choice('user::general.onboarding_step_kyc', 1) }}
@endsection

@section('styles')
    <style>
        .onboarding-kyc-box { width: 720px; max-width: 95%; margin: 0 auto; }
        .onboarding-kyc-box .login-logo { margin-bottom: 1rem; }
    </style>
@endsection

@section('content')
    <div class="onboarding-kyc-box">
        <div class="login-logo text-center">
            <a href="{{ url('/') }}">
                @if(!empty($logo=\Modules\Setting\Entities\Setting::where('setting_key','core.company_logo')->first()->setting_value))
                    <img class="logo-img logo-img-lg" src="{{ asset('storage/uploads/'.$logo) }}" alt="logo">
                @else
                    <h4>{{ \Modules\Setting\Entities\Setting::where('setting_key','core.company_name')->first()->setting_value }}</h4>
                @endif
            </a>
        </div>
        <div class="card">
            <div class="card-body">
                @include('user::themes.adminlte.auth.onboarding._stepper', ['currentStep' => 3])
                <p class="text-center text-muted mb-3">{{ trans_choice('user::general.onboarding_kyc_msg', 1) }}</p>
                @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3>{{ $completion_percent ?? 0 }}%</h3>
                                <p class="mb-0">{{ trans_choice('user::general.onboarding_kyc_complete', 1) }}</p>
                                <p class="small text-muted mt-2">
                                    {{ trans_choice('user::general.status', 1) }}:
                                    <strong>{{ ucfirst(str_replace('_', ' ', $kyc_status ?? 'incomplete')) }}</strong>
                                </p>
                            </div>
                        </div>
                        @if(($requirements ?? collect())->isNotEmpty())
                        <div class="card mt-3">
                            <div class="card-header"><h3 class="card-title">{{ trans_choice('user::general.onboarding_submit_document', 1) }}</h3></div>
                            <div class="card-body">
                                <form method="post" action="{{ url('register/kyc/document') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="form-group">
                                        <label>{{ trans_choice('user::general.document_type', 1) }}</label>
                                        <select name="document_type" class="form-control" required>
                                            @foreach($requirements as $r)
                                                @if($r->code !== 'signature')
                                                    <option value="{{ $r->code }}">{{ $r->name }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>{{ trans_choice('user::general.document_number', 1) }}</label>
                                        <input type="text" name="document_number" class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label>{{ trans_choice('user::general.document_file', 1) }}</label>
                                        <input type="file" name="file" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf" required>
                                        @include('core::partials.upload_template', ['templateKey' => 'kyc.document'])
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-block">{{ trans_choice('user::general.onboarding_submit', 1) }}</button>
                                </form>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header"><h3 class="card-title">{{ trans_choice('user::general.onboarding_kyc_checklist', 1) }}</h3></div>
                            <div class="card-body p-0">
                                @if(!empty($checklist))
                                <table class="table table-sm mb-0">
                                    <thead><tr><th>{{ trans_choice('user::general.document_type', 1) }}</th><th>{{ trans_choice('user::general.required', 1) }}</th><th>{{ trans_choice('user::general.status', 1) }}</th></tr></thead>
                                    <tbody>
                                    @foreach($checklist as $item)
                                        <tr>
                                            <td>{{ $item['requirement']->name }}</td>
                                            <td>{{ $item['requirement']->mandatory ? trans_choice('core::general.yes', 1) : trans_choice('core::general.no', 1) }}</td>
                                            <td>{{ ucfirst($item['status']) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                @else
                                    <p class="p-3 text-muted mb-0">{{ trans_choice('user::general.onboarding_kyc_unavailable', 1) }}</p>
                                @endif
                            </div>
                        </div>
                        <p class="text-muted small mt-2">{{ trans_choice('user::general.onboarding_kyc_iprs_note', 1) }}</p>
                    </div>
                </div>
                <form method="post" action="{{ url('register/complete') }}" class="mt-3 text-center">
                    @csrf
                    <button type="submit" class="btn btn-success btn-lg">{{ trans_choice('user::general.onboarding_finish', 1) }}</button>
                </form>
            </div>
        </div>
    </div>
@endsection

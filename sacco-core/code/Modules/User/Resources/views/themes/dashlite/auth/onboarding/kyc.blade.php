@extends('core::layouts.auth')
@section('title')
    {{ trans_choice('user::general.onboarding_step_kyc', 1) }}
@endsection

@section('content')
    <div class="nk-block nk-block-middle nk-auth-body bg-white" style="max-width: 720px;">
        <div class="brand-logo pb-3 text-center">
            <a href="{{ url('/') }}">
                @if(!empty($logo=\Modules\Setting\Entities\Setting::where('setting_key','core.company_logo')->first()->setting_value))
                    <img class="logo-light logo-img logo-img-lg" src="{{ asset('storage/uploads/'.$logo) }}" alt="logo">
                @else
                    <h4>{{ \Modules\Setting\Entities\Setting::where('setting_key','core.company_name')->first()->setting_value }}</h4>
                @endif
            </a>
        </div>
        @include('user::themes.dashlite.auth.onboarding._stepper', ['currentStep' => 3])
        <p class="text-soft text-center mb-3">{{ trans_choice('user::general.onboarding_kyc_msg', 1) }}</p>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-inner text-center">
                        <h3>{{ $completion_percent ?? 0 }}%</h3>
                        <p class="mb-0">{{ trans_choice('user::general.onboarding_kyc_complete', 1) }}</p>
                        <p class="small text-muted mt-2">{{ ucfirst(str_replace('_', ' ', $kyc_status ?? 'incomplete')) }}</p>
                    </div>
                </div>
                @if(($requirements ?? collect())->isNotEmpty())
                <div class="card mt-3">
                    <div class="card-inner">
                        <h6 class="title">{{ trans_choice('user::general.onboarding_submit_document', 1) }}</h6>
                        <form method="post" action="{{ url('register/kyc/document') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label class="form-label">{{ trans_choice('user::general.document_type', 1) }}</label>
                                <select name="document_type" class="form-control" required>
                                    @foreach($requirements as $r)
                                        @if($r->code !== 'signature')
                                            <option value="{{ $r->code }}">{{ $r->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ trans_choice('user::general.document_number', 1) }}</label>
                                <input type="text" name="document_number" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">{{ trans_choice('user::general.document_file', 1) }}</label>
                                <input type="file" name="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
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
                    <div class="card-inner">
                        <h6 class="title">{{ trans_choice('user::general.onboarding_kyc_checklist', 1) }}</h6>
                        @if(!empty($checklist))
                        <table class="table table-sm">
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
                            <p class="text-muted">{{ trans_choice('user::general.onboarding_kyc_unavailable', 1) }}</p>
                        @endif
                        <p class="text-muted small">{{ trans_choice('user::general.onboarding_kyc_iprs_note', 1) }}</p>
                    </div>
                </div>
            </div>
        </div>
        <form method="post" action="{{ url('register/complete') }}" class="mt-4 text-center">
            @csrf
            <button type="submit" class="btn btn-success btn-lg">{{ trans_choice('user::general.onboarding_finish', 1) }}</button>
        </form>
    </div>
@endsection

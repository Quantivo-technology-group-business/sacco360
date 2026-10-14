@php
    $otpThreshold = config('sacco.transaction_otp_threshold', 1000);
    $purpose = $purpose ?? 'transaction';
@endphp
<div class="card card-outline card-warning mt-3 transaction-auth-panel">
    <div class="card-header">
        <h3 class="card-title">{{ trans('sacco::general.transaction_authorization') }}</h3>
    </div>
    <div class="card-body">
        <p class="text-muted small">{{ trans('sacco::general.transaction_auth_help', ['amount' => number_format($otpThreshold, 2)]) }}</p>
        @if(session('dev_otp_code'))
            <div class="alert alert-info">{{ trans('sacco::general.dev_otp') }}: <strong>{{ session('dev_otp_code') }}</strong></div>
        @endif
        <div class="form-group">
            <label>{{ trans('sacco::general.transaction_pin') }}</label>
            <input type="password" name="transaction_pin" class="form-control" maxlength="6" autocomplete="off" inputmode="numeric">
        </div>
        <div class="form-group">
            <label>{{ trans('sacco::general.otp_code') }}</label>
            <div class="input-group">
                <input type="text" name="otp_code" class="form-control" maxlength="6" autocomplete="off">
                <div class="input-group-append">
                    <button type="submit" formaction="{{ url('portal/sacco/otp/request') }}" formmethod="POST" class="btn btn-outline-secondary" name="otp_request" value="1"
                            onclick="this.form.querySelector('[name=purpose]') || (function(f){var i=document.createElement('input');i.type='hidden';i.name='purpose';i.value='{{ $purpose }}';f.appendChild(i);})(this.form)">
                        {{ trans('sacco::general.request_otp') }}
                    </button>
                </div>
            </div>
        </div>
        <input type="hidden" name="purpose" value="{{ $purpose }}">
    </div>
</div>

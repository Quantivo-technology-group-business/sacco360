@extends('core::layouts.master')
@section('title') USSD Configuration @endsection
@section('content')

<div class="nk-block-head nk-block-head-sm mb-4">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">USSD Configuration</h3>
            <div class="nk-block-des text-soft">
                <p>Configure USSD mobile banking access for members.</p>
            </div>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ url('sacco/ussd/members') }}" class="btn btn-outline-light bg-white">
                <em class="icon ni ni-users"></em><span>Manage Members</span>
            </a>
            <a href="{{ url('sacco/ussd/sessions') }}" class="btn btn-outline-light bg-white ml-1">
                <em class="icon ni ni-list-thumb"></em><span>Session Log</span>
            </a>
        </div>
    </div>
</div>

{{-- Stats strip --}}
<div class="nk-block mb-4">
    <div class="row g-3">
        <div class="col-6 col-md-3">
            <div class="card card-bordered text-center">
                <div class="card-inner py-3">
                    <div class="fs-22px fw-bold text-primary">{{ $stats['total_enabled'] }}</div>
                    <div class="text-soft small">USSD Enabled</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-bordered text-center">
                <div class="card-inner py-3">
                    <div class="fs-22px fw-bold text-dark">{{ $stats['total_members'] }}</div>
                    <div class="text-soft small">Total Members</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-bordered text-center">
                <div class="card-inner py-3">
                    <div class="fs-22px fw-bold text-info">{{ $stats['sessions_today'] }}</div>
                    <div class="text-soft small">Sessions Today</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card card-bordered text-center">
                <div class="card-inner py-3">
                    <div class="fs-22px fw-bold {{ $stats['locked_members'] > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $stats['locked_members'] }}
                    </div>
                    <div class="text-soft small">Locked Accounts</div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ url('sacco/ussd/settings') }}">
    @csrf
    <div class="row g-4">

        {{-- Global toggle + endpoint --}}
        <div class="col-lg-6">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h6 class="card-title mb-4"><em class="icon ni ni-setting mr-1"></em> Global Settings</h6>
                    <div class="row g-3">

                        <div class="col-12">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ussd_enabled"
                                       name="ussd_enabled" {{ ($settings['ussd.enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ussd_enabled">
                                    <strong>Enable USSD Service</strong>
                                    <span class="d-block text-soft small">When disabled, all USSD requests return a maintenance message.</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">USSD Short Code</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text"><em class="icon ni ni-call"></em></span></div>
                                    <input type="text" name="ussd_shortcode" class="form-control"
                                           value="{{ $settings['ussd.shortcode'] ?? '*123#' }}"
                                           placeholder="e.g. *123#" maxlength="20">
                                </div>
                                <div class="form-note">The short code members dial to access the service.</div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">API Key <span class="text-soft small">(sent in X-USSD-API-Key header)</span></label>
                                <div class="input-group">
                                    <input type="text" name="ussd_api_key" class="form-control"
                                           value="{{ $settings['ussd.api_key'] ?? '' }}"
                                           placeholder="Leave blank to disable API key check">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="this.previousElementSibling.value = Math.random().toString(36).substring(2,34)">
                                            Generate
                                        </button>
                                    </div>
                                </div>
                                <div class="form-note">Shared with your USSD gateway provider. <code>POST {{ url('api/v1/sacco/ussd') }}</code></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">Welcome Message <span class="text-soft small">(≤ 160 chars)</span></label>
                                <input type="text" name="ussd_welcome_message" class="form-control"
                                       value="{{ $settings['ussd.welcome_message'] ?? 'Welcome to SACCO Mobile Banking' }}"
                                       maxlength="160">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Security & limits --}}
        <div class="col-lg-6">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <h6 class="card-title mb-4"><em class="icon ni ni-shield-check mr-1"></em> Security & Limits</h6>
                    <div class="row g-3">

                        <div class="col-12">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ussd_require_pin"
                                       name="ussd_require_pin" {{ ($settings['ussd.require_pin'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ussd_require_pin">
                                    <strong>Require PIN Authentication</strong>
                                    <span class="d-block text-soft small">Members must enter their PIN before accessing any service.</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="ussd_sms_pin_on_enable"
                                       name="ussd_sms_pin_on_enable" {{ ($settings['ussd.sms_pin_on_enable'] ?? '1') === '1' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="ussd_sms_pin_on_enable">
                                    <strong>SMS Temp PIN on Enablement</strong>
                                    <span class="d-block text-soft small">Send a temporary PIN via SMS when USSD is enabled for a member.</span>
                                </label>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">PIN Length</label>
                                <select name="ussd_pin_length" class="form-control">
                                    @foreach ([4, 5, 6] as $len)
                                        <option value="{{ $len }}" {{ ($settings['ussd.pin_length'] ?? '4') == $len ? 'selected' : '' }}>{{ $len }} digits</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Max Failed PIN Attempts</label>
                                <input type="number" name="ussd_pin_attempts_max" class="form-control"
                                       value="{{ $settings['ussd.pin_attempts_max'] ?? 3 }}" min="1" max="10">
                                <div class="form-note">Account locks after this many wrong PINs.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Global Daily Transaction Limit</label>
                                <input type="number" name="ussd_daily_limit" class="form-control"
                                       value="{{ $settings['ussd.daily_limit'] ?? 50000 }}" min="0" step="1000">
                                <div class="form-note">Per-member limits override this.</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Session Timeout (seconds)</label>
                                <input type="number" name="ussd_session_timeout" class="form-control"
                                       value="{{ $settings['ussd.session_timeout'] ?? 120 }}" min="30" max="600">
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        {{-- Default allowed services --}}
        <div class="col-12">
            <div class="card card-bordered">
                <div class="card-inner">
                    <h6 class="card-title mb-4"><em class="icon ni ni-grid-alt mr-1"></em> Default Allowed Services</h6>
                    <p class="text-soft small mb-3">Services enabled by default when USSD is activated for a member. Individual overrides can be set per member.</p>
                    @php
                        $allServices = \Modules\Sacco\Entities\UssdMemberConfig::ALL_SERVICES;
                        $defaultServices = json_decode($settings['ussd.allowed_services'] ?? '[]', true) ?: array_keys($allServices);
                    @endphp
                    <div class="row g-3">
                        @foreach ($allServices as $key => $label)
                            <div class="col-md-4 col-lg-2">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input"
                                           id="svc_{{ $key }}" name="ussd_allowed_services[]"
                                           value="{{ $key }}"
                                           {{ in_array($key, $defaultServices) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="svc_{{ $key }}">{{ $label }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- API endpoint reference --}}
        <div class="col-12">
            <div class="card card-bordered bg-lighter">
                <div class="card-inner">
                    <h6 class="card-title mb-3"><em class="icon ni ni-code mr-1"></em> Gateway Integration</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-soft small">USSD Endpoint (POST)</label>
                            <code class="d-block p-2 bg-white rounded border">{{ url('api/v1/sacco/ussd') }}</code>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-soft small">Expected Payload</label>
                            <code class="d-block p-2 bg-white rounded border">sessionId, phoneNumber, text, serviceCode</code>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-soft small">Authentication</label>
                            <code class="d-block p-2 bg-white rounded border">Header: X-USSD-API-Key: &lt;your-api-key&gt;</code>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-soft small">Response Format</label>
                            <code class="d-block p-2 bg-white rounded border">text/plain — "CON ..." or "END ..."</code>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="mt-4 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
            <em class="icon ni ni-save mr-1"></em> Save Configuration
        </button>
    </div>
</form>
@endsection

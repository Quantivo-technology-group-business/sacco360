@extends('core::layouts.master')
@section('title') USSD Configuration @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>USSD Configuration</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('sacco/ussd/members') }}">USSD Members</a></li>
                    <li class="breadcrumb-item active">Settings</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        {{-- Stats --}}
        <div class="row mb-3">
            <div class="col-md-3"><div class="info-box">
                <span class="info-box-icon bg-primary"><i class="fas fa-mobile-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">USSD Enabled</span>
                    <span class="info-box-number">{{ $stats['total_enabled'] }}</span>
                </div>
            </div></div>
            <div class="col-md-3"><div class="info-box">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total Members</span>
                    <span class="info-box-number">{{ $stats['total_members'] }}</span>
                </div>
            </div></div>
            <div class="col-md-3"><div class="info-box">
                <span class="info-box-icon bg-success"><i class="fas fa-list"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Sessions Today</span>
                    <span class="info-box-number">{{ $stats['sessions_today'] }}</span>
                </div>
            </div></div>
            <div class="col-md-3"><div class="info-box">
                <span class="info-box-icon bg-{{ $stats['locked_members'] > 0 ? 'danger' : 'secondary' }}"><i class="fas fa-lock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Locked Accounts</span>
                    <span class="info-box-number">{{ $stats['locked_members'] }}</span>
                </div>
            </div></div>
        </div>

        <form method="post" action="{{ url('sacco/ussd/settings') }}">
            @csrf
            <div class="row">

                {{-- Global Settings --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-cog mr-1"></i> Global Settings</h3></div>
                        <div class="card-body">

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ussd_enabled"
                                           name="ussd_enabled" {{ ($settings['ussd.enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="ussd_enabled">
                                        Enable USSD Service
                                    </label>
                                </div>
                                <small class="form-text text-muted">When off, all USSD requests return a maintenance message.</small>
                            </div>

                            <div class="form-group">
                                <label>USSD Short Code</label>
                                <input type="text" name="ussd_shortcode" class="form-control"
                                       value="{{ $settings['ussd.shortcode'] ?? '*123#' }}" placeholder="e.g. *123#" maxlength="20">
                            </div>

                            <div class="form-group">
                                <label>API Key <small class="text-muted">(X-USSD-API-Key header)</small></label>
                                <div class="input-group">
                                    <input type="text" name="ussd_api_key" class="form-control"
                                           value="{{ $settings['ussd.api_key'] ?? '' }}" placeholder="Leave blank to disable">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-outline-secondary"
                                                onclick="this.closest('.input-group').querySelector('input').value = Math.random().toString(36).substring(2,34)">
                                            Generate
                                        </button>
                                    </div>
                                </div>
                                <small class="form-text text-muted">Endpoint: <code>POST {{ url('api/v1/sacco/ussd') }}</code></small>
                            </div>

                            <div class="form-group">
                                <label>Welcome Message <small>(≤ 160 chars)</small></label>
                                <input type="text" name="ussd_welcome_message" class="form-control"
                                       value="{{ $settings['ussd.welcome_message'] ?? 'Welcome to SACCO Mobile Banking' }}"
                                       maxlength="160">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Security & Limits --}}
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-shield-alt mr-1"></i> Security & Limits</h3></div>
                        <div class="card-body">

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ussd_require_pin"
                                           name="ussd_require_pin" {{ ($settings['ussd.require_pin'] ?? '1') === '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="ussd_require_pin">Require PIN Authentication</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="ussd_sms_pin_on_enable"
                                           name="ussd_sms_pin_on_enable" {{ ($settings['ussd.sms_pin_on_enable'] ?? '1') === '1' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="ussd_sms_pin_on_enable">SMS Temp PIN on Enablement</label>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>PIN Length</label>
                                        <select name="ussd_pin_length" class="form-control">
                                            @foreach ([4, 5, 6] as $len)
                                                <option value="{{ $len }}" {{ ($settings['ussd.pin_length'] ?? '4') == $len ? 'selected' : '' }}>{{ $len }} digits</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Max Failed PIN Attempts</label>
                                        <input type="number" name="ussd_pin_attempts_max" class="form-control"
                                               value="{{ $settings['ussd.pin_attempts_max'] ?? 3 }}" min="1" max="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Daily Transaction Limit</label>
                                        <input type="number" name="ussd_daily_limit" class="form-control"
                                               value="{{ $settings['ussd.daily_limit'] ?? 50000 }}" min="0" step="1000">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Session Timeout (seconds)</label>
                                        <input type="number" name="ussd_session_timeout" class="form-control"
                                               value="{{ $settings['ussd.session_timeout'] ?? 120 }}" min="30" max="600">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Default services --}}
                <div class="col-12">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-th mr-1"></i> Default Allowed Services</h3></div>
                        <div class="card-body">
                            @php
                                $allServices = \Modules\Sacco\Entities\UssdMemberConfig::ALL_SERVICES;
                                $defaultServices = json_decode($settings['ussd.allowed_services'] ?? '[]', true) ?: array_keys($allServices);
                            @endphp
                            <div class="row">
                                @foreach ($allServices as $key => $label)
                                    <div class="col-md-4 col-lg-2">
                                        <div class="custom-control custom-checkbox mb-2">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="svc_{{ $key }}" name="ussd_allowed_services[]"
                                                   value="{{ $key }}" {{ in_array($key, $defaultServices) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="svc_{{ $key }}">{{ $label }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Integration reference --}}
                <div class="col-12">
                    <div class="card card-secondary">
                        <div class="card-header"><h3 class="card-title"><i class="fas fa-code mr-1"></i> Gateway Integration</h3></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="text-muted small">USSD Endpoint (POST)</label>
                                    <code class="d-block p-2 bg-white border rounded mb-2">{{ url('api/v1/sacco/ussd') }}</code>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Payload Fields</label>
                                    <code class="d-block p-2 bg-white border rounded mb-2">sessionId, phoneNumber, text, serviceCode</code>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Authentication</label>
                                    <code class="d-block p-2 bg-white border rounded mb-2">Header: X-USSD-API-Key: &lt;key&gt;</code>
                                </div>
                                <div class="col-md-6">
                                    <label class="text-muted small">Response Format</label>
                                    <code class="d-block p-2 bg-white border rounded mb-2">text/plain — CON ... or END ...</code>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mt-2">
                <button type="submit" class="btn btn-primary float-right">
                    <i class="fas fa-save mr-1"></i> Save Configuration
                </button>
            </div>
        </form>

    </div>
</section>
@endsection

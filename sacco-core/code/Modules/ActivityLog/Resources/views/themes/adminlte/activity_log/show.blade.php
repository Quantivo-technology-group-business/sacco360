@extends('core::layouts.master')
@section('title')
    Audit Log Detail
@endsection
@section('styles')
<style>
    .badge-action-create   { background-color: #28a745; color: #fff; }
    .badge-action-update   { background-color: #007bff; color: #fff; }
    .badge-action-delete   { background-color: #dc3545; color: #fff; }
    .badge-action-login    { background-color: #17a2b8; color: #fff; }
    .badge-action-logout   { background-color: #6c757d; color: #fff; }
    .badge-action-failed   { background-color: #fd7e14; color: #fff; }
    .badge-action-approve  { background-color: #6f42c1; color: #fff; }
    .badge-action-reject   { background-color: #dc3545; color: #fff; }
    .badge-action-transfer { background-color: #6610f2; color: #fff; }
    .badge-action-default  { background-color: #e9ecef; color: #212529; }
    .audit-avatar-lg {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; border-radius: 50%;
        background: var(--sacco-primary, #3c8dbc); color: #fff;
        font-size: 18px; font-weight: 700; flex-shrink: 0;
    }
    .diff-changed { background-color: #fff3cd !important; }
    .diff-table th, .diff-table td { vertical-align: middle; word-break: break-all; }
</style>
@endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-history mr-2"></i>Audit Log Detail</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url('activity_log') }}">Audit Log</a></li>
                    <li class="breadcrumb-item active">#{{ $activity_log->id }}</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">

                {{-- Header Card --}}
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            @php
                                $causer = $activity_log->causer;
                                $initials = '';
                                if ($causer) {
                                    $initials = strtoupper(substr($causer->first_name ?? '', 0, 1) . substr($causer->last_name ?? '', 0, 1));
                                }
                                $at = $activity_log->action_type ?: 'default';
                                $badgeClass = 'badge-action-' . (in_array($at, ['create','update','delete','login','logout','failed','approve','reject','transfer','export','view']) ? $at : 'default');
                            @endphp
                            <span class="audit-avatar-lg mr-3">{{ $initials ?: '?' }}</span>
                            <div class="flex-grow-1">
                                <h4 class="mb-1">
                                    @if($causer)
                                        <a href="{{ url('user/' . $causer->id . '/show') }}">
                                            {{ $causer->first_name }} {{ $causer->last_name }}
                                        </a>
                                    @else
                                        <span class="text-muted">System / Guest</span>
                                    @endif
                                </h4>
                                @if($causer && $causer->roles && $causer->roles->count())
                                    <span class="text-muted small">{{ $causer->roles->implode('name', ', ') }}</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="badge {{ $badgeClass }} px-3 py-2" style="font-size:14px;">
                                    {{ $activity_log->action_type ? ucfirst($activity_log->action_type) : 'Event' }}
                                </span>
                                <div class="text-muted small mt-1">
                                    {{ $activity_log->created_at ? $activity_log->created_at->format('Y-m-d H:i:s') : '' }}
                                </div>
                            </div>
                        </div>
                        <hr>
                        <p class="mb-0"><strong>Description:</strong> {{ $activity_log->description }}</p>
                    </div>
                </div>

                {{-- Before / After Diff Card --}}
                @if(!empty($diff))
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-exchange-alt mr-1"></i> Changes (Before / After)</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered diff-table mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Field</th>
                                    <th>Before</th>
                                    <th>After</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($diff as $field => $values)
                                <tr class="{{ $values['changed'] ? 'diff-changed' : '' }}">
                                    <td class="font-weight-bold small">{{ $field }}</td>
                                    <td class="small">{{ is_array($values['old']) ? json_encode($values['old']) : $values['old'] }}</td>
                                    <td class="small">{{ is_array($values['new']) ? json_encode($values['new']) : $values['new'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @elseif($activity_log->properties && $activity_log->properties->count())
                {{-- Plain properties (old format) --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-tags mr-1"></i> Properties</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered mb-0">
                            <thead class="thead-light">
                                <tr><th>Key</th><th>Value</th></tr>
                            </thead>
                            <tbody>
                                @foreach($activity_log->properties as $key => $value)
                                    @if(!in_array($key, ['ip_address','user_agent','url','method']))
                                    <tr>
                                        <td class="font-weight-bold small">{{ $key }}</td>
                                        <td class="small">{{ is_array($value) ? json_encode($value) : $value }}</td>
                                    </tr>
                                    @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- Raw JSON (collapsed) --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-light collapsed-card">
                        <h5 class="card-title mb-0">
                            <button class="btn btn-link p-0" data-card-widget="collapse" style="font-size:inherit;">
                                <i class="fas fa-code mr-1"></i> Raw Properties JSON
                            </button>
                        </h5>
                        <div class="card-tools">
                            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body" style="display:none;">
                        <pre class="small mb-0" style="background:#f8f9fa;padding:12px;border-radius:4px;max-height:300px;overflow:auto;">{{ json_encode($activity_log->properties, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>

            </div>

            <div class="col-md-4">

                {{-- Request Info --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-network-wired mr-1"></i> Request Info</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:110px;">IP Address</td>
                                <td><code>{{ $activity_log->ip_address ?: ($activity_log->properties['ip_address'] ?? '—') }}</code></td>
                            </tr>
                            <tr>
                                <td class="text-muted">Method</td>
                                <td>
                                    @php $method = $activity_log->properties['method'] ?? ''; @endphp
                                    @if($method)
                                        <span class="badge badge-secondary">{{ $method }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">URL</td>
                                <td class="small" style="word-break:break-all;">
                                    {{ $activity_log->properties['url'] ?? '—' }}
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">User Agent</td>
                                <td class="small" style="word-break:break-all;">
                                    {{ $activity_log->user_agent ?: ($activity_log->properties['user_agent'] ?? '—') }}
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                {{-- Subject Info --}}
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0"><i class="fas fa-cube mr-1"></i> Subject</h5>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <tr>
                                <td class="text-muted" style="width:110px;">Type</td>
                                <td>
                                    @if($activity_log->subject_type)
                                        <code>{{ class_basename($activity_log->subject_type) }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">ID</td>
                                <td>
                                    @if($activity_log->subject_id)
                                        #{{ $activity_log->subject_id }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Module</td>
                                <td>
                                    @if($activity_log->module)
                                        <span class="badge badge-light border">{{ ucfirst($activity_log->module) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td class="text-muted">Log Name</td>
                                <td>{{ $activity_log->log_name ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </div>

                <div class="mt-2">
                    <a href="{{ url('activity_log') }}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Audit Log
                    </a>
                </div>

            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
@endsection

@extends('core::layouts.master')
@section('title')
    Audit Log
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
    .badge-action-export   { background-color: #20c997; color: #fff; }
    .badge-action-view     { background-color: #adb5bd; color: #212529; }
    .badge-action-default  { background-color: #e9ecef; color: #212529; }
    .audit-avatar {
        display: inline-flex; align-items: center; justify-content: center;
        width: 32px; height: 32px; border-radius: 50%;
        background: var(--sacco-primary, #3c8dbc); color: #fff;
        font-size: 12px; font-weight: 700; flex-shrink: 0;
    }
    .info-box-number { font-size: 1.5rem; }
</style>
@endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-history mr-2"></i>Audit Log</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Audit Log</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        {{-- Stats Row --}}
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info elevation-1"><i class="fas fa-calendar-day"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Today's Events</span>
                        <span class="info-box-number">{{ number_format($stats['today_events']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success elevation-1"><i class="fas fa-calendar-week"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">This Week</span>
                        <span class="info-box-number">{{ number_format($stats['week_events']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Unique Users Today</span>
                        <span class="info-box-number">{{ number_format($stats['unique_users_today']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-danger elevation-1"><i class="fas fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Failed Logins Today</span>
                        <span class="info-box-number">{{ number_format($stats['failed_logins_today']) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filter Bar --}}
        <div class="card card-outline card-primary collapsed-card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filters</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <form action="{{ url('activity_log') }}" method="GET">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>User</label>
                                <select name="user_id" class="form-control form-control-sm">
                                    <option value="">All Users</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                            {{ trim($u->first_name . ' ' . $u->last_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Action Type</label>
                                <select name="action_type" class="form-control form-control-sm">
                                    <option value="">All Actions</option>
                                    @foreach($actionTypes as $at)
                                        <option value="{{ $at }}" {{ request('action_type') == $at ? 'selected' : '' }}>
                                            {{ ucfirst($at) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Module</label>
                                <select name="module" class="form-control form-control-sm">
                                    <option value="">All Modules</option>
                                    @foreach($modules as $mod)
                                        <option value="{{ $mod }}" {{ request('module') == $mod ? 'selected' : '' }}>
                                            {{ ucfirst($mod) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date From</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Date To</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Search</label>
                                <input type="text" name="s" class="form-control form-control-sm" placeholder="Description, IP, user name..." value="{{ request('s') }}">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-search"></i> Apply Filters
                            </button>
                            <a href="{{ url('activity_log') }}" class="btn btn-secondary btn-sm ml-1">
                                <i class="fas fa-times"></i> Reset
                            </a>
                            @can('activitylog.activity_logs.index')
                            <a href="{{ url('activity_log/export') }}?{{ http_build_query(request()->except('_token')) }}" class="btn btn-success btn-sm ml-1">
                                <i class="fas fa-download"></i> Export CSV
                            </a>
                            @endcan
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Main Table --}}
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list mr-1"></i> Audit Events</h3>
                <div class="card-tools">
                    <span class="badge badge-info">{{ $data->total() }} records</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th style="width:50px">#</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Subject</th>
                                <th>Module</th>
                                <th>IP Address</th>
                                <th>Date / Time</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($data as $row)
                            @php
                                $initials = '';
                                if ($row->causer_id) {
                                    $parts = explode(' ', trim($row->user_name));
                                    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
                                }
                                $at = $row->action_type ?: 'default';
                                $badgeClass = 'badge-action-' . (in_array($at, ['create','update','delete','login','logout','failed','approve','reject','transfer','export','view']) ? $at : 'default');
                            @endphp
                            <tr>
                                <td class="text-muted small">{{ $row->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="audit-avatar mr-2">{{ $initials ?: '?' }}</span>
                                        <div>
                                            @if($row->causer_id)
                                                <a href="{{ url('user/' . $row->causer_id . '/show') }}" class="font-weight-semibold">
                                                    {{ trim($row->user_name) ?: 'User #'.$row->causer_id }}
                                                </a>
                                            @else
                                                <span class="text-muted">System / Guest</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge {{ $badgeClass }} px-2 py-1">
                                        {{ $row->action_type ? ucfirst($row->action_type) : '—' }}
                                    </span>
                                </td>
                                <td>{{ $row->description }}</td>
                                <td class="small text-muted">
                                    @if($row->subject_type)
                                        {{ class_basename($row->subject_type) }}
                                        @if($row->subject_id)
                                            #{{ $row->subject_id }}
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if($row->module)
                                        <span class="badge badge-light border">{{ ucfirst($row->module) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $row->ip_address ?: '—' }}</td>
                                <td class="small text-muted text-nowrap">{{ $row->created_at ? $row->created_at->format('Y-m-d H:i') : '' }}</td>
                                <td>
                                    <a href="{{ url('activity_log/' . $row->id . '/show') }}" class="btn btn-xs btn-outline-secondary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No audit events found.</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <div class="row align-items-center">
                    <div class="col-md-4 small text-muted">
                        Page {{ $data->currentPage() }} of {{ $data->lastPage() }}
                        &nbsp;&bull;&nbsp; {{ number_format($data->total()) }} total records
                    </div>
                    <div class="col-md-8 d-flex justify-content-end">
                        {{ $data->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
@section('scripts')
<script>
// Auto-expand filter bar if any filter is active
(function(){
    var params = new URLSearchParams(window.location.search);
    var hasFilter = false;
    ['user_id','action_type','module','date_from','date_to','s'].forEach(function(k){
        if(params.get(k)) hasFilter = true;
    });
    if(hasFilter){
        var card = document.querySelector('.card-outline.card-primary');
        if(card) card.classList.remove('collapsed-card');
    }
})();
</script>
@endsection

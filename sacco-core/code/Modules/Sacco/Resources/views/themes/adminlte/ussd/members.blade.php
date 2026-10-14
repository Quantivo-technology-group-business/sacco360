@extends('core::layouts.master')
@section('title') USSD Member Access @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>USSD Member Access</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ url('sacco/ussd/settings') }}">USSD Settings</a></li>
                    <li class="breadcrumb-item active">Members</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <div class="card">
            <div class="card-header">
                <form method="get" class="d-flex flex-wrap gap-2" style="gap:.5rem">
                    <input type="text" name="s" class="form-control form-control-sm" style="max-width:250px"
                           placeholder="Name, mobile or account..." value="{{ request('s') }}">
                    <select name="filter" class="form-control form-control-sm" style="max-width:160px">
                        <option value="">All Members</option>
                        <option value="enabled"  {{ request('filter')==='enabled'  ? 'selected':'' }}>Enabled</option>
                        <option value="disabled" {{ request('filter')==='disabled' ? 'selected':'' }}>Disabled</option>
                        <option value="locked"   {{ request('filter')==='locked'   ? 'selected':'' }}>Locked</option>
                    </select>
                    <button class="btn btn-primary btn-sm">Filter</button>
                    <a href="{{ url('sacco/ussd/members') }}" class="btn btn-default btn-sm">Reset</a>
                </form>
                <div class="card-tools">
                    <a href="{{ url('sacco/ussd/settings') }}" class="btn btn-sm btn-default">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <a href="{{ url('sacco/ussd/sessions') }}" class="btn btn-sm btn-default ml-1">
                        <i class="fas fa-list"></i> Sessions
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-bordered mb-0">
                    <thead>
                        <tr>
                            <th>Member</th>
                            <th>Mobile</th>
                            <th>Account</th>
                            <th>USSD</th>
                            <th>PIN</th>
                            <th>Last Access</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($query as $row)
                            <tr>
                                <td><a href="{{ url('client/'.$row->id.'/show') }}">{{ $row->first_name }} {{ $row->last_name }}</a></td>
                                <td>{{ $row->mobile ?? '—' }}</td>
                                <td><code>{{ $row->account_number ?? '—' }}</code></td>
                                <td>
                                    @if($row->locked_at)
                                        <span class="badge badge-danger">Locked</span>
                                    @elseif($row->ussd_enabled)
                                        <span class="badge badge-success">Enabled</span>
                                    @else
                                        <span class="badge badge-secondary">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row->pin_set)
                                        <i class="fas fa-check-circle text-success"></i>
                                    @else
                                        <i class="fas fa-times-circle text-muted"></i>
                                    @endif
                                </td>
                                <td class="text-muted small">
                                    {{ $row->last_access_at ? \Carbon\Carbon::parse($row->last_access_at)->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        @if($row->ussd_enabled)
                                            <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/disable') }}" class="d-inline">@csrf
                                                <button class="btn btn-warning btn-sm" title="Disable">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/enable') }}" class="d-inline">@csrf
                                                <button class="btn btn-success btn-sm" title="Enable">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/reset-pin') }}"
                                              class="d-inline confirm" data-confirm="Reset USSD PIN?">@csrf
                                            <button class="btn btn-info btn-sm" title="Reset PIN">
                                                <i class="fas fa-key"></i>
                                            </button>
                                        </form>
                                        @if($row->locked_at)
                                            <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/unlock') }}" class="d-inline">@csrf
                                                <button class="btn btn-primary btn-sm" title="Unlock">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ url('client/'.$row->id.'/show') }}#ussd" class="btn btn-default btn-sm" title="Full Config">
                                            <i class="fas fa-cog"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-3">No members found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($query->hasPages())
                <div class="card-footer">{{ $query->links() }}</div>
            @endif
        </div>

    </div>
</section>
@endsection

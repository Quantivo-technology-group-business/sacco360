@extends('core::layouts.master')
@section('title') USSD Member Access @endsection
@section('content')

<div class="nk-block-head nk-block-head-sm mb-4">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">USSD Member Access</h3>
            <div class="nk-block-des text-soft">
                <p>Enable, configure, and manage USSD access per member.</p>
            </div>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ url('sacco/ussd/settings') }}" class="btn btn-outline-light bg-white">
                <em class="icon ni ni-setting"></em><span>Global Settings</span>
            </a>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="nk-block mb-3">
    <form method="get" action="{{ url('sacco/ussd/members') }}">
        <div class="card card-bordered">
            <div class="card-inner py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <div class="form-group mb-0">
                            <label class="form-label form-label-sm">Search</label>
                            <input type="text" name="s" class="form-control form-control-sm"
                                   placeholder="Name, mobile, account number..." value="{{ request('s') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group mb-0">
                            <label class="form-label form-label-sm">Status</label>
                            <select name="filter" class="form-control form-control-sm">
                                <option value="">All Members</option>
                                <option value="enabled"  {{ request('filter') === 'enabled'  ? 'selected' : '' }}>USSD Enabled</option>
                                <option value="disabled" {{ request('filter') === 'disabled' ? 'selected' : '' }}>USSD Disabled</option>
                                <option value="locked"   {{ request('filter') === 'locked'   ? 'selected' : '' }}>Locked</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary btn-sm">Filter</button>
                        <a href="{{ url('sacco/ussd/members') }}" class="btn btn-outline-secondary btn-sm ml-1">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<div class="nk-block">
    <div class="card card-bordered">
        <div class="card-inner p-0">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="thead-light">
                        <tr>
                            <th>Member</th>
                            <th>Mobile</th>
                            <th>Account</th>
                            <th>USSD Status</th>
                            <th>PIN Set</th>
                            <th>Last Access</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($query as $row)
                            <tr>
                                <td>
                                    <a href="{{ url('client/'.$row->id.'/show') }}" class="fw-medium">
                                        {{ $row->first_name }} {{ $row->last_name }}
                                    </a>
                                </td>
                                <td>{{ $row->mobile ?? '—' }}</td>
                                <td><code>{{ $row->account_number ?? '—' }}</code></td>
                                <td>
                                    @if($row->locked_at)
                                        <span class="badge badge-danger">Locked</span>
                                    @elseif($row->ussd_enabled)
                                        <span class="badge badge-success">Enabled</span>
                                    @else
                                        <span class="badge badge-light">Disabled</span>
                                    @endif
                                </td>
                                <td>
                                    @if($row->pin_set)
                                        <em class="icon ni ni-check-circle text-success"></em>
                                    @else
                                        <em class="icon ni ni-cross-circle text-muted"></em>
                                    @endif
                                </td>
                                <td class="text-soft small">
                                    {{ $row->last_access_at ? \Carbon\Carbon::parse($row->last_access_at)->diffForHumans() : 'Never' }}
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-toggle="dropdown">
                                            Actions
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if($row->ussd_enabled)
                                                <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/disable') }}">@csrf
                                                    <button class="dropdown-item text-warning">
                                                        <em class="icon ni ni-na mr-1"></em> Disable USSD
                                                    </button>
                                                </form>
                                            @else
                                                <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/enable') }}">@csrf
                                                    <button class="dropdown-item text-success">
                                                        <em class="icon ni ni-check-circle mr-1"></em> Enable USSD
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/reset-pin') }}"
                                                  class="confirm" data-confirm="Reset this member's USSD PIN?">@csrf
                                                <button class="dropdown-item">
                                                    <em class="icon ni ni-reload mr-1"></em> Reset PIN
                                                </button>
                                            </form>

                                            @if($row->locked_at)
                                                <form method="post" action="{{ url('sacco/ussd/member/'.$row->id.'/unlock') }}">@csrf
                                                    <button class="dropdown-item text-info">
                                                        <em class="icon ni ni-unlock mr-1"></em> Unlock Account
                                                    </button>
                                                </form>
                                            @endif

                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="{{ url('client/'.$row->id.'/show') }}#ussd">
                                                <em class="icon ni ni-eye mr-1"></em> View Full Config
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-soft py-4">No members found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($query->hasPages())
            <div class="card-inner border-top">
                {{ $query->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

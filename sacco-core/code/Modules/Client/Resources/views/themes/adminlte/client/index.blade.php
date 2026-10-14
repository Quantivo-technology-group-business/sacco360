@extends('core::layouts.master')
@section('title')
    {{ trans_choice('client::general.client',2) }}
@endsection
@section('content')
    <section class="content admin-list-page">
        {{-- Hero + stats --}}
        <div class="list-hero">
            <div class="row align-items-center">
                <div class="col-md-5 mb-3 mb-md-0">
                    <h1><i class="fas fa-users mr-2"></i>{{ trans_choice('client::general.client', 2) }}</h1>
                    <p class="list-hero-meta">{{ trans_choice('client::general.client', 2) }} — manage members, profiles, and assignments</p>
                </div>
                <div class="col-md-7">
                    <div class="row">
                        <div class="col-6 col-sm-3 mb-2 mb-sm-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($stats['total'] ?? $data->total()) }}</span>
                                <span class="stat-label">{{ trans_choice('core::general.total', 1) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3 mb-2 mb-sm-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($stats['active'] ?? 0) }}</span>
                                <span class="stat-label">{{ trans_choice('core::general.active', 1) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($stats['pending'] ?? 0) }}</span>
                                <span class="stat-label">{{ trans_choice('core::general.pending', 1) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-sm-3">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($stats['inactive'] ?? 0) }}</span>
                                <span class="stat-label">{{ trans_choice('core::general.inactive', 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Toolbar: filters + search + actions --}}
        <div class="list-toolbar">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-2 mb-lg-0">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="text-muted small font-weight-bold mr-2 d-none d-sm-inline">{{ trans_choice('core::general.filter', 1) }}:</span>
                        <a href="{{ url('client') }}" class="filter-pill {{ !request('status') ? 'active' : '' }}">
                            All
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'page' => 1]) }}" class="filter-pill {{ request('status') == 'active' ? 'active' : '' }}">
                            {{ trans_choice('core::general.active', 1) }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'pending', 'page' => 1]) }}" class="filter-pill {{ request('status') == 'pending' ? 'active' : '' }}">
                            {{ trans_choice('core::general.pending', 1) }}
                        </a>
                        <a href="{{ request()->fullUrlWithQuery(['status' => 'inactive', 'page' => 1]) }}" class="filter-pill {{ request('status') == 'inactive' ? 'active' : '' }}">
                            {{ trans_choice('core::general.inactive', 1) }}
                        </a>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex justify-content-lg-end align-items-center flex-wrap">
                        <a href="{{ url('client/member360') }}" class="btn btn-outline-primary btn-sm mr-2 mb-1">
                            <i class="fas fa-chart-pie mr-1"></i>{{ trans('sacco::general.member_360') }}
                        </a>
                        @can('client.clients.create')
                            <a href="{{ url('client/create') }}" class="btn btn-sacco-accent btn-sm mr-2 mb-1">
                                <i class="fas fa-plus mr-1"></i>{{ trans_choice('core::general.add', 1) }} {{ trans_choice('client::general.client', 1) }}
                            </a>
                        @endcan
                        <form class="form-inline mb-1" action="{{ url('client') }}">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('per_page'))
                                <input type="hidden" name="per_page" value="{{ request('per_page') }}">
                            @endif
                            <div class="input-group input-group-sm">
                                <input type="text" name="s" class="form-control" value="{{ request('s') }}"
                                       placeholder="{{ trans_choice('core::general.search', 1) }} by name, ID, phone, member no…"
                                       title="Search by name, National ID, phone number, member number, email, or account number">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table card --}}
        <div class="card list-card mb-0">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="text-muted small">
                    <strong>{{ $data->total() }}</strong> {{ trans_choice('client::general.client', $data->total()) }}
                    @if(request('s'))
                        · {{ trans_choice('core::general.search', 1) }}: <em>"{{ request('s') }}"</em>
                    @endif
                </span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                        <i class="fas fa-sliders-h mr-1"></i>{{ request('per_page') ?: 20 }} per page
                    </button>
                    <div class="dropdown-menu dropdown-menu-right">
                        <h6 class="dropdown-header">{{ trans_choice('core::general.show', 1) }}</h6>
                        @foreach([10, 20, 50] as $pp)
                            <a href="{{ request()->fullUrlWithQuery(['per_page' => $pp, 'page' => 1]) }}"
                               class="dropdown-item {{ (request('per_page') == $pp || (!request('per_page') && $pp == 20)) ? 'active' : '' }}">
                                {{ $pp }} per page
                            </a>
                        @endforeach
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header">Sort order</h6>
                        <a href="{{ request()->fullUrlWithQuery(['order_by_dir' => 'asc']) }}"
                           class="dropdown-item {{ (request('order_by_dir') == 'asc' || !request('order_by_dir')) ? 'active' : '' }}">ASC</a>
                        <a href="{{ request()->fullUrlWithQuery(['order_by_dir' => 'desc']) }}"
                           class="dropdown-item {{ request('order_by_dir') == 'desc' ? 'active' : '' }}">DESC</a>
                    </div>
                </div>
            </div>

            @if($data->count())
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-list mb-0">
                        <thead>
                        <tr>
                            <th>
                                <a href="{{ table_order_link('name') }}">{{ trans_choice('core::general.name', 1) }}</a>
                            </th>
                            <th>
                                <a href="{{ table_order_link('id') }}">{{ trans_choice('core::general.id', 1) }}</a>
                            </th>
                            <th>{{ trans('core::general.mobile') }}</th>
                            <th>
                                <a href="{{ table_order_link('status') }}">{{ trans_choice('core::general.status', 1) }}</a>
                            </th>
                            <th>
                                <a href="{{ table_order_link('branch') }}">{{ trans_choice('core::general.branch', 1) }}</a>
                            </th>
                            <th>
                                <a href="{{ table_order_link('staff') }}">{{ trans_choice('core::general.staff', 1) }}</a>
                            </th>
                            <th class="text-right">{{ trans_choice('core::general.action', 1) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $key)
                            @php
                                $initials = strtoupper(substr($key->first_name ?? '', 0, 1) . substr($key->last_name ?? '', 0, 1));
                                $statusClass = match($key->status) {
                                    'active' => 'badge-status-active',
                                    'pending' => 'badge-status-pending',
                                    'deceased' => 'badge-status-deceased',
                                    default => 'badge-status-inactive',
                                };
                                $statusLabel = match($key->status) {
                                    'pending' => trans_choice('core::general.pending', 1),
                                    'active' => trans_choice('core::general.active', 1),
                                    'inactive' => trans_choice('core::general.inactive', 1),
                                    'deceased' => trans_choice('client::general.deceased', 1),
                                    'unspecified' => trans_choice('core::general.unspecified', 1),
                                    default => ucfirst($key->status ?? ''),
                                };
                            @endphp
                            <tr>
                                <td>
                                    <div class="member-cell">
                                        @if(!empty($key->photo))
                                            <img class="member-avatar"
                                                 src="{{ asset('storage/uploads/' . $key->photo) }}"
                                                 alt="{{ $key->name }}">
                                        @else
                                            <div class="member-avatar-placeholder">{{ $initials ?: '?' }}</div>
                                        @endif
                                        <div>
                                            <a href="{{ url('client/' . $key->id . '/show') }}" class="member-name d-block">
                                                {{ trim($key->first_name . ' ' . $key->last_name) }}
                                            </a>
                                            @if($key->external_id || $key->account_number)
                                                <span class="member-sub">{{ $key->external_id ?: $key->account_number }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="text-muted font-weight-bold">#{{ $key->id }}</span></td>
                                <td>
                                    @if($key->mobile)
                                        <a href="tel:{{ $key->mobile }}" class="text-dark">{{ $key->mobile }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-status {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    @if(!empty($key->branch_id))
                                        <a href="{{ url('branch/' . $key->branch_id . '/show') }}" class="chip-link">
                                            <i class="fas fa-building"></i>{{ $key->branch }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(!empty($key->loan_officer_id))
                                        <a href="{{ url('user/' . $key->loan_officer_id . '/show') }}" class="chip-link">
                                            <i class="fas fa-user-tie"></i>{{ $key->staff }}
                                        </a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ url('client/' . $key->id . '/member360') }}"
                                       class="btn btn-sacco-accent btn-action-sm" title="{{ trans('sacco::general.member_360') }}">
                                        <i class="fas fa-chart-pie"></i>
                                    </a>
                                    <a href="{{ url('client/' . $key->id . '/show') }}"
                                       class="btn btn-outline-primary btn-action-sm" title="{{ trans_choice('core::general.detail', 2) }}">
                                        <i class="far fa-eye"></i>
                                    </a>
                                    @canany(['client.clients.edit', 'client.clients.edit_own'])
                                        <a href="{{ url('client/' . $key->id . '/edit') }}"
                                           class="btn btn-outline-secondary btn-action-sm" title="{{ trans_choice('core::general.edit', 1) }}">
                                            <i class="far fa-edit"></i>
                                        </a>
                                    @endcanany
                                    <div class="btn-group d-inline-block">
                                        <button type="button" class="btn btn-light btn-action-sm dropdown-toggle"
                                                data-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            <a href="{{ url('client/' . $key->id . '/show') }}" class="dropdown-item">
                                                <i class="far fa-eye mr-2"></i>{{ trans_choice('core::general.detail', 2) }}
                                            </a>
                                            @canany(['client.clients.edit', 'client.clients.edit_own'])
                                                <a href="{{ url('client/' . $key->id . '/edit') }}" class="dropdown-item">
                                                    <i class="far fa-edit mr-2"></i>{{ trans_choice('core::general.edit', 1) }}
                                                </a>
                                            @endcanany
                                            @canany(['client.clients.destroy', 'client.clients.destroy_own'])
                                                <div class="dropdown-divider"></div>
                                                <a href="{{ url('client/' . $key->id . '/destroy') }}" class="dropdown-item text-danger confirm">
                                                    <i class="fas fa-trash mr-2"></i>{{ trans_choice('core::general.delete', 1) }}
                                                </a>
                                            @endcanany
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-muted small mb-2 mb-md-0">
                            Showing
                            <strong>{{ $data->firstItem() }}–{{ $data->lastItem() }}</strong>
                            {{ trans_choice('core::general.of', 1) }}
                            <strong>{{ $data->total() }}</strong>
                        </div>
                        <div class="col-md-8">
                            <div class="d-flex justify-content-md-end">
                                {{ $data->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="list-empty">
                    <i class="fas fa-users d-block"></i>
                    <h5 class="text-muted">{{ trans('core::general.zeroRecords') }}</h5>
                    @if(request('s') || request('status'))
                        <a href="{{ url('client') }}" class="btn btn-outline-primary btn-sm mt-2">
                            Clear filters
                        </a>
                    @else
                        @can('client.clients.create')
                            <a href="{{ url('client/create') }}" class="btn btn-sacco-accent btn-sm mt-2">
                                <i class="fas fa-plus mr-1"></i>{{ trans_choice('core::general.add', 1) }} {{ trans_choice('client::general.client', 1) }}
                            </a>
                        @endcan
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection

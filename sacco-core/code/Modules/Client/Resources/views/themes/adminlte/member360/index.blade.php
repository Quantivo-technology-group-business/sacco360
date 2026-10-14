@extends('core::layouts.master')
@section('title')
    {{ trans('sacco::general.member_360') }}
@endsection
@section('content')
    <section class="content admin-list-page member-360-page">
        <div class="list-hero">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-3 mb-lg-0">
                    <h1><i class="fas fa-chart-pie mr-2"></i>{{ trans('sacco::general.member_360') }}</h1>
                    <p class="list-hero-meta">Complete financial snapshot for every member — savings, shares, loans, and net position</p>
                </div>
                <div class="col-lg-7">
                    <div class="row">
                        <div class="col-6 col-md-4 col-lg mb-2 mb-lg-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($portfolio['members'] ?? 0) }}</span>
                                <span class="stat-label">{{ trans_choice('client::general.client', 2) }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg mb-2 mb-lg-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($portfolio['total_savings'] ?? 0, 0) }}</span>
                                <span class="stat-label">{{ trans('sacco::general.savings_balance') }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-4 col-lg mb-2 mb-lg-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($portfolio['total_shares'] ?? 0, 0) }}</span>
                                <span class="stat-label">{{ trans('sacco::general.share_value') }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-lg mb-2 mb-lg-0">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($portfolio['total_loans'] ?? 0, 0) }}</span>
                                <span class="stat-label">{{ trans('sacco::general.loan_balance') }}</span>
                            </div>
                        </div>
                        <div class="col-6 col-md-6 col-lg">
                            <div class="stat-pill">
                                <span class="stat-value">{{ number_format($portfolio['net_position'] ?? 0, 0) }}</span>
                                <span class="stat-label">{{ trans('sacco::general.net_position') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="list-toolbar">
            <div class="row align-items-center">
                <div class="col-lg-7 mb-2 mb-lg-0">
                    <a href="{{ url('client/member360') }}" class="filter-pill {{ !request('status') && !request('arrears') ? 'active' : '' }}">All</a>
                    <a href="{{ request()->fullUrlWithQuery(['status' => 'active', 'arrears' => null, 'page' => 1]) }}" class="filter-pill {{ request('status') == 'active' ? 'active' : '' }}">{{ trans_choice('core::general.active', 1) }}</a>
                    <a href="{{ request()->fullUrlWithQuery(['arrears' => 1, 'status' => null, 'page' => 1]) }}" class="filter-pill {{ request('arrears') ? 'active' : '' }}">{{ trans('sacco::general.arrears') }}</a>
                </div>
                <div class="col-lg-5">
                    <form class="form-inline justify-content-lg-end" action="{{ url('client/member360') }}">
                        @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                        <div class="input-group input-group-sm">
                            <input type="text" name="s" class="form-control" value="{{ request('s') }}"
                                   placeholder="{{ trans('core::general.search') }} by name, ID, phone, member no…"
                                   title="Search by name, National ID, phone number, member number, email, or account number">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="card list-card">
            @if($data->count())
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-list mb-0 member-360-table">
                        <thead>
                        <tr>
                            <th>{{ trans_choice('client::general.client', 1) }}</th>
                            <th class="text-right">{{ trans('sacco::general.savings_balance') }}</th>
                            <th class="text-right">{{ trans('sacco::general.share_value') }}</th>
                            <th class="text-right">{{ trans('sacco::general.loan_balance') }}</th>
                            <th class="text-right">{{ trans('sacco::general.wallet_balance') }}</th>
                            <th class="text-right">{{ trans('sacco::general.arrears') }}</th>
                            <th class="text-right">{{ trans('sacco::general.net_position') }}</th>
                            <th>KYC</th>
                            <th class="text-right">{{ trans_choice('core::general.action', 1) }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($data as $member)
                            @php
                                $s = $summaries[$member->id] ?? null;
                                $kycClass = ($s['kyc_status'] ?? '') === 'verified' ? 'badge-status-active' : ((in_array($s['kyc_status'] ?? '', ['pending_review', 'pending'])) ? 'badge-status-pending' : 'badge-status-inactive');
                                $net = $s['net_position'] ?? 0;
                                $initials = strtoupper(substr($member->first_name ?? '', 0, 1) . substr($member->last_name ?? '', 0, 1));
                            @endphp
                            <tr>
                                <td>
                                    <div class="member-cell">
                                        @if(!empty($member->photo))
                                            <img class="member-avatar" src="{{ asset('storage/uploads/' . $member->photo) }}" alt="">
                                        @else
                                            <div class="member-avatar-placeholder">{{ $initials ?: '?' }}</div>
                                        @endif
                                        <div>
                                            <a href="{{ url('client/' . $member->id . '/member360') }}" class="member-name d-block">
                                                {{ trim($member->first_name . ' ' . $member->last_name) }}
                                            </a>
                                            <span class="member-sub">
                                                {{ $s['member_number'] ?? $member->member_number ?? $member->external_id ?? '#' . $member->id }}
                                                @if($member->branch) · {{ $member->branch }} @endif
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-right font-weight-bold">{{ number_format($s['savings_balance'] ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($s['share_value'] ?? 0, 2) }}</td>
                                <td class="text-right text-danger">{{ number_format($s['loan_balance'] ?? 0, 2) }}</td>
                                <td class="text-right">{{ number_format($s['wallet_balance'] ?? 0, 2) }}</td>
                                <td class="text-right">
                                    @if(($s['arrears_amount'] ?? 0) > 0)
                                        <span class="text-danger font-weight-bold">{{ number_format($s['arrears_amount'], 2) }}</span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-right font-weight-bold {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($net, 2) }}
                                </td>
                                <td>
                                    <span class="badge badge-status {{ $kycClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $s['kyc_status'] ?? 'incomplete')) }}
                                    </span>
                                </td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ url('client/' . $member->id . '/member360') }}" class="btn btn-sacco-accent btn-action-sm" title="{{ trans('sacco::general.member_360') }}">
                                        <i class="fas fa-chart-pie"></i>
                                    </a>
                                    <a href="{{ url('client/' . $member->id . '/show') }}" class="btn btn-outline-primary btn-action-sm" title="{{ trans_choice('core::general.detail', 2) }}">
                                        <i class="far fa-user"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-4 text-muted small mb-2 mb-md-0">
                            Showing <strong>{{ $data->firstItem() }}–{{ $data->lastItem() }}</strong> {{ trans_choice('core::general.of', 1) }} <strong>{{ $data->total() }}</strong>
                        </div>
                        <div class="col-md-8 d-flex justify-content-md-end">{{ $data->links() }}</div>
                    </div>
                </div>
            @else
                <div class="list-empty">
                    <i class="fas fa-chart-pie d-block"></i>
                    <h5 class="text-muted">{{ trans('core::general.zeroRecords') }}</h5>
                </div>
            @endif
        </div>
    </section>
@endsection

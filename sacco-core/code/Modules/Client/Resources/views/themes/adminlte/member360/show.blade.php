@extends('core::layouts.master')
@section('title')
    {{ trans('sacco::general.member_360') }} — {{ $client->first_name }} {{ $client->last_name }}
@endsection
@section('content')
    <section class="content admin-list-page member-360-page">
        <div class="list-hero mb-3">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-1">
                        <i class="fas fa-chart-pie mr-2"></i>{{ $client->first_name }} {{ $client->last_name }}
                    </h1>
                    <p class="list-hero-meta mb-0">
                        {{ trans('sacco::general.member_number') }}: <strong>{{ $member360['member_number'] ?? $client->external_id }}</strong>
                        @if($client->branch) · {{ $client->branch->name }} @endif
                        @if($client->loan_officer) · {{ $client->loan_officer->first_name }} {{ $client->loan_officer->last_name }} @endif
                    </p>
                </div>
                <div class="col-md-4 text-md-right mt-2 mt-md-0">
                    @php
                        $kycBadge = ($member360['kyc_status'] ?? '') === 'verified' ? 'success' : (in_array($member360['kyc_status'] ?? '', ['pending_review', 'pending']) ? 'warning' : 'danger');
                    @endphp
                    <span class="badge badge-{{ $kycBadge }} badge-lg px-3 py-2">
                        KYC: {{ ucfirst(str_replace('_', ' ', $member360['kyc_status'] ?? 'incomplete')) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mb-3 d-flex flex-wrap">
            <a href="{{ url('client/member360') }}" class="btn btn-outline-secondary btn-sm mr-2 mb-1">
                <i class="fas fa-arrow-left mr-1"></i>All Members
            </a>
            <a href="{{ url('client/' . $client->id . '/show') }}" class="btn btn-sacco-accent btn-sm mb-1">
                <i class="far fa-user mr-1"></i>Full Profile
            </a>
        </div>

        {{-- Financial KPIs --}}
        <div class="row mb-3">
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi">
                    <div class="m360-kpi-icon"><i class="fas fa-piggy-bank"></i></div>
                    <div class="m360-kpi-value">{{ number_format($member360['savings_balance'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.savings_balance') }}</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi">
                    <div class="m360-kpi-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="m360-kpi-value">{{ number_format($member360['share_value'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.share_value') }}</div>
                    <small class="text-muted">{{ number_format($member360['share_total']) }} shares</small>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi">
                    <div class="m360-kpi-icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <div class="m360-kpi-value text-danger">{{ number_format($member360['loan_balance'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.loan_balance') }}</div>
                    <small class="text-muted">{{ $member360['active_loans'] }} active</small>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi">
                    <div class="m360-kpi-icon"><i class="fas fa-wallet"></i></div>
                    <div class="m360-kpi-value">{{ number_format($member360['wallet_balance'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.wallet_balance') }}</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi {{ ($member360['arrears_amount'] ?? 0) > 0 ? 'm360-kpi-alert' : '' }}">
                    <div class="m360-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="m360-kpi-value">{{ number_format($member360['arrears_amount'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.arrears') }}</div>
                </div>
            </div>
            <div class="col-md-2 col-6 mb-3">
                <div class="m360-kpi m360-kpi-highlight">
                    <div class="m360-kpi-icon"><i class="fas fa-balance-scale"></i></div>
                    <div class="m360-kpi-value {{ $member360['net_position'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($member360['net_position'], 2) }}</div>
                    <div class="m360-kpi-label">{{ trans('sacco::general.net_position') }}</div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- BOSA / FOSA split --}}
            @if(($member360['bosa_balance'] ?? 0) > 0 || ($member360['fosa_balance'] ?? 0) > 0)
            <div class="col-md-6 mb-3">
                <div class="card list-card h-100">
                    <div class="card-header"><i class="fas fa-layer-group mr-1"></i> BOSA / FOSA Split</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6 border-right">
                                <div class="h4 mb-0 text-primary">{{ number_format($member360['bosa_balance'], 2) }}</div>
                                <small class="text-muted">BOSA</small>
                            </div>
                            <div class="col-6">
                                <div class="h4 mb-0 text-primary">{{ number_format($member360['fosa_balance'], 2) }}</div>
                                <small class="text-muted">FOSA</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Guarantor exposure --}}
            <div class="col-md-6 mb-3">
                <div class="card list-card h-100">
                    <div class="card-header"><i class="fas fa-handshake mr-1"></i> Guarantor & Exposure</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted d-block">Guarantor exposure</small>
                                <strong>{{ number_format($member360['guarantor_exposure'], 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Guarantees given</small>
                                <strong>{{ $member360['guarantees_given_count'] }}</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">Locked shares</small>
                                <strong>{{ number_format($member360['share_locked'] ?? 0) }}</strong>
                            </div>
                            <div class="col-6 mt-2">
                                <small class="text-muted d-block">Free shares</small>
                                <strong>{{ number_format($member360['share_free'] ?? 0) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Earmarks & alerts --}}
            @if(!empty($member360['active_earmarks']))
            <div class="col-md-12 mb-3">
                <div class="card list-card">
                    <div class="card-header text-danger"><i class="fas fa-shield-alt mr-1"></i> Active Earmarks</div>
                    <div class="card-body py-2">
                        @foreach($member360['active_earmarks'] as $earmark)
                            <div class="alert alert-{{ $earmark['severity'] === 'critical' ? 'danger' : ($earmark['severity'] === 'warning' ? 'warning' : 'info') }} py-2 mb-2">
                                <strong>{{ $earmark['title'] }}</strong>: {{ $earmark['message'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Cross-sell --}}
            @if(!empty($member360['cross_sell_suggestions']))
            <div class="col-md-6 mb-3">
                <div class="card list-card h-100">
                    <div class="card-header"><i class="fas fa-lightbulb mr-1"></i> Product Suggestions</div>
                    <div class="card-body">
                        @foreach($member360['cross_sell_suggestions'] as $suggestion)
                            <div class="mb-2 pb-2 border-bottom">
                                <strong>{{ $suggestion['title'] ?? $suggestion['product'] ?? 'Suggestion' }}</strong>
                                <p class="text-muted small mb-0">{{ $suggestion['message'] ?? $suggestion['reason'] ?? '' }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Activity timeline --}}
            @if(!empty($timeline))
            <div class="col-md-6 mb-3">
                <div class="card list-card h-100">
                    <div class="card-header"><i class="fas fa-history mr-1"></i> Recent Activity</div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush m360-timeline">
                            @foreach($timeline as $event)
                                <li class="list-group-item py-2 px-3">
                                    <small class="text-muted d-block">{{ \Carbon\Carbon::parse($event->created_at)->diffForHumans() }}</small>
                                    <span class="small">{{ $event->description ?? 'Activity recorded' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            @endif
        </div>

        {{-- Quick actions --}}
        <div class="card list-card">
            <div class="card-header">{{ trans_choice('core::general.action', 2) }}</div>
            <div class="card-body">
                @can('sacco.earmarks.create')
                    <a href="{{ url('sacco/earmark/create?client_id=' . $client->id) }}" class="btn btn-warning btn-sm mr-1 mb-1">Add Earmark</a>
                @endcan
                @can('sacco.transfers.create')
                    <a href="{{ url('sacco/transfer/create?client_id=' . $client->id) }}" class="btn btn-primary btn-sm mr-1 mb-1">BOSA/FOSA Transfer</a>
                @endcan
                @can('sacco.kyc.index')
                    <a href="{{ url('sacco/kyc/' . $client->id . '/show') }}" class="btn btn-info btn-sm mr-1 mb-1">Member KYC</a>
                @endcan
                <a href="{{ url('loan/create?client_id=' . $client->id) }}" class="btn btn-outline-primary btn-sm mr-1 mb-1">New Loan</a>
                <a href="{{ url('savings/create?client_id=' . $client->id) }}" class="btn btn-outline-primary btn-sm mr-1 mb-1">New Savings</a>
            </div>
        </div>
    </section>
@endsection

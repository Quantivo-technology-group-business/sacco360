@extends('core::layouts.master')
@section('title')
    {{ trans_choice('dashboard::general.dashboard', 1) }}
@endsection
@section('styles')
    <style>
        /* portal-welcome styled in admin-brand.css */

        .portal-kpi .small-box {
            margin-bottom: 15px;
        }

        .portal-kpi .small-box .inner h3 {
            font-size: 1.5rem;
        }

        .quick-action-btn {
            margin-bottom: 10px;
        }

        .status-pill {
            font-size: 0.75rem;
            padding: 3px 8px;
        }

        .net-positive { color: #28a745; }
        .net-negative { color: #dc3545; }

        .chart-container {
            min-height: 260px;
        }
    </style>
@endsection
@section('content')
    @php
        $client = $dashboard['client'];
        $loanStats = $dashboard['loan_stats'];
        $savingsStats = $dashboard['savings_stats'];
        $shareStats = $dashboard['share_stats'];
        $member360 = $dashboard['member360'];
        $kycStatus = $member360['kyc_status'] ?? 'unknown';
        $kycBadge = $kycStatus === 'verified' ? 'success' : (in_array($kycStatus, ['pending_review', 'pending']) ? 'warning' : 'secondary');
    @endphp

    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ trans_choice('dashboard::general.dashboard', 1) }}</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item active">{{ trans_choice('dashboard::general.dashboard', 1) }}</li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        {{-- Welcome banner --}}
        <div class="portal-welcome">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2>Welcome, {{ $client->first_name ?? '' }} {{ $client->last_name ?? '' }}</h2>
                    <div class="meta">
                        @if(!empty($member360['member_number']))
                            Member #{{ $member360['member_number'] }} &bull;
                        @endif
                        @if($client->branch)
                            {{ $client->branch->name ?? '' }} &bull;
                        @endif
                        <span class="badge badge-{{ $kycBadge }} status-pill">KYC: {{ ucfirst(str_replace('_', ' ', $kycStatus)) }}</span>
                        @if($kycStatus !== 'verified')
                        <a href="{{ url('portal/sacco/kyc') }}" class="btn btn-xs btn-outline-light ml-2">Complete KYC</a>
                        @endif
                    </div>
                </div>
                <div class="col-md-4 text-md-right mt-3 mt-md-0">
                    <div class="h4 mb-0 {{ $dashboard['net_position'] >= 0 ? 'net-positive' : 'net-negative' }}">
                        Net Position: {{ number_format($dashboard['net_position'], 2) }}
                    </div>
                    <small>Total Assets: {{ number_format($dashboard['total_assets'], 2) }}</small>
                </div>
            </div>
        </div>

        {{-- Key metrics --}}
        <div class="row portal-kpi">
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ number_format($loanStats['total_loans']) }}</h3>
                        <p>{{ trans_choice('loan::general.loan', 2) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
                    <a href="{{ url('portal/loan') }}" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>{{ number_format($loanStats['disbursed_amount']) }}</h3>
                        <p>{{ trans_choice('loan::general.disbursed', 2) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>{{ number_format($loanStats['total_outstanding']) }}</h3>
                        <p>{{ trans_choice('loan::general.outstanding', 2) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-balance-scale"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-{{ $loanStats['total_arrears'] > 0 ? 'danger' : 'secondary' }}">
                    <div class="inner">
                        <h3>{{ number_format($loanStats['total_arrears']) }}</h3>
                        <p>{{ trans_choice('loan::general.arrears', 2) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-teal">
                    <div class="inner">
                        <h3>{{ number_format($savingsStats['total_balance']) }}</h3>
                        <p>{{ trans_choice('savings::general.savings', 1) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-piggy-bank"></i></div>
                    <a href="{{ url('portal/savings') }}" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6">
                <div class="small-box bg-purple">
                    <div class="inner">
                        <h3>{{ number_format($shareStats['share_value']) }}</h3>
                        <p>{{ trans_choice('share::general.share', 2) }}</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-pie"></i></div>
                    <a href="{{ url('portal/share') }}" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>

        @if($member360)
        <div class="row">
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-primary"><i class="fas fa-landmark"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('sacco::general.bosa_balance') }}</span>
                        <span class="info-box-number">{{ number_format($member360['bosa_balance'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-teal"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans('sacco::general.fosa_balance') }}</span>
                        <span class="info-box-number">{{ number_format($member360['fosa_balance'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-indigo"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Wallet Balance</span>
                        <span class="info-box-number">{{ number_format($member360['wallet_balance'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-orange"><i class="fas fa-handshake"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Guarantor Exposure</span>
                        <span class="info-box-number">{{ number_format($member360['guarantor_exposure'] ?? 0, 2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="info-box">
                    <span class="info-box-icon bg-olive"><i class="fas fa-lock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Locked Shares</span>
                        <span class="info-box-number">{{ number_format($member360['share_locked'] ?? 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <a href="{{ url('portal/sacco/earmarks') }}" class="text-decoration-none">
                <div class="info-box">
                    <span class="info-box-icon bg-{{ ($member360['has_blocking_earmark'] ?? false) ? 'danger' : 'success' }}"><i class="fas fa-shield-alt"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Active Earmarks</span>
                        <span class="info-box-number">{{ count($member360['active_earmarks'] ?? []) }}</span>
                    </div>
                </div>
                </a>
            </div>
        </div>
        @endif

        <div class="row">
            {{-- Quick actions --}}
            <div class="col-lg-3 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ url('portal/loan/application/create') }}" class="btn btn-primary btn-block quick-action-btn">
                            <i class="fas fa-file-alt"></i> Apply for Loan
                        </a>
                        <a href="{{ url('portal/loan') }}" class="btn btn-success btn-block quick-action-btn">
                            <i class="fas fa-list"></i> My Loans
                        </a>
                        <a href="{{ url('portal/savings') }}" class="btn btn-info btn-block quick-action-btn">
                            <i class="fas fa-piggy-bank"></i> My Savings
                        </a>
                        @if($loanStats['active_count'] > 0)
                            @php $firstActive = $dashboard['active_loans']->first(); @endphp
                            @if($firstActive)
                                <a href="{{ url('portal/loan/' . $firstActive->id . '/repayment/create') }}" class="btn btn-warning btn-block quick-action-btn">
                                    <i class="fas fa-coins"></i> Make Repayment
                                </a>
                            @endif
                        @endif
                        <a href="{{ url('portal/share') }}" class="btn btn-secondary btn-block quick-action-btn">
                            <i class="fas fa-chart-pie"></i> My Shares
                        </a>
                        @if(class_exists(\Modules\Investment\Entities\InvestmentPlan::class))
                        <a href="{{ url('portal/investment') }}" class="btn btn-outline-purple btn-block quick-action-btn"
                           style="border-color:#7c3aed;color:#7c3aed">
                            <i class="fas fa-chart-line"></i> My Investments
                        </a>
                        <a href="{{ url('portal/investment/plans') }}" class="btn btn-outline-secondary btn-block quick-action-btn">
                            <i class="fas fa-star"></i> Browse Plans
                        </a>
                        @endif
                        @if(class_exists(\Modules\Sacco\Services\ShareMarketplaceService::class))
                        <a href="{{ url('portal/share/marketplace') }}" class="btn btn-outline-secondary btn-block quick-action-btn">
                            <i class="fas fa-store"></i> {{ trans('sacco::share_marketplace.marketplace') }}
                        </a>
                        @endif
                        @if(class_exists(\Modules\Sacco\Services\WingService::class))
                        <a href="{{ url('portal/sacco/accounts') }}" class="btn btn-outline-primary btn-block quick-action-btn">
                            <i class="fas fa-wallet"></i> {{ trans('sacco::general.my_accounts') }}
                        </a>
                        <a href="{{ url('portal/sacco/utility/create') }}" class="btn btn-outline-warning btn-block quick-action-btn">
                            <i class="fas fa-bolt"></i> {{ trans('sacco::general.pay_utility') }}
                        </a>
                        <a href="{{ url('portal/sacco/member-transfer/create') }}" class="btn btn-outline-info btn-block quick-action-btn">
                            <i class="fas fa-users"></i> {{ trans('sacco::general.member_transfer') }}
                        </a>
                        <a href="{{ url('portal/sacco/transfer') }}" class="btn btn-outline-primary btn-block quick-action-btn">
                            <i class="fas fa-exchange-alt"></i> {{ trans_choice('sacco::general.bosa_fosa_transfer', 1) }}
                        </a>
                        <a href="{{ url('portal/sacco/deposit') }}" class="btn btn-outline-success btn-block quick-action-btn">
                            <i class="fas fa-mobile-alt"></i> {{ trans('sacco::general.deposit_to_fosa') }}
                        </a>
                        <a href="{{ url('portal/sacco/kyc') }}" class="btn btn-outline-info btn-block quick-action-btn">
                            <i class="fas fa-id-card"></i> {{ trans('sacco::general.member_kyc') }}
                        </a>
                        @endif
                    </div>
                </div>

                @if(!empty($member360['cross_sell_suggestions']))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans('sacco::general.cross_sell_suggestions') }}</h3>
                    </div>
                    <div class="card-body">
                        @foreach($member360['cross_sell_suggestions'] as $suggestion)
                            <p class="mb-2"><strong>{{ $suggestion['campaign'] }}</strong><br>{{ $suggestion['message'] }}</p>
                            @if(($suggestion['target'] ?? '') === 'bosa')
                                <form method="post" action="{{ url('portal/sacco/open-bosa') }}" class="mb-3">@csrf
                                    <button type="submit" class="btn btn-sm btn-primary">{{ trans('sacco::general.open_bosa_account') }}</button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Loan status breakdown --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans_choice('loan::general.loan', 1) }} {{ trans_choice('core::general.status', 1) }}</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('loan::general.active', 2) }}</span>
                                <span class="badge badge-success">{{ $loanStats['active_count'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('loan::general.pending', 1) }}</span>
                                <span class="badge badge-warning">{{ $loanStats['pending_count'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('loan::general.closed', 2) }}</span>
                                <span class="badge badge-secondary">{{ $loanStats['closed_count'] }}</span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('loan::general.total', 1) }} {{ trans_choice('loan::general.repayment', 2) }}</span>
                                <strong>{{ number_format($loanStats['total_repaid']) }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Savings summary --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans_choice('savings::general.savings', 1) }} Summary</h3>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('savings::general.account', 2) }}</span>
                                <strong>{{ $savingsStats['total_accounts'] }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('savings::general.deposit', 2) }}</span>
                                <strong class="text-success">{{ number_format($savingsStats['total_deposits']) }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('savings::general.withdrawal', 2) }}</span>
                                <strong class="text-danger">{{ number_format($savingsStats['total_withdrawals']) }}</strong>
                            </li>
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ trans_choice('savings::general.interest', 1) }}</span>
                                <strong>{{ number_format($savingsStats['total_interest']) }}</strong>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="col-lg-5 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans_choice('loan::general.repayment', 2) }} History</h3>
                    </div>
                    <div class="card-body chart-container">
                        <div id="repayment_chart"></div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans_choice('savings::general.savings', 1) }} Activity</h3>
                    </div>
                    <div class="card-body chart-container">
                        <div id="savings_chart"></div>
                    </div>
                </div>
            </div>

            {{-- Upcoming & overdue --}}
            <div class="col-lg-4 col-md-12">
                @if($dashboard['overdue_repayments']->count() > 0)
                <div class="card card-danger card-outline">
                    <div class="card-header">
                        <h3 class="card-title text-danger"><i class="fas fa-exclamation-circle"></i> Overdue Payments</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>{{ trans_choice('loan::general.loan', 1) }}</th>
                                <th>{{ trans_choice('core::general.date', 1) }}</th>
                                <th class="text-right">{{ trans_choice('loan::general.amount', 1) }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($dashboard['overdue_repayments'] as $schedule)
                                <tr>
                                    <td>
                                        <a href="{{ url('portal/loan/' . $schedule->loan_id . '/show') }}">#{{ $schedule->loan_id }}</a>
                                        <br><small class="text-muted">{{ $schedule->loan_product }}</small>
                                    </td>
                                    <td class="text-danger">{{ \Carbon\Carbon::parse($schedule->due_date)->format('M d, Y') }}</td>
                                    <td class="text-right">{{ number_format($schedule->total_due, 2) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Upcoming Payments</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                            <tr>
                                <th>{{ trans_choice('loan::general.loan', 1) }}</th>
                                <th>Due Date</th>
                                <th class="text-right">{{ trans_choice('loan::general.amount', 1) }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dashboard['upcoming_repayments'] as $schedule)
                                <tr>
                                    <td>
                                        <a href="{{ url('portal/loan/' . $schedule->loan_id . '/show') }}">#{{ $schedule->loan_id }}</a>
                                        <br><small class="text-muted">{{ $schedule->loan_product }}</small>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($schedule->due_date)->format('M d, Y') }}</td>
                                    <td class="text-right">{{ number_format($schedule->total_due, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No upcoming payments</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active loans & recent transactions --}}
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">{{ trans_choice('loan::general.active', 2) }} {{ trans_choice('loan::general.loan', 2) }}</h3>
                        <div class="card-tools">
                            <a href="{{ url('portal/loan') }}" class="btn btn-sm btn-default">View All</a>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ trans_choice('loan::general.product', 1) }}</th>
                                <th>{{ trans_choice('loan::general.amount', 1) }}</th>
                                <th>{{ trans_choice('core::general.status', 1) }}</th>
                                <th>Maturity</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dashboard['active_loans'] as $loan)
                                <tr>
                                    <td><a href="{{ url('portal/loan/' . $loan->id . '/show') }}">{{ $loan->id }}</a></td>
                                    <td>{{ $loan->loan_product->name ?? '-' }}</td>
                                    <td>{{ number_format($loan->principal) }}</td>
                                    <td><span class="badge badge-success">{{ ucfirst($loan->status) }}</span></td>
                                    <td>{{ $loan->expected_maturity_date ? \Carbon\Carbon::parse($loan->expected_maturity_date)->format('M d, Y') : '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No active loans</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Loan Transactions</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                            <tr>
                                <th>{{ trans_choice('core::general.date', 1) }}</th>
                                <th>{{ trans_choice('loan::general.loan', 1) }}</th>
                                <th class="text-right">{{ trans_choice('loan::general.amount', 1) }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dashboard['recent_loan_transactions'] as $txn)
                                <tr>
                                    <td>{{ $txn->submitted_on ? \Carbon\Carbon::parse($txn->submitted_on)->format('M d, Y') : '-' }}</td>
                                    <td><a href="{{ url('portal/loan/transaction/' . $txn->id . '/show') }}">#{{ $txn->loan_id }}</a></td>
                                    <td class="text-right">{{ number_format($txn->amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No transactions yet</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Savings Transactions</h3>
                    </div>
                    <div class="card-body table-responsive p-0">
                        <table class="table table-hover table-striped mb-0">
                            <thead>
                            <tr>
                                <th>{{ trans_choice('core::general.date', 1) }}</th>
                                <th>Type</th>
                                <th class="text-right">{{ trans_choice('loan::general.amount', 1) }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($dashboard['recent_savings_transactions'] as $txn)
                                <tr>
                                    <td>{{ $txn->submitted_on ? \Carbon\Carbon::parse($txn->submitted_on)->format('M d, Y') : '-' }}</td>
                                    <td>
                                        @if($txn->credit > 0)
                                            <span class="badge badge-success">Deposit</span>
                                        @else
                                            <span class="badge badge-warning">Withdrawal</span>
                                        @endif
                                    </td>
                                    <td class="text-right">{{ number_format($txn->credit > 0 ? $txn->credit : $txn->debit, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No transactions yet</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Investment Section ─────────────────────────────────────────────── --}}
        @if(!empty($dashboard['investment_stats']) && class_exists(\Modules\Investment\Entities\InvestmentPlan::class))
        @php $invStats = $dashboard['investment_stats']; $avPlans = $dashboard['available_plans']; @endphp

        {{-- KPI row --}}
        @if($invStats['count'] > 0 || $invStats['pending_count'] > 0)
        <div class="row mt-2 mb-1">
            <div class="col-12">
                <h5 class="font-weight-bold" style="color:#7c3aed">
                    <i class="fas fa-chart-line mr-2"></i>My Investments
                </h5>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="info-box" style="border-left:4px solid #7c3aed">
                    <span class="info-box-icon" style="background:linear-gradient(135deg,#6d28d9,#7c3aed)"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Invested</span>
                        <span class="info-box-number">{{ number_format($invStats['total_invested'],2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-success"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Profit Accrued</span>
                        <span class="info-box-number">{{ number_format($invStats['total_profit_accrued'],2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Profit Due</span>
                        <span class="info-box-number">{{ number_format($invStats['profit_due'],2) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="info-box">
                    <span class="info-box-icon" style="background:linear-gradient(135deg,#0f766e,#14b8a6)"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Current Value</span>
                        <span class="info-box-number">{{ number_format($invStats['current_value'],2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- My active investments list --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-wallet mr-1" style="color:#7c3aed"></i> My Investment Accounts
                        </h5>
                        <div class="card-tools">
                            <a href="{{ url('portal/investment') }}" class="btn btn-xs btn-outline-secondary">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Account #</th><th>Plan</th>
                                    <th class="text-right">Principal</th>
                                    <th class="text-right">Profit Accrued</th>
                                    <th class="text-right">Current Value</th>
                                    <th>Maturity</th><th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($invStats['investments']->take(5) as $inv)
                            <tr>
                                <td>
                                    <a href="{{ url('portal/investment/'.$inv->id) }}" style="color:#7c3aed;font-weight:600">
                                        {{ $inv->account_number }}
                                    </a>
                                </td>
                                <td>{{ optional($inv->plan)->name }}</td>
                                <td class="text-right">{{ number_format($inv->principal_amount,2) }}</td>
                                <td class="text-right text-success">{{ number_format($inv->total_profit_accrued,2) }}</td>
                                <td class="text-right font-weight-bold">
                                    {{ number_format((float)$inv->principal_amount + (float)$inv->total_profit_accrued, 2) }}
                                </td>
                                <td>
                                    @if($inv->maturity_date)
                                        @if($inv->maturity_date->isPast())
                                            <span class="text-danger font-weight-bold">{{ $inv->maturity_date->format('d M Y') }} ⚠</span>
                                        @else
                                            {{ $inv->maturity_date->format('d M Y') }}
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{!! $inv->status_badge !!}</td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="text-center text-muted py-2">No investments yet.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Available Investment Plans --}}
        @if($avPlans->count() > 0)
        <div class="row mt-2 mb-1">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <h5 class="font-weight-bold mb-0" style="color:#7c3aed">
                    <i class="fas fa-star mr-2"></i>Available Investment Plans
                </h5>
                <a href="{{ url('portal/investment/plans') }}" class="btn btn-sm" style="border-color:#7c3aed;color:#7c3aed;border-width:1.5px">
                    View All Plans
                </a>
            </div>
        </div>
        <div class="row">
            @foreach($avPlans->take(4) as $plan)
            @php
                $colours = ['fixed_deposit'=>'#3b82f6','bond'=>'#0891b2','unit_trust'=>'#16a34a',
                            'equity'=>'#d97706','real_estate'=>'#dc2626','money_market'=>'#14b8a6','other'=>'#6b7280'];
                $planColor = $colours[$plan->plan_type] ?? '#6b7280';
                $icons = ['fixed_deposit'=>'fa-piggy-bank','bond'=>'fa-file-contract','unit_trust'=>'fa-layer-group',
                          'equity'=>'fa-chart-bar','real_estate'=>'fa-building','money_market'=>'fa-money-bill-wave','other'=>'fa-star'];
                $planIcon = $icons[$plan->plan_type] ?? 'fa-star';
            @endphp
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="card h-100 mb-0" style="border-radius:14px;border:2px solid {{$planColor}}20;
                     transition:all .2s;overflow:hidden">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center mb-3">
                            <div style="width:44px;height:44px;border-radius:12px;
                                        background:{{$planColor}};display:flex;align-items:center;
                                        justify-content:center;color:#fff;font-size:1.2rem;flex-shrink:0">
                                <i class="fas {{$planIcon}}"></i>
                            </div>
                            <div class="ml-2">
                                <div style="font-size:.72rem;color:#64748b;font-weight:700;text-transform:uppercase;letter-spacing:.05em">
                                    {{ $plan->plan_type_label }}
                                </div>
                                <div style="font-weight:800;font-size:.88rem;color:#1e293b;line-height:1.2">
                                    {{ $plan->name }}
                                </div>
                            </div>
                        </div>
                        <div class="text-center my-3">
                            <div style="font-size:2rem;font-weight:900;color:{{$planColor}};line-height:1">
                                @if($plan->return_rate_type==='tiered') Tiered @else {{ $plan->return_rate }}% @endif
                            </div>
                            <div style="font-size:.7rem;color:#94a3b8;font-weight:600">ANNUAL RETURN</div>
                        </div>
                        <div style="font-size:.72rem;color:#64748b" class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Min. Investment</span>
                                <span class="font-weight-bold">{{ number_format($plan->min_amount,2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Duration</span>
                                <span class="font-weight-bold">
                                    {{ $plan->min_duration_months }}@if($plan->max_duration_months)–{{ $plan->max_duration_months }}@endif months
                                </span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Method</span>
                                <span class="font-weight-bold">{{ ucfirst($plan->interest_method) }}</span>
                            </div>
                        </div>
                        <a href="{{ url('portal/investment/plans/'.$plan->id) }}"
                           class="btn btn-block btn-sm font-weight-bold"
                           style="background:{{$planColor}};color:#fff;border-radius:8px">
                            <i class="fas fa-info-circle mr-1"></i>View & Invest
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @endif
        {{-- ── End Investment Section ──────────────────────────────────────────── --}}

    </section>
@endsection
@section('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highcharts/6.0.6/highcharts.js"></script>
    <script>
        Highcharts.chart('repayment_chart', {
            chart: { type: 'column' },
            title: { text: null },
            xAxis: { categories: @json($dashboard['repayment_chart']['labels']) },
            yAxis: { title: { text: 'Amount' } },
            series: [{
                name: 'Repayments',
                data: @json($dashboard['repayment_chart']['amounts']),
                color: '#28a745'
            }],
            credits: { enabled: false },
            legend: { enabled: false }
        });

        Highcharts.chart('savings_chart', {
            chart: { type: 'column' },
            title: { text: null },
            xAxis: { categories: @json($dashboard['savings_chart']['labels']) },
            yAxis: { title: { text: 'Amount' } },
            series: [{
                name: 'Deposits',
                data: @json($dashboard['savings_chart']['deposits']),
                color: '#17a2b8'
            }, {
                name: 'Withdrawals',
                data: @json($dashboard['savings_chart']['withdrawals']),
                color: '#dc3545'
            }],
            credits: { enabled: false }
        });
    </script>
@endsection

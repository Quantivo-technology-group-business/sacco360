@extends('core::layouts.master')
@section('title')
    {{ trans_choice('dashboard::general.dashboard', 1) }}
@endsection
@section('content')
    @php
        $client = $dashboard['client'];
        $loanStats = $dashboard['loan_stats'];
        $savingsStats = $dashboard['savings_stats'];
        $shareStats = $dashboard['share_stats'];
        $member360 = $dashboard['member360'];
        $kycStatus = $member360['kyc_status'] ?? 'unknown';
        $kycBadge = $kycStatus === 'verified' ? 'success' : ($kycStatus === 'pending' ? 'warning' : 'default');
    @endphp

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="callout callout-info">
                <h4>Welcome, {{ $client->first_name ?? '' }} {{ $client->last_name ?? '' }}</h4>
                <p>
                    Net Position: <strong>{{ number_format($dashboard['net_position'], 2) }}</strong>
                    @if($member360)
                        &bull; <span class="label label-{{ $kycBadge }}">KYC: {{ ucfirst(str_replace('_', ' ', $kycStatus)) }}</span>
                        @if($kycStatus !== 'verified')
                            <a href="{{ url('portal/sacco/kyc') }}" class="btn btn-xs btn-default">Complete KYC</a>
                        @endif
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans_choice('loan::general.total', 1) }} {{ trans_choice('loan::general.loan', 2) }}</span>
                    <span class="info-box-number">{{ number_format($loanStats['total_loans']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-green"><i class="fa fa-money"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans_choice('loan::general.outstanding', 2) }}</span>
                    <span class="info-box-number">{{ number_format($loanStats['total_outstanding']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-red"><i class="fa fa-warning"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans_choice('loan::general.arrears', 2) }}</span>
                    <span class="info-box-number">{{ number_format($loanStats['total_arrears']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-bank"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans_choice('savings::general.savings', 1) }} {{ trans_choice('savings::general.balance', 1) }}</span>
                    <span class="info-box-number">{{ number_format($savingsStats['total_balance']) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-yellow"><i class="fa fa-pie-chart"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans_choice('share::general.share', 2) }}</span>
                    <span class="info-box-number">{{ number_format($shareStats['share_value']) }}</span>
                </div>
            </div>
        </div>
        @if($member360)
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-blue"><i class="fa fa-university"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans('sacco::general.bosa_balance') }}</span>
                    <span class="info-box-number">{{ number_format($member360['bosa_balance'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-aqua"><i class="fa fa-credit-card"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans('sacco::general.fosa_balance') }}</span>
                    <span class="info-box-number">{{ number_format($member360['fosa_balance'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="info-box">
                <span class="info-box-icon bg-purple"><i class="fa fa-balance-scale"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">{{ trans('sacco::general.net_position') }}</span>
                    <span class="info-box-number">{{ number_format($member360['net_position'] ?? 0, 2) }}</span>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header"><h3 class="box-title">Upcoming Payments</h3></div>
                <div class="box-body table-responsive no-padding">
                    <table class="table table-hover">
                        <thead><tr><th>Loan</th><th>Due</th><th>Amount</th></tr></thead>
                        <tbody>
                        @forelse($dashboard['upcoming_repayments'] as $schedule)
                            <tr>
                                <td><a href="{{ url('portal/loan/' . $schedule->loan_id . '/show') }}">#{{ $schedule->loan_id }}</a></td>
                                <td>{{ \Carbon\Carbon::parse($schedule->due_date)->format('M d, Y') }}</td>
                                <td>{{ number_format($schedule->total_due, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center">No upcoming payments</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box box-success">
                <div class="box-header"><h3 class="box-title">Quick Actions</h3></div>
                <div class="box-body">
                    <a href="{{ url('portal/loan/application/create') }}" class="btn btn-primary btn-block">Apply for Loan</a>
                    <a href="{{ url('portal/loan') }}" class="btn btn-success btn-block">My Loans</a>
                    <a href="{{ url('portal/savings') }}" class="btn btn-info btn-block">My Savings</a>
                    @if(class_exists(\Modules\Sacco\Services\WingService::class))
                    <a href="{{ url('portal/sacco/accounts') }}" class="btn btn-default btn-block">{{ trans('sacco::general.my_accounts') }}</a>
                    <a href="{{ url('portal/sacco/utility/create') }}" class="btn btn-default btn-block">{{ trans('sacco::general.pay_utility') }}</a>
                    <a href="{{ url('portal/sacco/member-transfer/create') }}" class="btn btn-default btn-block">{{ trans('sacco::general.member_transfer') }}</a>
                    <a href="{{ url('portal/sacco/transfer') }}" class="btn btn-default btn-block">{{ trans_choice('sacco::general.bosa_fosa_transfer', 1) }}</a>
                    <a href="{{ url('portal/sacco/deposit') }}" class="btn btn-default btn-block">{{ trans('sacco::general.deposit_to_fosa') }}</a>
                    <a href="{{ url('portal/sacco/kyc') }}" class="btn btn-default btn-block">{{ trans('sacco::general.member_kyc') }}</a>
                    @endif
                </div>
            </div>
            @if(!empty($member360['cross_sell_suggestions']))
            <div class="box box-warning">
                <div class="box-header"><h3 class="box-title">{{ trans('sacco::general.cross_sell_suggestions') }}</h3></div>
                <div class="box-body">
                    @foreach($member360['cross_sell_suggestions'] as $suggestion)
                        <p><strong>{{ $suggestion['campaign'] }}</strong><br>{{ $suggestion['message'] }}</p>
                        @if(($suggestion['target'] ?? '') === 'bosa')
                            <form method="post" action="{{ url('portal/sacco/open-bosa') }}">@csrf
                                <button type="submit" class="btn btn-sm btn-primary">{{ trans('sacco::general.open_bosa_account') }}</button>
                            </form>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
@stop

<div class="grid-stack-item dashboard_summary"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="DashboardSummary">
    <div class="grid-stack-item-content" style="overflow-y: auto;">
        <div class="p-2">
            <div class="row no-gutters">

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-primary"><i class="fas fa-building"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('branch::general.branch', 2) }}</span>
                            <span class="info-box-number">{{ $summary['branches'] }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('client::general.client', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['clients']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-success"><i class="fas fa-hand-holding-usd"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('loan::general.active', 1) }} {{ trans_choice('loan::general.loan', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['active_loans']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-teal"><i class="fas fa-money-bill-wave"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('loan::general.disbursed', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['disbursed'] / 1000000, 1) }}M</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-warning"><i class="fas fa-balance-scale"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('loan::general.outstanding', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['outstanding'] / 1000000, 1) }}M</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon {{ $summary['par_30'] > 5 ? 'bg-danger' : 'bg-orange' }}">
                            <i class="fas fa-exclamation-triangle"></i>
                        </span>
                        <div class="info-box-content">
                            <span class="info-box-text">PAR 30</span>
                            <span class="info-box-number" style="color: {{ $summary['par_30'] > 5 ? '#dc2626' : '#ea580c' }} !important">
                                {{ $summary['par_30'] }}%
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-purple"><i class="fas fa-piggy-bank"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('savings::general.savings', 1) }}</span>
                            <span class="info-box-number">{{ number_format($summary['savings_balance'] / 1000000, 1) }}M</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-indigo"><i class="fas fa-university"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('savings::general.account', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['savings_accounts']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-olive"><i class="fas fa-coins"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('loan::general.today', 1) }} {{ trans_choice('loan::general.collection', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['collections_today']) }}</span>
                        </div>
                    </div>
                </div>

                <div class="col-xl col-lg-2 col-md-3 col-6 mb-2 pr-1 pl-1">
                    <div class="info-box mb-0">
                        <span class="info-box-icon bg-navy"><i class="fas fa-chart-line"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">{{ trans_choice('loan::general.monthly', 1) }} {{ trans_choice('loan::general.collection', 2) }}</span>
                            <span class="info-box-number">{{ number_format($summary['collections_month']) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

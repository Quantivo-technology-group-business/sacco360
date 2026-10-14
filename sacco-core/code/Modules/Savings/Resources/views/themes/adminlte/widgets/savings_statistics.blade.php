<div class="grid-stack-item savings_statistics"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="SavingsStatistics">
    <div class="grid-stack-item-content">
        <div class="row h-100">
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-purple mb-0">
                    <span class="info-box-icon"><i class="fas fa-piggy-bank"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('savings::general.savings', 2) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total_accounts']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-success mb-0">
                    <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('core::general.active', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['active_accounts']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-info mb-0">
                    <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('savings::general.balance', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total_balance']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-teal mb-0">
                    <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('savings::general.deposit', 2) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total_deposits']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-orange mb-0">
                    <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('savings::general.withdrawal', 2) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total_withdrawals']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-yellow mb-0">
                    <span class="info-box-icon"><i class="fas fa-percent"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('savings::general.interest', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total_interest']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid-stack-item client_statistics"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="ClientStatistics">
    <div class="grid-stack-item-content">
        <div class="row h-100">
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-info mb-0">
                    <span class="info-box-icon"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('client::general.client', 2) }}</span>
                        <span class="info-box-number">{{ number_format($stats['total']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-success mb-0">
                    <span class="info-box-icon"><i class="fas fa-user-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('core::general.active', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['active']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-secondary mb-0">
                    <span class="info-box-icon"><i class="fas fa-user-slash"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('core::general.inactive', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['inactive']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-warning mb-0">
                    <span class="info-box-icon"><i class="fas fa-user-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ trans_choice('loan::general.pending', 1) }}</span>
                        <span class="info-box-number">{{ number_format($stats['pending']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-primary mb-0">
                    <span class="info-box-icon"><i class="fas fa-user-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">New (Month)</span>
                        <span class="info-box-number">{{ number_format($stats['new_this_month']) }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-2 col-4 mb-2">
                <div class="info-box bg-teal mb-0">
                    <span class="info-box-icon"><i class="fas fa-calendar-plus"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">New (Year)</span>
                        <span class="info-box-number">{{ number_format($stats['new_this_year']) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

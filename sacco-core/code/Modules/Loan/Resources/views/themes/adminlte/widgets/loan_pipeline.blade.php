<div class="grid-stack-item loan_pipeline"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="LoanPipeline">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">{{ trans_choice('loan::general.loan', 1) }} {{ trans_choice('loan::general.pipeline', 1) }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-box-tool" data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-warning mb-0">
                            <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.pending_approval', 1) }}</span>
                                <span class="info-box-number">{{ $pipeline['pending'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-info mb-0">
                            <span class="info-box-icon"><i class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.awaiting_disbursement', 1) }}</span>
                                <span class="info-box-number">{{ $pipeline['approved'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-success mb-0">
                            <span class="info-box-icon"><i class="fas fa-play"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.active', 2) }}</span>
                                <span class="info-box-number">{{ $pipeline['active'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-secondary mb-0">
                            <span class="info-box-icon"><i class="fas fa-flag-checkered"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.closed', 2) }}</span>
                                <span class="info-box-number">{{ $pipeline['closed'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-danger mb-0">
                            <span class="info-box-icon"><i class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.written_off', 2) }}</span>
                                <span class="info-box-number">{{ $pipeline['written_off'] }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-4 mb-2">
                        <div class="info-box bg-primary mb-0">
                            <span class="info-box-icon"><i class="fas fa-file-alt"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">{{ trans_choice('loan::general.application', 2) }}</span>
                                <span class="info-box-number">{{ $pipeline['applications'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-2">
                    <small class="text-muted">{{ trans_choice('loan::general.pending', 1) }} {{ trans_choice('loan::general.amount', 1) }}: <strong>{{ number_format($pipeline['pending_amount']) }}</strong></small>
                </div>
            </div>
        </div>
    </div>
</div>

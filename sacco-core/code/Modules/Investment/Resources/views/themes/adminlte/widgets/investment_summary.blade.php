<div class="grid-stack-item" gs-w="{{$config['width']??12}}" gs-h="{{$config['height']??2}}"
     gs-x="{{$config['x']??0}}" gs-y="{{$config['y']??0}}" gs-id="{{$config['id']??''}}">
    <div class="grid-stack-item-content">
        <div class="card h-100 mb-0">
            <div class="card-header">
                <span class="card-title">
                    <i class="fas fa-chart-line mr-1" style="color:#6d28d9"></i> Investments Overview
                </span>
                <div class="card-tools">
                    <a href="{{url('investment')}}" class="btn btn-xs btn-outline-secondary">View All</a>
                    @can('dashboard.destroy')
                    <a class="btn btn-xs btn-outline-danger trash-item ml-1 confirm"
                       href="{{url('dashboard/'.($config['id']??'').'/destroy')}}">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                    @endcan
                </div>
            </div>
            <div class="card-body p-2">
                <div class="row">
                    <div class="col-6 col-md-3 mb-2">
                        <div class="info-box mb-0" style="min-height:70px">
                            <span class="info-box-icon" style="background:linear-gradient(135deg,#6d28d9,#7c3aed)">
                                <i class="fas fa-wallet"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Total Invested</span>
                                <span class="info-box-number">{{number_format($stats['total_invested'],2)}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="info-box mb-0" style="min-height:70px">
                            <span class="info-box-icon bg-success">
                                <i class="fas fa-chart-line"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Profit Accrued</span>
                                <span class="info-box-number">{{number_format($stats['total_profit_accrued'],2)}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="info-box mb-0" style="min-height:70px">
                            <span class="info-box-icon bg-warning">
                                <i class="fas fa-clock"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Profit Due</span>
                                <span class="info-box-number">{{number_format($stats['profit_due'],2)}}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3 mb-2">
                        <div class="info-box mb-0" style="min-height:70px">
                            <span class="info-box-icon bg-primary">
                                <i class="fas fa-users"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text">Active</span>
                                <span class="info-box-number">{{$stats['active_count']}}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-1">
                    <div class="col-md-3 text-center">
                        <small class="text-muted">Plans Available</small>
                        <div class="font-weight-bold">{{$stats['plans_count']}}</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">Pending Activation</small>
                        <div class="font-weight-bold text-warning">{{$stats['pending_count']}}</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">Maturing (30 days)</small>
                        <div class="font-weight-bold text-danger">{{$stats['maturing_soon']}}</div>
                    </div>
                    <div class="col-md-3 text-center">
                        <small class="text-muted">Profit Paid Out</small>
                        <div class="font-weight-bold text-success">{{number_format($stats['total_profit_paid'],2)}}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

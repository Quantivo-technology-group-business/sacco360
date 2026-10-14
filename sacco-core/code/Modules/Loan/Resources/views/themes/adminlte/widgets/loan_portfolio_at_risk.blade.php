<div class="grid-stack-item loan_portfolio_at_risk"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="LoanPortfolioAtRisk">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">{{ trans_choice('loan::general.portfolio_at_risk', 1) }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-box-tool" data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row text-center mb-3">
                    @foreach([1, 7, 30, 90] as $days)
                        <div class="col-3">
                            <div class="description-block border-right">
                                <h5 class="description-header text-{{ $par['par_percentages'][$days] > 5 ? 'danger' : ($par['par_percentages'][$days] > 2 ? 'warning' : 'success') }}">
                                    {{ $par['par_percentages'][$days] }}%
                                </h5>
                                <span class="description-text">PAR {{ $days }}</span>
                                <br><small class="text-muted">{{ number_format($par['at_risk_amounts'][$days]) }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div id="par_chart">{!! $chart->container() !!}</div>
            </div>
        </div>
    </div>
</div>
{!! $chart->script() !!}

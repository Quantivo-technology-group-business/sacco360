<div class="grid-stack-item loan_arrears_aging"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="LoanArrearsAging">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">{{ trans_choice('loan::general.arrears', 1) }} {{ trans_choice('loan::general.aging', 1) }}</h3>
                <div class="card-tools">
                    <span class="badge badge-danger mr-2">{{ trans_choice('loan::general.total', 1) }}: {{ number_format($total_arrears) }}</span>
                    <button type="button" class="btn btn-box-tool" data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                {!! $chart->container() !!}
            </div>
        </div>
    </div>
</div>
{!! $chart->script() !!}

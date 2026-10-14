<div class="grid-stack-item loan_product_mix"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="LoanProductMix">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">{{ trans_choice('loan::general.loan', 1) }} {{ trans_choice('loan::general.product', 1) }} {{ trans_choice('loan::general.mix', 1) }}</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-box-tool" data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">{!! $chart->container() !!}</div>
                    <div class="col-md-5">
                        <table class="table table-sm table-striped">
                            <thead><tr><th>{{ trans_choice('loan::general.product', 1) }}</th><th class="text-right">{{ trans_choice('loan::general.amount', 1) }}</th><th class="text-right">#</th></tr></thead>
                            <tbody>
                            @foreach($products as $product)
                                <tr>
                                    <td>{{ $product->name }}</td>
                                    <td class="text-right">{{ number_format($product->total_principal) }}</td>
                                    <td class="text-right">{{ $product->loan_count }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
{!! $chart->script() !!}

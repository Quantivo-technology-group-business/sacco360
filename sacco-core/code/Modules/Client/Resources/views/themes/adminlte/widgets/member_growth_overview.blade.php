<div class="grid-stack-item member_growth_overview"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="MemberGrowthOverview">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">Member Growth</h3>
                <div class="card-tools">
                    <span class="badge badge-info mr-2">{{ $total_new }} new (12 mo)</span>
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

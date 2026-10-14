<div class="grid-stack-item"
     gs-x="{{$config["x"]}}" gs-y="{{$config["y"]}}"
     gs-w="{{$config["width"]}}" gs-h="{{$config["height"]}}" gs-id="LoanStatusOverview">
    <div class="grid-stack-item-content">
        <div style="padding:.65rem 1rem .4rem; border-bottom:1px solid #e2e8f0; background:#fff;">
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--sacco-primary,#0c2340);margin-right:.45rem;vertical-align:middle;"></span>
                {{ trans_choice('loan::general.loan', 2) }} {{ __('Status Overview') }}
            </span>
        </div>
        <div style="padding:.5rem;">
            {!! $chart->container() !!}
        </div>
    </div>
</div>
{!! $chart->script() !!}

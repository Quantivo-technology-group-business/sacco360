<div class="grid-stack-item loan_disbursement_trends"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="LoanDisbursementTrends">
    <div class="grid-stack-item-content">
        <div style="padding:.65rem 1rem .4rem; border-bottom:1px solid #e2e8f0; background:#fff; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:.5rem;">
            <span style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#64748b;">
                <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:var(--sacco-primary,#0c2340);margin-right:.45rem;vertical-align:middle;"></span>
                {{ trans_choice('loan::general.disbursement', 2) }} {{ trans_choice('loan::general.trend', 2) ?? 'Trends' }}
            </span>
            <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="font-size:.65rem;font-weight:700;padding:.3em .65em;border-radius:2rem;background:#dcfce7;color:#16a34a;">
                    {{ number_format($total_ytd) }} ({{ $count_ytd }} loans)
                </span>
                <button type="button" class="btn btn-box-tool btn-sm" data-card-widget="collapse" style="padding:.2rem .45rem;font-size:.75rem;">
                    <i class="fa fa-minus"></i>
                </button>
            </div>
        </div>
        <div style="padding:.5rem;">
            {!! $chart->container() !!}
        </div>
    </div>
</div>
{!! $chart->script() !!}

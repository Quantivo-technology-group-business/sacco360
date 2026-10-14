<div class="grid-stack-item financial_overview"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="FinancialOverview">
    <div class="grid-stack-item-content">
        <div class="card h-100" style="border:none;border-radius:0;">
            <div class="card-header" style="padding: 0.75rem 1rem; background:#fff; border-bottom:1px solid #e2e8f0;">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem">
                    <div class="d-flex align-items-center" style="gap:.6rem">
                        <span style="width:8px;height:8px;border-radius:50%;background:var(--sacco-accent,#c5a572);display:inline-block;flex-shrink:0;"></span>
                        <span class="card-title mb-0" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#64748b;">
                            Income vs Expense
                        </span>
                    </div>
                    <div class="d-flex align-items-center" style="gap:.5rem;flex-wrap:wrap;">
                        <span class="badge" style="font-size:.65rem;font-weight:700;padding:.3em .65em;border-radius:2rem;background:{{ $month_net >= 0 ? '#dcfce7' : '#fee2e2' }};color:{{ $month_net >= 0 ? '#16a34a' : '#dc2626' }}">
                            Net: {{ number_format($month_net) }}
                        </span>
                        <button type="button" class="btn btn-box-tool btn-sm" data-card-widget="collapse" style="padding:.2rem .45rem;font-size:.75rem;">
                            <i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Summary stat row --}}
            <div class="d-flex" style="border-bottom:1px solid #f1f5f9;">
                <div style="flex:1;padding:.65rem 1rem;text-align:center;border-right:1px solid #f1f5f9;">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.2rem;">
                        This Month Income
                    </div>
                    <div style="font-size:1rem;font-weight:800;color:#16a34a;letter-spacing:-.02em;">
                        {{ number_format($month_income) }}
                    </div>
                </div>
                <div style="flex:1;padding:.65rem 1rem;text-align:center;border-right:1px solid #f1f5f9;">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.2rem;">
                        This Month Expense
                    </div>
                    <div style="font-size:1rem;font-weight:800;color:#dc2626;letter-spacing:-.02em;">
                        {{ number_format($month_expense) }}
                    </div>
                </div>
                <div style="flex:1;padding:.65rem 1rem;text-align:center;">
                    <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#94a3b8;margin-bottom:.2rem;">
                        YTD Net
                    </div>
                    <div style="font-size:1rem;font-weight:800;color:{{ ($ytd_income - $ytd_expense) >= 0 ? '#16a34a' : '#dc2626' }};letter-spacing:-.02em;">
                        {{ number_format($ytd_income - $ytd_expense) }}
                    </div>
                </div>
            </div>

            <div class="card-body p-2" style="overflow:hidden;">
                {!! $chart->container() !!}
            </div>
        </div>
    </div>
</div>
{!! $chart->script() !!}

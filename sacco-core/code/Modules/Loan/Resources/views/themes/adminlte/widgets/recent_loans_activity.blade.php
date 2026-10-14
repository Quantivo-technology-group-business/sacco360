<div class="grid-stack-item recent_loans_activity"
     gs-x="{{$config['x']}}" gs-y="{{$config['y']}}"
     gs-w="{{$config['width']}}" gs-h="{{$config['height']}}" gs-id="RecentLoansActivity">
    <div class="grid-stack-item-content">
        <div class="card card-bordered h-100">
            <div class="card-header with-border">
                <h3 class="card-title">{{ trans_choice('loan::general.recent', 1) }} {{ trans_choice('loan::general.loan', 2) }}</h3>
                <div class="card-tools">
                    <a href="{{ url('loan') }}" class="btn btn-sm btn-default">View All</a>
                    <button type="button" class="btn btn-box-tool" data-card-widget="collapse"><i class="fa fa-minus"></i></button>
                </div>
            </div>
            <div class="card-body table-responsive p-0" style="max-height:100%;overflow-y:auto;">
                <table class="table table-hover table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>{{ trans_choice('client::general.client', 1) }}</th>
                        <th>{{ trans_choice('loan::general.product', 1) }}</th>
                        <th>{{ trans_choice('loan::general.amount', 1) }}</th>
                        <th>{{ trans_choice('core::general.status', 1) }}</th>
                        <th>{{ trans_choice('core::general.date', 1) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentLoans as $loan)
                        <tr>
                            <td><a href="{{ url('loan/' . $loan->id . '/show') }}">{{ $loan->id }}</a></td>
                            <td>{{ $loan->client->first_name ?? '' }} {{ $loan->client->last_name ?? '' }}</td>
                            <td>{{ $loan->loan_product->name ?? '-' }}</td>
                            <td>{{ number_format($loan->principal ?: $loan->applied_amount) }}</td>
                            <td><span class="badge badge-{{ $loan->status == 'active' ? 'success' : ($loan->status == 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst(str_replace('_', ' ', $loan->status)) }}</span></td>
                            <td>{{ $loan->created_at ? $loan->created_at->format('M d, Y') : '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted">No data available</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

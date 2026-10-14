@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.pending_transfers') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.pending_transfers') }}</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ trans('sacco::share_marketplace.reference') }}</th>
                <th>{{ trans('sacco::share_marketplace.type') }}</th>
                <th>{{ trans('sacco::share_marketplace.seller') }}</th>
                <th>{{ trans('sacco::share_marketplace.buyer') }}</th>
                <th>{{ trans('sacco::share_marketplace.quantity') }}</th>
                <th>{{ trans('sacco::share_marketplace.total_amount') }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($pending as $transfer)
                <tr>
                    <td>{{ $transfer->reference }}</td>
                    <td>{{ ucfirst($transfer->type) }}</td>
                    <td>{{ $transfer->seller->member_number ?? $transfer->seller->external_id ?? $transfer->seller_client_id }}</td>
                    <td>{{ $transfer->buyer->member_number ?? $transfer->buyer->external_id ?? $transfer->buyer_client_id }}</td>
                    <td>{{ number_format($transfer->quantity) }}</td>
                    <td>{{ number_format($transfer->total_amount, 2) }}</td>
                    <td><a href="{{ url('sacco/share-transfer/'.$transfer->id.'/show') }}" class="btn btn-sm btn-primary">{{ trans('core::general.view') }}</a></td>
                </tr>
            @empty
                <tr><td colspan="7">{{ trans('sacco::share_marketplace.no_pending') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $pending->links() }}
    </div></div>
</section>
@endsection

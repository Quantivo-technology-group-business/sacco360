@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.marketplace') }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>{{ trans('sacco::share_marketplace.marketplace') }}</h1></div>
            <div class="col-sm-6 text-right">
                <a href="{{ url('portal/share/marketplace/listings') }}" class="btn btn-outline-primary btn-sm">{{ trans('sacco::share_marketplace.my_listings') }}</a>
                <a href="{{ url('portal/share/marketplace/transfers') }}" class="btn btn-outline-secondary btn-sm">{{ trans('sacco::share_marketplace.my_transfers') }}</a>
            </div>
        </div>
    </div>
</section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                <tr>
                    <th>{{ trans('sacco::share_marketplace.product') }}</th>
                    @if($showSellerName)<th>{{ trans('sacco::share_marketplace.seller') }}</th>@endif
                    <th>{{ trans('sacco::share_marketplace.available') }}</th>
                    <th>{{ trans('sacco::share_marketplace.price_per_share') }}</th>
                    <th>{{ trans('sacco::share_marketplace.total_value') }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($listings as $listing)
                    @php
                        $product = $listing->share->share_product->name ?? 'Share';
                        $sellerLabel = $listing->seller->member_number ?? $listing->seller->external_id ?? $listing->seller_client_id;
                    @endphp
                    <tr>
                        <td>{{ $product }}</td>
                        @if($showSellerName)<td>{{ $sellerLabel }}</td>@endif
                        <td>{{ number_format($listing->remaining_quantity) }}</td>
                        <td>{{ number_format($listing->price_per_share, 2) }}</td>
                        <td>{{ number_format($listing->remaining_quantity * $listing->price_per_share, 2) }}</td>
                        <td><a href="{{ url('portal/share/marketplace/listing/'.$listing->id.'/buy') }}" class="btn btn-sm btn-primary">{{ trans('sacco::share_marketplace.buy') }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $showSellerName ? 6 : 5 }}">{{ trans('sacco::share_marketplace.no_listings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

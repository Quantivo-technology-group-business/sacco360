@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.my_listings') }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>{{ trans('sacco::share_marketplace.my_listings') }}</h1></div>
            <div class="col-sm-6 text-right">
                <a href="{{ url('portal/share/marketplace/listing/create') }}" class="btn btn-primary btn-sm">{{ trans('sacco::share_marketplace.create_listing') }}</a>
                <a href="{{ url('portal/share/marketplace/direct/create') }}" class="btn btn-outline-primary btn-sm">{{ trans('sacco::share_marketplace.direct_sale') }}</a>
            </div>
        </div>
    </div>
</section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead>
                <tr>
                    <th>{{ trans('sacco::share_marketplace.product') }}</th>
                    <th>{{ trans('sacco::share_marketplace.quantity') }}</th>
                    <th>{{ trans('sacco::share_marketplace.remaining') }}</th>
                    <th>{{ trans('sacco::share_marketplace.price_per_share') }}</th>
                    <th>{{ trans_choice('core::general.status',1) }}</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($listings as $listing)
                    <tr>
                        <td>{{ $listing->share->share_product->name ?? 'Share' }}</td>
                        <td>{{ number_format($listing->quantity) }}</td>
                        <td>{{ number_format($listing->remaining_quantity) }}</td>
                        <td>{{ number_format($listing->price_per_share, 2) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $listing->status)) }}</td>
                        <td>
                            @if(in_array($listing->status, ['active', 'partially_sold']))
                                <a href="{{ url('portal/share/marketplace/listing/'.$listing->id.'/cancel') }}" class="btn btn-sm btn-danger confirm">{{ trans('sacco::share_marketplace.cancel') }}</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">{{ trans('sacco::share_marketplace.no_my_listings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection

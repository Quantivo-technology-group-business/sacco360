@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.direct_sale') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.direct_sale') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        <form method="post" action="{{ url('portal/share/marketplace/direct/store') }}">@csrf
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.share_account') }}</label>
                <select name="share_id" class="form-control" required>
                    <option value="">{{ trans('core::general.select') }}</option>
                    @foreach($shares as $share)
                        <option value="{{ $share->id }}" @selected(old('share_id') == $share->id)>
                            {{ $share->share_product->name ?? 'Share' }} #{{ $share->id }} ({{ trans('sacco::share_marketplace.free_shares') }}: {{ $share->free_shares }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.buyer_member_number') }}</label>
                <input type="text" name="buyer_member_number" class="form-control" value="{{ old('buyer_member_number') }}" required>
            </div>
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.quantity') }}</label>
                <input type="number" name="quantity" class="form-control" min="1" value="{{ old('quantity') }}" required>
            </div>
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.price_per_share') }}</label>
                <input type="number" step="0.01" name="price_per_share" class="form-control" min="0.01" value="{{ old('price_per_share') }}" required>
            </div>
            <button type="submit" class="btn btn-primary">{{ trans('sacco::share_marketplace.send_offer') }}</button>
            <a href="{{ url('portal/share/marketplace/listings') }}" class="btn btn-default">{{ trans('core::general.cancel') }}</a>
        </form>
    </div></div>
</section>
@endsection

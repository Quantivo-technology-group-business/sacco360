@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.buy_shares') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.buy_shares') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        <p><strong>{{ trans('sacco::share_marketplace.product') }}:</strong> {{ $listing->share->share_product->name ?? 'Share' }}</p>
        <p><strong>{{ trans('sacco::share_marketplace.available') }}:</strong> {{ number_format($listing->remaining_quantity) }}</p>
        <p><strong>{{ trans('sacco::share_marketplace.price_per_share') }}:</strong> {{ number_format($listing->price_per_share, 2) }}</p>
        <form method="post" action="{{ url('portal/share/marketplace/listing/'.$listing->id.'/buy') }}">@csrf
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.quantity') }}</label>
                <input type="number" name="quantity" class="form-control" min="1" max="{{ $listing->remaining_quantity }}" value="{{ old('quantity', 1) }}" required>
            </div>
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.pay_from_savings') }}</label>
                <select name="buyer_savings_id" class="form-control" required>
                    <option value="">{{ trans('core::general.select') }}</option>
                    @foreach($savings as $account)
                        @php $bal = $wing->getAccountBalance($account->id); @endphp
                        <option value="{{ $account->id }}" @selected(old('buyer_savings_id') == $account->id)>
                            #{{ $account->account_number ?? $account->id }} ({{ trans('sacco::share_marketplace.balance') }}: {{ number_format($bal, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <p class="text-muted">{{ trans('sacco::share_marketplace.hold_notice') }}</p>
            <button type="submit" class="btn btn-primary">{{ trans('sacco::share_marketplace.submit_purchase') }}</button>
            <a href="{{ url('portal/share/marketplace') }}" class="btn btn-default">{{ trans('core::general.cancel') }}</a>
        </form>
    </div></div>
</section>
@endsection

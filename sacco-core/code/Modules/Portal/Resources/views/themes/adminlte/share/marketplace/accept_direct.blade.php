@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.accept_direct_sale') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.accept_direct_sale') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        <p><strong>{{ trans('sacco::share_marketplace.seller') }}:</strong> {{ $transfer->seller->member_number ?? $transfer->seller->external_id ?? $transfer->seller_client_id }}</p>
        <p><strong>{{ trans('sacco::share_marketplace.product') }}:</strong> {{ $transfer->seller_share->share_product->name ?? 'Share' }}</p>
        <p><strong>{{ trans('sacco::share_marketplace.quantity') }}:</strong> {{ number_format($transfer->quantity) }}</p>
        <p><strong>{{ trans('sacco::share_marketplace.total_amount') }}:</strong> {{ number_format($transfer->total_amount, 2) }}</p>
        <form method="post" action="{{ url('portal/share/marketplace/direct/'.$transfer->id.'/accept') }}">@csrf
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
            <button type="submit" class="btn btn-primary">{{ trans('sacco::share_marketplace.accept_and_submit') }}</button>
            <a href="{{ url('portal/share/marketplace/transfers') }}" class="btn btn-default">{{ trans('core::general.cancel') }}</a>
        </form>
    </div></div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.review_transfer') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.review_transfer') }} #{{ $transfer->reference }}</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><strong>{{ trans('sacco::share_marketplace.seller') }}</strong><br>{{ $transfer->seller->first_name ?? '' }} {{ $transfer->seller->last_name ?? '' }} ({{ $transfer->seller->member_number ?? $transfer->seller->external_id ?? $transfer->seller_client_id }})</div>
            <div class="col-md-4"><strong>{{ trans('sacco::share_marketplace.buyer') }}</strong><br>{{ $transfer->buyer->first_name ?? '' }} {{ $transfer->buyer->last_name ?? '' }} ({{ $transfer->buyer->member_number ?? $transfer->buyer->external_id ?? $transfer->buyer_client_id }})</div>
            <div class="col-md-4"><strong>{{ trans('sacco::share_marketplace.product') }}</strong><br>{{ $transfer->seller_share->share_product->name ?? 'Share' }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-3"><strong>{{ trans('sacco::share_marketplace.quantity') }}</strong><br>{{ number_format($transfer->quantity) }}</div>
            <div class="col-md-3"><strong>{{ trans('sacco::share_marketplace.price_per_share') }}</strong><br>{{ number_format($transfer->price_per_share, 2) }}</div>
            <div class="col-md-3"><strong>{{ trans('sacco::share_marketplace.total_amount') }}</strong><br>{{ number_format($transfer->total_amount, 2) }}</div>
            <div class="col-md-3"><strong>{{ trans('sacco::share_marketplace.type') }}</strong><br>{{ ucfirst($transfer->type) }}</div>
        </div>
        @if(!$regulatory['passed'])
            <div class="alert alert-warning">
                {{ trans('sacco::share_marketplace.sasra_warning') }}: {{ implode(' ', $regulatory['violations']) }}
            </div>
        @else
            <div class="alert alert-info">
                {{ trans('sacco::share_marketplace.buyer_share_percent') }}: {{ number_format($regulatory['member_percent'], 2) }}% ({{ trans('sacco::share_marketplace.max') }}: {{ number_format($regulatory['max_percent'], 2) }}%)
            </div>
        @endif
        @if($transfer->status === 'pending_admin')
        <form method="post" action="{{ url('sacco/share-transfer/'.$transfer->id.'/approve') }}" class="mb-3">@csrf
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.credit_seller_savings') }}</label>
                <select name="seller_savings_id" class="form-control">
                    <option value="">{{ trans('sacco::share_marketplace.default_seller_account') }}</option>
                    @foreach($sellerSavings as $account)
                        <option value="{{ $account->id }}">#{{ $account->account_number ?? $account->id }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn btn-success confirm">{{ trans('sacco::share_marketplace.approve_transfer') }}</button>
        </form>
        <form method="post" action="{{ url('sacco/share-transfer/'.$transfer->id.'/reject') }}">@csrf
            <div class="form-group">
                <label>{{ trans('sacco::share_marketplace.rejection_reason') }}</label>
                <textarea name="rejected_notes" class="form-control" rows="2"></textarea>
            </div>
            <button type="submit" class="btn btn-danger">{{ trans('sacco::share_marketplace.reject_transfer') }}</button>
        </form>
        @else
            <p><strong>{{ trans_choice('core::general.status',1) }}:</strong> {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}</p>
        @endif
        <a href="{{ url('sacco/share-transfer') }}" class="btn btn-default mt-3">{{ trans('core::general.back') }}</a>
    </div></div>
</section>
@endsection

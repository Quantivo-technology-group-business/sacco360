@extends('core::layouts.master')
@section('title') {{ trans('sacco::share_marketplace.my_transfers') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::share_marketplace.my_transfers') }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body table-responsive">
        <table class="table table-bordered">
            <thead>
            <tr>
                <th>{{ trans('sacco::share_marketplace.reference') }}</th>
                <th>{{ trans('sacco::share_marketplace.type') }}</th>
                <th>{{ trans('sacco::share_marketplace.counterparty') }}</th>
                <th>{{ trans('sacco::share_marketplace.quantity') }}</th>
                <th>{{ trans('sacco::share_marketplace.total_amount') }}</th>
                <th>{{ trans_choice('core::general.status',1) }}</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($transfers as $transfer)
                @php
                    $isSeller = $transfer->seller_client_id === $client->id;
                    $counterparty = $isSeller ? $transfer->buyer : $transfer->seller;
                    $counterpartyLabel = $counterparty->member_number ?? $counterparty->external_id ?? $counterparty->id;
                @endphp
                <tr>
                    <td>{{ $transfer->reference }}</td>
                    <td>{{ ucfirst($transfer->type) }}</td>
                    <td>{{ $counterpartyLabel }} ({{ $isSeller ? trans('sacco::share_marketplace.buyer') : trans('sacco::share_marketplace.seller') }})</td>
                    <td>{{ number_format($transfer->quantity) }}</td>
                    <td>{{ number_format($transfer->total_amount, 2) }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $transfer->status)) }}</td>
                    <td>
                        @if(!$isSeller && $transfer->type === 'direct' && $transfer->status === 'pending_buyer_acceptance')
                            <a href="{{ url('portal/share/marketplace/direct/'.$transfer->id.'/accept') }}" class="btn btn-sm btn-success">{{ trans('sacco::share_marketplace.accept') }}</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7">{{ trans('sacco::share_marketplace.no_transfers') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

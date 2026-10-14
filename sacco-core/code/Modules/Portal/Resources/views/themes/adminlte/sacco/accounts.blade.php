@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.my_accounts') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.accounts_overview') }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="row">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h5>{{ trans('sacco::general.bosa') }}</h5>
                @if($bosa)
                    <p class="h4">{{ number_format($bosaBalance, 2) }}</p>
                    <small>#{{ $bosa->account_number ?? $bosa->id }}</small>
                @else
                    <p class="text-muted">No BOSA account</p>
                    <form method="post" action="{{ url('portal/sacco/open-bosa') }}">@csrf<button class="btn btn-sm btn-primary">{{ trans('sacco::general.open_bosa_account') }}</button></form>
                @endif
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h5>{{ trans('sacco::general.fosa') }}</h5>
                @if($fosa)
                    <p class="h4">{{ number_format($fosaBalance, 2) }}</p>
                    <small>#{{ $fosa->account_number ?? $fosa->id }}</small>
                @else
                    <p class="text-muted">No FOSA account</p>
                    <form method="post" action="{{ url('portal/sacco/open-fosa') }}">@csrf<button class="btn btn-sm btn-primary">{{ trans('sacco::general.open_fosa_account') }}</button></form>
                @endif
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h5>{{ trans('sacco::general.wallet') }}</h5>
                @if($wallet)
                    <p class="h4">{{ number_format($walletBalance, 2) }}</p>
                    <a href="{{ url('portal/wallet') }}" class="btn btn-sm btn-outline-primary">Manage</a>
                @else
                    <p class="text-muted">No active wallet</p>
                @endif
            </div></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ trans('sacco::general.fixed_deposits') }}</h3></div>
            <div class="card-body">
                @forelse($fixedDeposits as $fd)
                    <p>{{ number_format($fd->principal, 2) }} — matures {{ $fd->maturity_date }} ({{ $fd->status }})</p>
                @empty
                    <p>{{ trans('sacco::general.no_fixed_deposits') }}</p>
                @endforelse
                <a href="{{ url('portal/sacco/fixed_deposit') }}" class="btn btn-sm btn-primary">View all</a>
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <p><strong>{{ trans('sacco::general.share_value') }}:</strong> {{ number_format($member360['share_value'] ?? 0, 2) }}</p>
                <p><strong>{{ trans('sacco::general.debit_card', 2) }}:</strong> {{ $cards->count() }}</p>
                <p><strong>{{ trans('sacco::general.net_position') }}:</strong> {{ number_format($member360['net_position'] ?? 0, 2) }}</p>
                <a href="{{ url('portal/sacco/statements') }}" class="btn btn-sm btn-outline-secondary">{{ trans('sacco::general.account_statements') }}</a>
            </div></div>
        </div>
    </div>
</section>
@endsection

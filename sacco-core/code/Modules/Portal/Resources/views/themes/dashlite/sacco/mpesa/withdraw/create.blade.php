@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.withdraw_mpesa') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.withdraw_mpesa') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($simulation)<div class="alert alert-warning">{{ trans('sacco::general.mpesa_b2c_simulation_notice') }}</div>@endif
    <form method="post" action="{{ url('portal/sacco/mpesa/withdraw/store') }}">@csrf
        <div class="card mb-3"><div class="card-body">
            <div class="form-group">
                <label>{{ trans('sacco::general.source_account') }}</label>
                <select name="source_type" class="form-control" required>
                    @if($fosa)<option value="fosa">{{ trans('sacco::general.fosa') }} (#{{ $fosa->account_number ?? $fosa->id }}) — {{ number_format($fosaBalance, 2) }}</option>@endif
                    @if($wallet)<option value="wallet">{{ trans('sacco::general.wallet_balance') }} (#{{ $wallet->id }}) — {{ number_format($walletBalance, 2) }}</option>@endif
                </select>
            </div>
            <div class="form-group">
                <label>{{ trans('sacco::general.recipient_phone') }}</label>
                <input class="form-control" value="{{ $memberPhone }}" readonly>
                <small class="text-muted">{{ trans('sacco::general.withdraw_to_own_phone') }}</small>
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="form-group"><label>{{ trans('core::general.amount') }}</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('sacco::general.narration') }}</label><input name="narration" class="form-control"></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'mpesa_withdraw'])
            <button class="btn btn-primary">{{ trans('sacco::general.withdraw_mpesa') }}</button>
        </div></div>
    </form>
</section>
@endsection

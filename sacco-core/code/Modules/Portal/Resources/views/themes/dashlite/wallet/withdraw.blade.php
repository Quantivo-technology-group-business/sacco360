@extends('core::layouts.master')
@section('title') Wallet Withdraw @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <p>Available: {{ number_format($wallet->balance, 2) }}</p>
    <form method="post" action="{{ url('portal/wallet/withdraw/store') }}">@csrf
        <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        <div class="form-group"><label>Date</label><input type="date" name="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
        <div class="form-group"><label>Payment type</label><select name="payment_type_id" class="form-control" required>@foreach($paymentTypes as $pt)<option value="{{ $pt->id }}">{{ $pt->name }}</option>@endforeach</select></div>
        @include('portal::sacco.partials.transaction_auth', ['purpose' => 'wallet_withdraw'])
        <button type="submit" class="btn btn-primary">Withdraw</button>
    </form>
</div></div></section>
@endsection

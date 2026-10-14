@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.new_fixed_deposit') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($fosa && $product)
        <p>FOSA available: {{ number_format($balance, 2) }}</p>
        <form method="post" action="{{ url('portal/sacco/fixed_deposit/store') }}">@csrf
            <div class="form-group"><label>Principal</label><input type="number" step="0.01" name="principal" class="form-control" required></div>
            <div class="form-group"><label>Tenure (months)</label><input type="number" name="tenure_months" class="form-control" value="12" required></div>
            <div class="form-group"><label>Renewal</label>
                <select name="renewal_option" class="form-control"><option value="payout">Payout at maturity</option><option value="renew_principal">Renew principal</option><option value="renew_principal_interest">Renew principal + interest</option></select>
            </div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'fixed_deposit'])
            <button type="submit" class="btn btn-primary">{{ trans('core::general.save') }}</button>
        </form>
    @else
        <div class="alert alert-warning">FOSA account or fixed deposit product not available.</div>
    @endif
</div></div></section>
@endsection

@extends('core::layouts.master')
@section('title') Send Pesalink @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Send Pesalink from FOSA</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($simulation)<div class="alert alert-warning">{{ trans('sacco::general.pesalink_simulation_notice') }}</div>@endif
    <div class="card mb-3"><div class="card-body">
        <p>FOSA Account: {{ $fosa ? ('#' . ($fosa->account_number ?? $fosa->id)) : 'N/A' }}</p>
        <p>Available Balance: {{ number_format($balance, 2) }}</p>
    </div></div>
    <form method="post" action="{{ url('portal/sacco/pesalink/store') }}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Beneficiary Account</label><input name="beneficiary_account" class="form-control" required></div>
            <div class="form-group"><label>Beneficiary Bank</label><input name="beneficiary_bank" class="form-control" required></div>
            <div class="form-group"><label>Beneficiary Name</label><input name="beneficiary_name" class="form-control" required></div>
                    <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'pesalink'])
            <button class="btn btn-primary">Send</button>
        </div></div>
    </form>
</section>
@endsection

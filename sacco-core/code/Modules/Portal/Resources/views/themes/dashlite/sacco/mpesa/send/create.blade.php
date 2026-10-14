@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.send_mpesa') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.send_mpesa') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($simulation)<div class="alert alert-warning">{{ trans('sacco::general.mpesa_b2c_simulation_notice') }}</div>@endif
    <form method="post" action="{{ url('portal/sacco/mpesa/send/store') }}" id="mpesa-send-form">@csrf
        <div class="card mb-3"><div class="card-body">
            <div class="form-group">
                <label>{{ trans('sacco::general.source_account') }}</label>
                <select name="source_type" class="form-control" required>
                    @if($fosa)<option value="fosa">{{ trans('sacco::general.fosa') }} (#{{ $fosa->account_number ?? $fosa->id }}) — {{ number_format($fosaBalance, 2) }}</option>@endif
                    @if($wallet)<option value="wallet">{{ trans('sacco::general.wallet_balance') }} (#{{ $wallet->id }}) — {{ number_format($walletBalance, 2) }}</option>@endif
                </select>
            </div>
        </div></div>
        <div class="card"><div class="card-body">
            <div class="form-group">
                <label>{{ trans('sacco::general.saved_beneficiary') }}</label>
                <select name="beneficiary_id" id="beneficiary_id" class="form-control">
                    <option value="">{{ trans('sacco::general.new_recipient') }}</option>
                    @foreach($beneficiaries as $b)
                        <option value="{{ $b->id }}">{{ $b->nickname }} — {{ $b->name }} ({{ $b->phone }})</option>
                    @endforeach
                </select>
            </div>
            <div id="manual-recipient">
                <div class="form-group"><label>{{ trans('sacco::general.recipient_phone') }}</label><input name="recipient_phone" id="recipient_phone" class="form-control" placeholder="07XXXXXXXX"></div>
                <div class="form-group"><label>{{ trans('sacco::general.recipient_name') }}</label><input name="recipient_name" id="recipient_name" class="form-control"></div>
            </div>
            <div class="form-group"><label>{{ trans('core::general.amount') }}</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('sacco::general.narration') }}</label><input name="narration" class="form-control"></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'mpesa_send'])
            <button class="btn btn-primary">{{ trans('sacco::general.send_mpesa') }}</button>
        </div></div>
    </form>
</section>
<script>
document.getElementById('beneficiary_id').addEventListener('change', function () {
    var manual = document.getElementById('manual-recipient');
    var phone = document.getElementById('recipient_phone');
    var name = document.getElementById('recipient_name');
    if (this.value) {
        manual.style.display = 'none';
        phone.removeAttribute('required');
        name.removeAttribute('required');
    } else {
        manual.style.display = 'block';
        phone.setAttribute('required', 'required');
        name.setAttribute('required', 'required');
    }
});
</script>
@endsection

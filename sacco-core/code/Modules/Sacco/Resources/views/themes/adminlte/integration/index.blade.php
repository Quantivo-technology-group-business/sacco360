@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.integrations') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.integrations') }}</h1></div></section>
<section class="content">
    <div class="row">
        <div class="col-md-4"><div class="card"><div class="card-header">CRB Credit Check</div><div class="card-body">
            <form method="post" action="{{url('sacco/integration/crb')}}">@csrf
                <div class="form-group"><label>Client ID</label><input name="client_id" class="form-control" required></div>
                <button class="btn btn-primary">Check CRB</button>
            </form>
            @if(session('crb_result'))<pre class="mt-2">{{json_encode(session('crb_result'), JSON_PRETTY_PRINT)}}</pre>@endif
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-header">NIDA/IPRS Validation</div><div class="card-body">
            <form method="post" action="{{url('sacco/integration/nida')}}">@csrf
                <div class="form-group"><label>ID Number</label><input name="id_number" class="form-control" required></div>
                <div class="form-group"><label>First Name</label><input name="first_name" class="form-control"></div>
                <div class="form-group"><label>Last Name</label><input name="last_name" class="form-control"></div>
                <button class="btn btn-primary">Validate ID</button>
            </form>
            @if(session('nida_result'))<pre class="mt-2">{{json_encode(session('nida_result'), JSON_PRETTY_PRINT)}}</pre>@endif
        </div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-header">RTGS/EFT Transfer</div><div class="card-body">
            <form method="post" action="{{url('sacco/integration/rtgs')}}">@csrf
                <div class="form-group"><label>Beneficiary Account</label><input name="beneficiary_account" class="form-control" required></div>
                <div class="form-group"><label>Bank</label><input name="beneficiary_bank" class="form-control" required></div>
                <div class="form-group"><label>Name</label><input name="beneficiary_name" class="form-control" required></div>
                <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
                <button class="btn btn-primary">Initiate RTGS</button>
            </form>
            @if(session('rtgs_result'))<pre class="mt-2">{{json_encode(session('rtgs_result'), JSON_PRETTY_PRINT)}}</pre>@endif
        </div></div></div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6"><div class="card"><div class="card-header">M-Pesa C2B (PayBill to FOSA)</div><div class="card-body">
            <p>Validation: <code>POST {{url('api/v1/sacco/mpesa/c2b/validate')}}</code></p>
            <p>Confirmation: <code>POST {{url('api/v1/sacco/mpesa/c2b/confirm')}}</code></p>
            <p>Bill reference = member number</p>
            <a href="{{url('sacco/mpesa_c2b')}}" class="btn btn-sm btn-info">View C2B Transactions</a>
        </div></div></div>
        <div class="col-md-6"><div class="card"><div class="card-header">Pesalink / IPS</div><div class="card-body">
            <p>Outbound: <a href="{{url('sacco/pesalink/create')}}">Send via admin</a></p>
            <p>Inbound: <code>POST {{url('api/v1/sacco/pesalink/inbound')}}</code></p>
            <p>API: {{config('sacco.pesalink_api_url') ?: 'Not configured (simulation mode)'}}</p>
            <a href="{{url('sacco/pesalink')}}" class="btn btn-sm btn-info">View Pesalink</a>
        </div></div></div>
    </div>
    <div class="card mt-3"><div class="card-header">Mobile Banking / USSD / ATM</div><div class="card-body">
        <p>USSD: <code>POST /api/v1/sacco/ussd</code> — Shortcode: {{config('sacco.ussd_shortcode')}}</p>
        <p>ATM: <code>POST /api/v1/sacco/atm</code> — Real-time FOSA ledger posting</p>
        <p>Internet Banking: <code>{{url('portal/sacco/transfer')}}</code></p>
    </div></div>
</section>
@endsection

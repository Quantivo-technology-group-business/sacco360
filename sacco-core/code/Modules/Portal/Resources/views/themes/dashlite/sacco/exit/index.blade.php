@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.exit_settlement') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <h4>Settlement Preview</h4>
    <ul class="list-unstyled">
        <li>BOSA: {{ number_format($preview['bosa_balance'], 2) }}</li>
        <li>Shares: {{ number_format($preview['share_value'], 2) }}</li>
        <li>Loan offset: {{ number_format($preview['loan_offset'], 2) }}</li>
        <li><strong>Net payout: {{ number_format($preview['net_payout'], 2) }}</strong></li>
    </ul>
    <form method="post" action="{{ url('portal/sacco/exit/store') }}">@csrf
        <div class="form-group"><label>Exit reason</label>
            <select name="exit_reason" class="form-control" required><option value="resignation">Resignation</option><option value="retirement">Retirement</option><option value="death">Death</option><option value="other">Other</option></select>
        </div>
        <div class="form-group"><label>Payout account</label><input type="text" name="payout_account" class="form-control"></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
        @include('portal::sacco.partials.transaction_auth', ['purpose' => 'exit'])
        <button type="submit" class="btn btn-danger">{{ trans('sacco::general.request_exit') }}</button>
    </form>
    <hr>
    <h5>Previous requests</h5>
    @forelse($settlements as $s)<p>#{{ $s->id }} — {{ $s->status }} — {{ number_format($s->net_payout, 2) }}</p>@empty<p>None</p>@endforelse
</div></div></section>
@endsection

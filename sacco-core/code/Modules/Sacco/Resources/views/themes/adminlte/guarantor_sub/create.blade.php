@extends('core::layouts.master')
@section('title') New Guarantor Substitution @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New Guarantor Substitution Request</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <form method="POST" action="{{ url('sacco/guarantor-sub/store') }}">@csrf
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Loan <span class="text-danger">*</span></label>
                <select name="loan_id" class="form-control select2" required>
                    <option value="">— Select Loan —</option>
                    @foreach($loans as $l)
                        <option value="{{ $l->id }}">#{{ $l->id }} — {{ $l->client->full_name ?? '' }} ({{ number_format($l->amount,2) }})</option>
                    @endforeach
                </select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Outgoing Guarantor (to remove) <span class="text-danger">*</span></label>
                <select name="outgoing_guarantor_client_id" class="form-control select2" required>
                    <option value="">— Select Member —</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->member_number ?? $c->id }})</option>@endforeach
                </select></div></div>
            <div class="col-md-4"><div class="form-group"><label>Incoming Guarantor (to add) <span class="text-danger">*</span></label>
                <select name="incoming_guarantor_client_id" class="form-control select2" required>
                    <option value="">— Select Member —</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->member_number ?? $c->id }})</option>@endforeach
                </select></div></div>
        </div>
        <div class="row">
            <div class="col-md-3"><div class="form-group"><label>Guaranteed Amount</label>
                <input type="number" name="guaranteed_amount" class="form-control" step="0.01" min="0" value="{{ old('guaranteed_amount') }}"></div></div>
            <div class="col-md-9"><div class="form-group"><label>Reason for Substitution <span class="text-danger">*</span></label>
                <textarea name="reason" class="form-control" rows="2" required>{{ old('reason') }}</textarea></div></div>
        </div>
        <div class="callout callout-info">
            <p class="mb-0"><i class="fas fa-info-circle"></i> After submission, both the outgoing and incoming guarantor must <strong>consent</strong> before the request can be approved. The outgoing guarantor's security will only be released after final approval.</p>
        </div>
        <button type="submit" class="btn btn-primary">Submit Request</button>
        <a href="{{ url('sacco/guarantor-sub') }}" class="btn btn-default">Cancel</a>
    </form>
</div></div></section>
@endsection

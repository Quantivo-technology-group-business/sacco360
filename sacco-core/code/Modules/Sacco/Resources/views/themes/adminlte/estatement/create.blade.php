@extends('core::layouts.master')
@section('title') New Statement Schedule @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New E-Statement Schedule</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <form method="POST" action="{{ url('sacco/estatement/store') }}">@csrf
        <div class="row">
            <div class="col-md-5"><div class="form-group"><label>Schedule Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div></div>
            <div class="col-md-3"><div class="form-group"><label>Member (blank = all)</label>
                <select name="client_id" class="form-control select2"><option value="">— All Active Members —</option>
                @foreach($clients as $c)<option value="{{ $c->id }}" {{ old('client_id')==$c->id?'selected':'' }}>{{ $c->full_name }}</option>@endforeach
                </select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Statement Type</label>
                <select name="statement_type" class="form-control">
                    <option value="full">Full Statement</option><option value="savings">Savings Only</option>
                    <option value="loans">Loans Only</option><option value="shares">Shares Only</option>
                </select></div></div>
        </div>
        <div class="row">
            <div class="col-md-2"><div class="form-group"><label>Frequency <span class="text-danger">*</span></label>
                <select name="frequency" class="form-control" required>
                    <option value="monthly">Monthly</option><option value="quarterly">Quarterly</option><option value="annually">Annually</option>
                </select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Delivery Channel</label>
                <select name="delivery_channel" class="form-control">
                    <option value="email">Email</option><option value="portal">Portal</option><option value="sms">SMS</option><option value="print">Print</option>
                </select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Send on Day of Month</label>
                <input type="number" name="send_day_of_month" class="form-control" min="1" max="28" value="{{ old('send_day_of_month',1) }}" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Lookback Months</label>
                <input type="number" name="lookback_months" class="form-control" min="1" max="24" value="{{ old('lookback_months',1) }}"></div></div>
        </div>
        <div class="row">
            <div class="col-md-8">
                <label>Include Sections:</label>
                <div class="form-check form-check-inline"><input type="checkbox" name="include_loans" value="1" class="form-check-input" id="il" {{ old('include_loans',1)?'checked':'' }}><label for="il" class="form-check-label">Loans</label></div>
                <div class="form-check form-check-inline"><input type="checkbox" name="include_savings" value="1" class="form-check-input" id="is" {{ old('include_savings',1)?'checked':'' }}><label for="is" class="form-check-label">Savings</label></div>
                <div class="form-check form-check-inline"><input type="checkbox" name="include_shares" value="1" class="form-check-input" id="ish" {{ old('include_shares',1)?'checked':'' }}><label for="ish" class="form-check-label">Shares</label></div>
                <div class="form-check form-check-inline"><input type="checkbox" name="include_summary" value="1" class="form-check-input" id="isu" {{ old('include_summary',1)?'checked':'' }}><label for="isu" class="form-check-label">Summary</label></div>
            </div>
        </div>
        <div class="mt-3"><button type="submit" class="btn btn-primary">Save Schedule</button>
        <a href="{{ url('sacco/estatement') }}" class="btn btn-default">Cancel</a></div>
    </form>
</div></div></section>
@endsection

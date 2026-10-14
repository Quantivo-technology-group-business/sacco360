@extends('core::layouts.master')
@section('title') New Investment @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New Investment</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <form method="POST" action="{{ url('sacco/investment/store') }}">
            @csrf
            <div class="row">
                <div class="col-md-5"><div class="form-group"><label>Investment Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div></div>
                <div class="col-md-3"><div class="form-group"><label>Type <span class="text-danger">*</span></label>
                    <select name="type" class="form-control" required>
                        @foreach(['treasury_bill'=>'Treasury Bill','treasury_bond'=>'Treasury Bond','fixed_deposit_bank'=>'Bank Fixed Deposit',
                                  'equities'=>'Equities','real_estate'=>'Real Estate','corporate_bond'=>'Corporate Bond','unit_trust'=>'Unit Trust','other'=>'Other'] as $v=>$l)
                            <option value="{{ $v }}" {{ old('type')==$v?'selected':'' }}>{{ $l }}</option>
                        @endforeach
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label>Issuer / Institution</label>
                    <input type="text" name="issuer" class="form-control" value="{{ old('issuer') }}" placeholder="e.g. Central Bank of Kenya"></div></div>
            </div>
            <div class="row">
                <div class="col-md-2"><div class="form-group"><label>Principal Amount <span class="text-danger">*</span></label>
                    <input type="number" name="principal_amount" class="form-control" step="0.01" min="1" value="{{ old('principal_amount') }}" required></div></div>
                <div class="col-md-2"><div class="form-group"><label>Annual Rate (%)</label>
                    <input type="number" name="interest_rate" class="form-control" step="0.0001" min="0" value="{{ old('interest_rate') }}"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Investment Date <span class="text-danger">*</span></label>
                    <input type="date" name="investment_date" class="form-control" value="{{ old('investment_date', date('Y-m-d')) }}" required></div></div>
                <div class="col-md-2"><div class="form-group"><label>Maturity Date</label>
                    <input type="date" name="maturity_date" class="form-control" value="{{ old('maturity_date') }}"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Tenure (Days)</label>
                    <input type="number" name="tenure_days" class="form-control" min="1" value="{{ old('tenure_days') }}"></div></div>
                <div class="col-md-2"><div class="form-group"><label>Expected Return</label>
                    <input type="number" name="expected_return" class="form-control" step="0.01" min="0" value="{{ old('expected_return') }}"></div></div>
            </div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label>Reference / Certificate No.</label>
                    <input type="text" name="reference_number" class="form-control" value="{{ old('reference_number') }}"></div></div>
                <div class="col-md-8"><div class="form-group"><label>Notes</label>
                    <input type="text" name="notes" class="form-control" value="{{ old('notes') }}"></div></div>
            </div>
            <button type="submit" class="btn btn-primary">Save Investment</button>
            <a href="{{ url('sacco/investment') }}" class="btn btn-default">Cancel</a>
        </form>
    </div></div>
</section>
@endsection

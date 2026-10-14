@extends('core::layouts.master')
@section('title') Submit Welfare Claim @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Submit Welfare Claim</h1></div>
</section>
<section class="content">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ url('sacco/welfare/claims/store') }}" enctype="multipart/form-data">
                @csrf
                <h5 class="mb-3">Claim Details</h5>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Member <span class="text-danger">*</span></label>
                            <select name="client_id" class="form-control select2" required>
                                <option value="">— Select Member —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}" {{ old('client_id')==$c->id?'selected':'' }}>
                                        {{ $c->full_name }} ({{ $c->member_number ?? $c->id }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Welfare Product <span class="text-danger">*</span></label>
                            <select name="sacco_welfare_product_id" class="form-control" required>
                                <option value="">— Select Product —</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ old('sacco_welfare_product_id')==$p->id?'selected':'' }}>
                                        {{ $p->name }} ({{ ucfirst($p->type) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Event Date <span class="text-danger">*</span></label>
                            <input type="date" name="event_date" class="form-control" value="{{ old('event_date') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Claim Date <span class="text-danger">*</span></label>
                            <input type="date" name="claim_date" class="form-control" value="{{ old('claim_date', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Amount Claimed <span class="text-danger">*</span></label>
                            <input type="number" name="amount_claimed" class="form-control" step="0.01" min="1" value="{{ old('amount_claimed') }}" required>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control" value="{{ old('remarks') }}">
                        </div>
                    </div>
                </div>

                <h5 class="mb-3 mt-3">Beneficiary Details</h5>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Beneficiary Name <span class="text-danger">*</span></label>
                            <input type="text" name="beneficiary_name" class="form-control" value="{{ old('beneficiary_name') }}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Relationship</label>
                            <input type="text" name="beneficiary_relationship" class="form-control" value="{{ old('beneficiary_relationship') }}" placeholder="e.g. Spouse">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="beneficiary_phone" class="form-control" value="{{ old('beneficiary_phone') }}">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>ID Number</label>
                            <input type="text" name="beneficiary_id_number" class="form-control" value="{{ old('beneficiary_id_number') }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Payout Account / Phone</label>
                            <input type="text" name="beneficiary_account" class="form-control" value="{{ old('beneficiary_account') }}">
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                    <a href="{{ url('sacco/welfare/claims') }}" class="btn btn-default">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection

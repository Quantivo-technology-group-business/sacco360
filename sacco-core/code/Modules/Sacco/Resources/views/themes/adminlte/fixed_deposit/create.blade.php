@extends('core::layouts.master')
@section('title') Create Fixed Deposit @endsection
@section('content')
<section class="content"><form method="post" action="{{url('sacco/fixed_deposit/store')}}">@csrf
<div class="card"><div class="card-body">
    <div class="form-group"><label>Savings Account ID (FD product)</label><input name="savings_id" class="form-control" value="{{request('savings_id')}}" required></div>
    <div class="form-group"><label>Principal</label><input name="principal" type="number" step="0.01" class="form-control"></div>
    <div class="form-group"><label>Tenure (months)</label><input name="tenure_months" type="number" class="form-control" value="12" required></div>
    <div class="form-group"><label>Interest Rate (% p.a.)</label><input name="interest_rate" type="number" step="0.01" class="form-control" value="8"></div>
    <div class="form-group"><label>Renewal Option</label>
        <select name="renewal_option" class="form-control">
            <option value="payout">Payout interest at maturity</option>
            <option value="renew_principal">Renew principal only</option>
            <option value="renew_principal_interest">Renew principal + interest</option>
        </select>
    </div>
    <div class="form-group"><label>Lien Loan ID (optional)</label><input name="lien_loan_id" class="form-control"></div>
    <button class="btn btn-primary">Create Fixed Deposit</button>
</div></div></form></section>
@endsection

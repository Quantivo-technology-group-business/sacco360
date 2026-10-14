@extends('core::layouts.master')
@section('title') Send Pesalink @endsection
@section('content')
<section class="content"><form method="post" action="{{url('sacco/pesalink/store')}}">@csrf
<div class="card"><div class="card-body">
    <div class="form-group"><label>FOSA Savings ID</label><input name="savings_id" class="form-control" required></div>
    <div class="form-group"><label>Beneficiary Account</label><input name="beneficiary_account" class="form-control" required></div>
    <div class="form-group"><label>Beneficiary Bank</label><input name="beneficiary_bank" class="form-control" required></div>
    <div class="form-group"><label>Beneficiary Name</label><input name="beneficiary_name" class="form-control" required></div>
    <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
    <button class="btn btn-primary">Send</button>
</div></div></form></section>
@endsection

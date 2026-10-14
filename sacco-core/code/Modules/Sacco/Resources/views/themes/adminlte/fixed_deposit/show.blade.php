@extends('core::layouts.master')
@section('title') Fixed Deposit #{{$deposit->id}} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <p><strong>Principal:</strong> {{number_format($deposit->principal,2)}}</p>
    <p><strong>Rate:</strong> {{$deposit->interest_rate}}% | <strong>Tenure:</strong> {{$deposit->tenure_months}} months</p>
    <p><strong>Start:</strong> {{$deposit->start_date}} | <strong>Maturity:</strong> {{$deposit->maturity_date}}</p>
    <p><strong>Projected Interest:</strong> {{number_format($projectedInterest,2)}}</p>
    <p><strong>Status:</strong> {{$deposit->status}} | <strong>Renewal:</strong> {{$deposit->renewal_option}}</p>
    @if($deposit->lien_loan_id)<p><strong>Lien Loan:</strong> #{{$deposit->lien_loan_id}}</p>@endif
</div></div></section>
@endsection

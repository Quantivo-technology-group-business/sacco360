@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.guarantor_detail') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <h4>{{ trans('sacco::general.guarantor_detail') }}</h4>
    <p>Loan #{{ $loan->id ?? '' }}</p>
    <p>Name: {{ $guarantor->first_name }} {{ $guarantor->last_name }}</p>
    <p>Relationship: {{ optional($guarantor->client_relationship)->name }}</p>
    <a href="{{ url('portal/loan/' . ($loan->id ?? '') . '/show') }}" class="btn btn-default">{{ trans('core::general.back') }}</a>
</div></div></section>
@endsection

@extends('core::layouts.master')
@section('title') Transfer #{{$transfer->id}} @endsection
@section('content')
<section class="content">
    <div class="card"><div class="card-body">
        <p><strong>Reference:</strong> {{$transfer->reference}}</p>
        <p><strong>Type:</strong> {{$transfer->transfer_type}}</p>
        <p><strong>Amount:</strong> {{number_format($transfer->amount,2)}}</p>
        <p><strong>Date:</strong> {{$transfer->transfer_date}}</p>
        <p><strong>Status:</strong> {{$transfer->status}}</p>
        <a href="{{url('sacco/transfer')}}" class="btn btn-default">Back</a>
    </div></div>
</section>
@endsection

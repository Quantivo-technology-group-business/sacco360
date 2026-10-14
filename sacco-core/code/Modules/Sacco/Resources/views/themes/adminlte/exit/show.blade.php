@extends('core::layouts.master')
@section('title') Exit Settlement #{{$settlement->id}} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Exit Settlement #{{$settlement->id}}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card"><div class="card-body">
        <p><strong>Member:</strong> {{$client->first_name ?? ''}} {{$client->last_name ?? ''}} ({{$settlement->client_id}})</p>
        <p><strong>Reason:</strong> {{ucfirst($settlement->exit_reason)}} | <strong>Date:</strong> {{$settlement->exit_date}}</p>
        <table class="table"><tr><td>BOSA Balance</td><td>{{number_format($settlement->bosa_balance,2)}}</td></tr>
        <tr><td>Share Value</td><td>{{number_format($settlement->share_value,2)}}</td></tr>
        <tr><td>Loan Offset</td><td>{{number_format($settlement->loan_offset,2)}}</td></tr>
        <tr><td>Net Payout</td><td><strong>{{number_format($settlement->net_payout,2)}}</strong></td></tr>
        <tr><td>Status</td><td>{{$settlement->status}}</td></tr></table>
        @if($settlement->status==='draft')
        <a href="{{url('sacco/exit/'.$settlement->id.'/approve')}}" class="btn btn-warning">Approve</a>
        @endif
        @if($settlement->status==='approved')
        <a href="{{url('sacco/exit/'.$settlement->id.'/process')}}" class="btn btn-success" onclick="return confirm('Process settlement? This closes member accounts.')">Process Settlement</a>
        @endif
    </div></div>
</section>
@endsection

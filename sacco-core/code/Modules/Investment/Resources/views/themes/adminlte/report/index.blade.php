@extends('core::layouts.master')
@section('title') Investment Reports @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Investment Reports</h1></div>
</section>
<section class="content">
<div class="row">
    @foreach([
        ['investment/report/portfolio',  'fas fa-wallet',       'primary', 'Investment Portfolio',  'Total invested, profit accrued and paid — by plan and by member.'],
        ['investment/report/maturities', 'fas fa-hourglass-end','warning', 'Maturing Investments',  'Investments approaching or past their maturity date.'],
        ['investment/report/profit-due', 'fas fa-money-bill-wave','success','Profit Due Report',    'Members with accrued profit that has not yet been paid out.'],
    ] as [$url,$icon,$color,$title,$desc])
    <div class="col-md-4">
        <div class="card card-{{$color}} card-outline">
            <div class="card-body text-center py-4">
                <i class="{{$icon}} fa-3x text-{{$color}} mb-3"></i>
                <h5>{{$title}}</h5>
                <p class="text-muted small">{{$desc}}</p>
                <a href="{{url($url)}}" class="btn btn-{{$color}}">View Report</a>
            </div>
        </div>
    </div>
    @endforeach
</div>
</section>
@endsection

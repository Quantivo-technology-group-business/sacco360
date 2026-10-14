@extends('core::layouts.master')
@section('title') SASRA Compliance Dashboard @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>SASRA Compliance Dashboard</h1></div></section>
<section class="content">
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">As of:</label>
        <input type="date" name="as_of" value="{{$asOfDate}}" class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Refresh</button>
        <a href="{{url('sacco/regulatory?as_of='.$asOfDate)}}" class="btn btn-secondary ml-2">Regulatory Reports</a>
        <a href="{{url('sacco/compliance/borrowings')}}" class="btn btn-info ml-2">Borrowings</a>
        <a href="{{url('sacco/compliance/audit-log')}}" class="btn btn-dark ml-2">Audit Log</a>
    </form>
    <div class="row">
        @foreach(['core_capital_absolute','core_capital_to_assets','core_capital_to_deposits','institutional_capital_to_assets','liquidity_ratio','non_earning_assets','external_borrowing'] as $key)
        @php $r = $ratios[$key] ?? null; @endphp
        @if($r)
        <div class="col-md-3 mb-3">
            <div class="card">
                <div class="card-body text-center">
                    <h6>{{$r['label']}}</h6>
                    <h3>{{number_format($r['value'], 2)}}{{$r['unit'] === '%' ? '%' : ''}}</h3>
                    <small>Threshold: {{$r['direction'] === 'min' ? '≥' : '≤'}} {{$r['threshold']}}{{$r['unit'] === '%' ? '%' : ' KES'}}</small><br>
                    <span class="badge badge-{{$r['status'] === 'green' ? 'success' : ($r['status'] === 'amber' ? 'warning' : 'danger')}}">{{strtoupper($r['status'])}}</span>
                </div>
            </div>
        </div>
        @endif
        @endforeach
    </div>
    @if(!empty($ratios['single_member_exposure']['breaches']))
    <div class="alert alert-danger">
        <strong>Member Exposure Breaches:</strong>
        <ul class="mb-0">
            @foreach($ratios['single_member_exposure']['breaches'] as $b)
            <li>{{$b['name']}}: {{$b['ratio']}}% (KES {{number_format($b['exposure'],2)}})</li>
            @endforeach
        </ul>
    </div>
    @endif
</section>
@endsection

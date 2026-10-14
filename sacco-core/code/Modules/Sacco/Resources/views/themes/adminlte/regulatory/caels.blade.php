@extends('core::layouts.master')
@section('title') CAELS Comparative Report @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>CAELS Comparative Report</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <form method="get" class="form-inline mb-3">
        <input type="date" name="as_of" value="{{$asOfDate}}" class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Generate</button>
    </form>
    @can('sacco.classification.override')
    <div class="card mb-3"><div class="card-header">Manual Classification Override</div><div class="card-body">
        <form method="post" action="{{url('sacco/regulatory/classification/override')}}" class="form-inline">@csrf
            <input type="number" name="loan_id" class="form-control mr-2" placeholder="Loan ID" required>
            <select name="classification" class="form-control mr-2" required>
                <option value="Performing">Performing</option><option value="Watch">Watch</option>
                <option value="Substandard">Substandard</option><option value="Doubtful">Doubtful</option><option value="Loss">Loss</option>
            </select>
            <button class="btn btn-warning">Override</button>
        </form>
    </div></div>
    @endcan
    <div class="card mb-3"><div class="card-header">Risk Profile: <strong>{{$report['risk_profile']}}</strong></div></div>
    <div class="card mb-3"><div class="card-header">Trend Analysis</div><div class="card-body">
        <table class="table table-sm"><thead><tr><th>Period</th><th>NPL %</th><th>Provision</th><th>Coverage %</th></tr></thead>
        <tbody>@foreach($report['trends'] as $t)<tr><td>{{$t['period']}}</td><td>{{$t['npl_ratio']}}%</td><td>{{number_format($t['total_provision'],2)}}</td><td>{{$t['provision_coverage']}}%</td></tr>@endforeach</tbody></table>
    </div></div>
    <div class="card"><div class="card-header">Sectoral Breakdown</div><div class="card-body">
        <table class="table table-sm"><thead><tr><th>Sector</th><th>Outstanding</th><th>Provision</th><th>Loans</th><th>NPL</th></tr></thead>
        <tbody>@foreach($report['sectoral'] as $sector => $s)<tr><td>{{$sector}}</td><td>{{number_format($s['outstanding'],2)}}</td><td>{{number_format($s['provision'],2)}}</td><td>{{$s['count']}}</td><td>{{number_format($s['npl'],2)}}</td></tr>@endforeach</tbody></table>
    </div></div>
</section>
@endsection

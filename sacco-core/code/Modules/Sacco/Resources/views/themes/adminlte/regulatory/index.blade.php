@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.sasra_reports') }} @endsection
@section('content')
@php
    $statusBadge = function($status) {
        return $status === 'green' ? 'success' : ($status === 'amber' ? 'warning' : 'danger');
    };
@endphp
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.sasra_reports') }}</h1></div></section>
<section class="content">
    <form method="get" class="form-inline mb-3">
        <label class="mr-2">As of:</label>
        <input type="date" name="as_of" value="{{$asOfDate}}" class="form-control mr-2">
        <button class="btn btn-primary" type="submit">Generate</button>
        <a href="{{url('sacco/regulatory/export?as_of='.$asOfDate)}}" class="btn btn-secondary ml-2" target="_blank">Export JSON</a>
        <a href="{{url('sacco/regulatory/export?as_of='.$asOfDate.'&format=pdf')}}" class="btn btn-danger ml-2" target="_blank">Export PDF</a>
        <a href="{{url('sacco/regulatory/export?as_of='.$asOfDate.'&format=excel')}}" class="btn btn-success ml-2">Export Excel</a>
        <a href="{{url('sacco/regulatory/provision-rates')}}" class="btn btn-info ml-2">NPL Provision Rates</a>
        <a href="{{url('sacco/regulatory/caels?as_of='.$asOfDate)}}" class="btn btn-warning ml-2">CAELS Report</a>
        <a href="{{url('sacco/compliance?as_of='.$asOfDate)}}" class="btn btn-dark ml-2">Compliance Dashboard</a>
    </form>

    <div class="row">
        <div class="col-md-4 mb-3">
            <div class="card"><div class="card-header">Form 1/001: Capital Adequacy</div><div class="card-body">
                <p>Core Capital: {{number_format($reports['form_3']['core_capital'] ?? 0,2)}}</p>
                <p>Total Assets: {{number_format($reports['form_3']['total_assets'] ?? 0,2)}}</p>
                <p>CAR (Assets): <strong>{{$reports['form_3']['capital_adequacy_ratio'] ?? 0}}%</strong></p>
                <p>CAR (Deposits): <strong>{{$reports['form_3']['capital_to_deposits_ratio'] ?? 0}}%</strong></p>
                <span class="badge badge-{{($reports['form_3']['compliant'] ?? false)?'success':'danger'}}">{{($reports['form_3']['compliant'] ?? false)?'Compliant':'Non-compliant'}}</span>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card"><div class="card-header">Form 1/002: Liquidity</div><div class="card-body">
                <p>Liquid Assets: {{number_format($reports['form_4']['liquid_assets'] ?? 0,2)}}</p>
                <p>Total Deposits: {{number_format($reports['form_4']['total_deposits'] ?? 0,2)}}</p>
                <p>Ratio: <strong>{{$reports['form_4']['liquidity_ratio'] ?? 0}}%</strong> (min {{$reports['form_4']['minimum_required'] ?? 15}}%)
                <span class="badge badge-{{$statusBadge($reports['form_4']['status'] ?? 'red')}}">{{$reports['form_4']['status'] ?? 'red'}}</span></p>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card"><div class="card-header">Form 1/004: Asset Quality / NPL</div><div class="card-body">
                <p>NPL Ratio: <strong>{{$reports['form_5']['npl_ratio'] ?? 0}}%</strong></p>
                <p>Total Provision: {{number_format($reports['form_5']['total_provision'] ?? 0,2)}}</p>
                <p>Coverage: {{$reports['form_5']['provision_coverage'] ?? 0}}%</p>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card"><div class="card-header">Form 1/006: Statement of Financial Position</div><div class="card-body" style="max-height:300px;overflow:auto">
                <h6>Assets</h6>
                @foreach(($reports['form_1']['assets'] ?? []) as $a)<p class="mb-0">{{$a->name}}: {{number_format($a->balance,2)}}</p>@endforeach
                <h6 class="mt-2">Liabilities & Equity</h6>
                @foreach(($reports['form_1']['liabilities'] ?? []) as $l)<p class="mb-0">{{$l->name}}: {{number_format($l->balance,2)}}</p>@endforeach
            </div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card"><div class="card-header">Form 1/007: Comprehensive Income</div><div class="card-body">
                <p>Period: {{$reports['form_2']['period'] ?? ''}}</p>
                <p>Total Income: {{number_format($reports['form_2']['total_income'] ?? 0,2)}}</p>
                <p>Total Expenses: {{number_format($reports['form_2']['total_expenses'] ?? 0,2)}}</p>
                <p>Net Income: <strong>{{number_format($reports['form_2']['net_income'] ?? 0,2)}}</strong></p>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card"><div class="card-header">Form 1/003: Deposit Return</div><div class="card-body">
                <p>Total Deposits: {{number_format($reports['form_7']['total_deposits'] ?? 0,2)}}</p>
                <p>BOSA (non-withdrawable): {{number_format($reports['form_7']['bosa_total'] ?? 0,2)}} | FOSA (withdrawable): {{number_format($reports['form_7']['fosa_total'] ?? 0,2)}}</p>
                <p>Non-withdrawable: {{number_format($reports['form_7']['non_withdrawable_deposits'] ?? 0,2)}} | Withdrawable: {{number_format($reports['form_7']['withdrawable_deposits'] ?? 0,2)}}</p>
                <h6>Top Depositors</h6>
                @foreach(($reports['form_7']['top_depositors'] ?? []) as $d)<p class="mb-0">{{$d->name}}: {{number_format($d->balance,2)}}</p>@endforeach
            </div></div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card"><div class="card-header">Form 1/005: Investment Return</div><div class="card-body">
                <p>Investment Income YTD: {{number_format($reports['form_5_investment']['investment_income_ytd'] ?? 0,2)}}</p>
                @foreach(($reports['form_5_investment']['investments'] ?? []) as $inv)<p class="mb-0">{{$inv->name}}: {{number_format($inv->balance,2)}}</p>@endforeach
            </div></div>
        </div>
    </div>

    <div class="card mb-3"><div class="card-header">Form 6: Membership Statistics</div><div class="card-body">
        <p>Active: {{$reports['form_6']['total_members'] ?? 0}} | New YTD: {{$reports['form_6']['new_members_ytd'] ?? 0}}
        | Dormant: {{$reports['form_6']['dormant_members'] ?? 0}} | Defaulting: {{$reports['form_6']['defaulting_members'] ?? 0}}</p>
    </div></div>

    <div class="card mb-3"><div class="card-header">Form 8: Insider Lending</div><div class="card-body">
        <p>Staff/Director Loans: {{count($reports['form_8']['staff_loans'] ?? [])}}</p>
    </div></div>

    <div class="card mb-3"><div class="card-header">Form 4B: Sectorial Lending</div><div class="card-body">
        <table class="table table-sm"><thead><tr><th>Sector</th><th>Loans</th><th>Outstanding</th></tr></thead>
        <tbody>@foreach(($reports['form_4b']['sectors'] ?? []) as $s)<tr><td>{{$s->sector}}</td><td>{{$s->loan_count}}</td><td>{{number_format($s->outstanding,2)}}</td></tr>@endforeach</tbody></table>
    </div></div>

    <div class="card"><div class="card-header">Prudential Ratios Summary</div><div class="card-body">
        <div class="row">
            @foreach(['core_capital_absolute','core_capital_to_assets','core_capital_to_deposits','liquidity_ratio','non_earning_assets','external_borrowing'] as $key)
            @php $r = $reports['prudential_ratios'][$key] ?? null; @endphp
            @if($r)<div class="col-md-4"><p>{{$r['label']}}: <strong>{{number_format($r['value'],2)}}{{$r['unit']==='%'?'%':''}}</strong> <span class="badge badge-{{$statusBadge($r['status'])}}">{{$r['status']}}</span></p></div>@endif
            @endforeach
        </div>
    </div></div>
</section>
@endsection

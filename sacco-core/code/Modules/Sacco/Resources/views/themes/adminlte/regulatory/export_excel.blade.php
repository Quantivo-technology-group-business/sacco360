<table>
    <tr><th colspan="2">SASRA Regulatory Reports - {{$asOfDate}}</th></tr>
    <tr><th>Form 1/001 Capital Adequacy</th><td></td></tr>
    <tr><td>Core Capital</td><td>{{$reports['form_3']['core_capital']??0}}</td></tr>
    <tr><td>CAR (Assets) %</td><td>{{$reports['form_3']['capital_adequacy_ratio']??0}}</td></tr>
    <tr><td>CAR (Deposits) %</td><td>{{$reports['form_3']['capital_to_deposits_ratio']??0}}</td></tr>
    <tr><th>Form 1/002 Liquidity</th><td></td></tr>
    <tr><td>Liquidity Ratio %</td><td>{{$reports['form_4']['liquidity_ratio']??0}}</td></tr>
    <tr><th>Form 1/004 NPL</th><td></td></tr>
    <tr><td>NPL Ratio %</td><td>{{$reports['form_5']['npl_ratio']??0}}</td></tr>
    <tr><td>Total Provision</td><td>{{$reports['form_5']['total_provision']??0}}</td></tr>
    <tr><th>Form 1/007 Income</th><td></td></tr>
    <tr><td>Net Income</td><td>{{$reports['form_2']['net_income']??0}}</td></tr>
</table>

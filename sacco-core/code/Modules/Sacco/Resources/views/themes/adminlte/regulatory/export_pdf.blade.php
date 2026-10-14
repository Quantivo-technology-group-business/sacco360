<html><head><style>body{font-family:Arial;font-size:12px}table{width:100%;border-collapse:collapse}td,th{border:1px solid #ccc;padding:4px}h2{margin-top:20px}</style></head><body>
<h1>SASRA Regulatory Reports</h1><p>As of: {{$asOfDate}}</p>
<h2>Form 1/001 - Capital Adequacy</h2>
<p>Core Capital: {{number_format($reports['form_3']['core_capital']??0,2)}} | CAR: {{$reports['form_3']['capital_adequacy_ratio']??0}}%</p>
<h2>Form 1/002 - Liquidity</h2>
<p>Liquidity Ratio: {{$reports['form_4']['liquidity_ratio']??0}}%</p>
<h2>Form 1/004 - Asset Quality</h2>
<p>NPL Ratio: {{$reports['form_5']['npl_ratio']??0}}% | Provision: {{number_format($reports['form_5']['total_provision']??0,2)}}</p>
<h2>Form 1/006 - Financial Position</h2>
<table><tr><th>Account</th><th>Balance</th></tr>
@foreach(($reports['form_1']['assets']??[]) as $a)<tr><td>{{$a->name}}</td><td>{{number_format($a->balance,2)}}</td></tr>@endforeach
</table>
<h2>Form 1/007 - Comprehensive Income</h2>
<p>Net Income: {{number_format($reports['form_2']['net_income']??0,2)}}</p>
</body></html>

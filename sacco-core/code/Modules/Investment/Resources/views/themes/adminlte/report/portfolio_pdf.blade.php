<!DOCTYPE html><html><head><meta charset="utf-8"><title>Investment Portfolio</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;color:#222}
h2{font-size:14px;margin:0 0 4px}
table{width:100%;border-collapse:collapse;margin-bottom:10px}
th,td{border:1px solid #bbb;padding:3px 5px}
th{background:#e0e0e0;font-weight:bold}
.text-right{text-align:right}
.summary{background:#e8f5e9}
</style></head>
<body>
<h2>Investment Portfolio Report</h2>
<p style="font-size:10px;color:#555">Generated: {{date('d M Y H:i')}}</p>
<table><thead><tr><th>Account #</th><th>Member</th><th>Plan</th><th class="text-right">Principal</th><th class="text-right">Rate</th><th>Maturity</th><th class="text-right">Accrued</th><th class="text-right">Paid</th><th class="text-right">Due</th><th>Status</th></tr></thead>
<tbody>
@foreach($data as $inv)
<tr>
    <td>{{$inv->account_number}}</td>
    <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
    <td>{{optional($inv->plan)->name}}</td>
    <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
    <td class="text-right">{{$inv->effective_rate}}%</td>
    <td>{{$inv->maturity_date?->format('d M Y')??'—'}}</td>
    <td class="text-right">{{number_format($inv->total_profit_accrued,2)}}</td>
    <td class="text-right">{{number_format($inv->total_profit_paid,2)}}</td>
    <td class="text-right">{{number_format($inv->profit_due,2)}}</td>
    <td>{{ucfirst($inv->status)}}</td>
</tr>
@endforeach
</tbody>
<tfoot><tr class="summary">
    <td colspan="3" class="text-right"><strong>TOTAL</strong></td>
    <td class="text-right"><strong>{{number_format($summary['total_invested'],2)}}</strong></td>
    <td></td><td></td>
    <td class="text-right"><strong>{{number_format($summary['total_profit_accrued'],2)}}</strong></td>
    <td class="text-right"><strong>{{number_format($summary['total_profit_paid'],2)}}</strong></td>
    <td class="text-right"><strong>{{number_format($summary['profit_due'],2)}}</strong></td>
    <td></td>
</tr></tfoot>
</table>
</body></html>

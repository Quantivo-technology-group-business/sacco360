<!DOCTYPE html><html><head><meta charset="utf-8"><title>Account Statement</title>
<style>
body{font-family:Arial,sans-serif;font-size:10px;color:#222;margin:15px}
h2{font-size:15px;margin:0 0 3px}
.meta{margin-bottom:10px;font-size:10px;color:#555}
table{width:100%;border-collapse:collapse;margin-bottom:10px}
th,td{border:1px solid #ccc;padding:3px 5px}
th{background:#e0e0e0;font-weight:bold}
.text-right{text-align:right}
.ob{background:#fffde7;font-weight:bold}
.total{background:#f0f0f0;font-weight:bold}
.closing{background:#d4edda;font-weight:bold}
.red{color:#c0392b}
</style></head>
<body>
<h2>Account Statement — [{{$account->gl_code}}] {{$account->name}}</h2>
<div class="meta">
    Period: <strong>{{$start_date}} to {{$end_date}}</strong> &nbsp;|&nbsp;
    Account Type: <strong>{{ucfirst($account->account_type)}}</strong> &nbsp;|&nbsp;
    Printed: {{date('Y-m-d H:i')}}
</div>
<table>
    <thead>
        <tr><th>Date</th><th>Transaction #</th><th>Type</th><th>Notes</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Balance</th></tr>
    </thead>
    <tbody>
        <tr class="ob"><td>{{$start_date}}</td><td colspan="3"><em>Opening Balance</em></td><td></td><td></td><td class="text-right">{{number_format($openingBalance,2)}}</td></tr>
        @foreach($lines as $line)
        <tr>
            <td>{{$line->date}}</td>
            <td>{{$line->transaction_number}}</td>
            <td>{{$line->manual_entry ? 'Manual' : ucfirst(str_replace('_',' ',$line->transaction_type))}}</td>
            <td>{{$line->reference}} {{$line->notes}}</td>
            <td class="text-right">@if(!empty($line->debit)) {{number_format($line->debit,2)}} @endif</td>
            <td class="text-right">@if(!empty($line->credit)) {{number_format($line->credit,2)}} @endif</td>
            <td class="text-right @if($line->running_balance<0) red @endif">{{number_format($line->running_balance,2)}}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr class="total"><td colspan="4" class="text-right">Period Totals</td><td class="text-right">{{number_format($periodDr,2)}}</td><td class="text-right">{{number_format($periodCr,2)}}</td><td class="text-right">{{number_format($closingBalance,2)}}</td></tr>
        <tr class="closing"><td colspan="6" class="text-right">Closing Balance ({{$end_date}})</td><td class="text-right">{{number_format($closingBalance,2)}}</td></tr>
    </tfoot>
</table>
</body></html>

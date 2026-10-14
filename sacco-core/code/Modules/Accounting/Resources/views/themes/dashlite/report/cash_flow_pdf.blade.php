<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Cash Flow Statement</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:11px;color:#333}
        h2,h3{margin:4px 0}
        table{width:100%;border-collapse:collapse;margin-bottom:12px}
        th,td{border:1px solid #bbb;padding:4px 6px}
        th{background:#e0e0e0;font-weight:bold}
        .text-right{text-align:right}
        .section-header{background:#cce5ff;font-weight:bold;font-size:12px;padding:6px}
        .total-row{background:#f0f0f0;font-weight:bold}
        .net-row{background:#d4edda;font-weight:bold;font-size:12px}
        .negative{color:#c0392b}
        .positive{color:#27ae60}
    </style>
</head>
<body>
    <h2>Cash Flow Statement</h2>
    <p>Period: <strong>{{$start_date}} to {{$end_date}}</strong></p>

    @php $categories = ['operating'=>'Operating Activities','investing'=>'Investing Activities','financing'=>'Financing Activities']; @endphp

    @foreach($categories as $catKey => $catLabel)
    @php $catRows = $data->where('cash_flow_category', $catKey); @endphp
    @if($catRows->count())
    <div class="section-header">{{$catLabel}}</div>
    <table>
        <thead>
            <tr><th>GL Code</th><th>Account</th><th>Type</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th class="text-right">Net Flow</th></tr>
        </thead>
        <tbody>
        @foreach($catRows as $row)
        @php
            $net = in_array($row->account_type,['income','liability','equity'])
                 ? ($row->credit - $row->debit) : ($row->debit - $row->credit);
        @endphp
        <tr>
            <td>{{$row->gl_code}}</td><td>{{$row->name}}</td><td>{{ucfirst($row->account_type)}}</td>
            <td class="text-right">{{number_format($row->debit,2)}}</td>
            <td class="text-right">{{number_format($row->credit,2)}}</td>
            <td class="text-right {{$net<0?'negative':'positive'}}">{{number_format($net,2)}}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr class="total-row">
            <td colspan="5" class="text-right">Net {{$catLabel}}</td>
            <td class="text-right">{{number_format($summary[$catKey]??0,2)}}</td>
        </tr>
        </tfoot>
    </table>
    @endif
    @endforeach

    <table>
        <tr class="total-row"><td>Net Cash — Operating</td><td class="text-right">{{number_format($summary['operating']??0,2)}}</td></tr>
        <tr class="total-row"><td>Net Cash — Investing</td><td class="text-right">{{number_format($summary['investing']??0,2)}}</td></tr>
        <tr class="total-row"><td>Net Cash — Financing</td><td class="text-right">{{number_format($summary['financing']??0,2)}}</td></tr>
        <tr class="net-row"><td><strong>Net Change in Cash</strong></td><td class="text-right"><strong>{{number_format($summary['total']??0,2)}}</strong></td></tr>
    </table>
</body>
</html>

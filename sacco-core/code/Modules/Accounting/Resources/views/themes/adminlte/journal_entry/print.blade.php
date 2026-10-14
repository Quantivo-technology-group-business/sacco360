<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Journal Entry — {{$data->first()->transaction_number ?? ''}}</title>
    <style>
        body{font-family:Arial,sans-serif;font-size:12px;color:#222;margin:20px}
        h2{margin:0 0 4px 0;font-size:18px}
        .meta{margin-bottom:16px;font-size:11px;color:#555}
        table{width:100%;border-collapse:collapse;margin-bottom:14px}
        th,td{border:1px solid #ccc;padding:5px 8px}
        th{background:#e8e8e8;font-weight:bold}
        .text-right{text-align:right}
        .total-row{background:#f4f4f4;font-weight:bold}
        .stamp{color:#c0392b;font-size:20px;font-weight:bold;border:3px solid #c0392b;display:inline-block;padding:2px 12px;transform:rotate(-5deg);opacity:.7;float:right;margin-top:-30px}
        .footer{margin-top:30px;font-size:10px;color:#888;border-top:1px solid #ccc;padding-top:8px}
        @media print{
            body{margin:10px}
            .no-print{display:none}
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px">
        <button onclick="window.print()">🖨 Print</button>
        <a href="{{url('accounting/journal_entry/'.(isset($data) && $data->count() ? $data->first()->transaction_number : '').'/pdf')}}">⬇ Download PDF</a>
        <a href="javascript:window.close()">✕ Close</a>
    </div>

    <h2>Journal Entry</h2>
    @if(!empty($data) && $data->count())
    @php $first = $data->first(); @endphp

    @if($first->reversed)
        <div class="stamp">REVERSED</div>
    @endif

    <div class="meta">
        <strong>Transaction #:</strong> {{$first->transaction_number}}<br>
        <strong>Date:</strong> {{$first->date}}<br>
        <strong>Branch:</strong> {{optional($first->branch)->name ?? 'N/A'}}<br>
        <strong>Created by:</strong>
        @if($first->created_by)
            {{$first->created_by->first_name}} {{$first->created_by->last_name}}
        @else System @endif
        &nbsp;&nbsp;<strong>Created on:</strong> {{$first->created_at}}<br>
        @if($first->reference) <strong>Reference:</strong> {{$first->reference}}<br> @endif
        @if($first->notes)     <strong>Notes:</strong> {{$first->notes}}<br> @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Account (GL)</th>
                <th>Type</th>
                <th>Description</th>
                <th class="text-right">Debit</th>
                <th class="text-right">Credit</th>
            </tr>
        </thead>
        <tbody>
        @foreach($data as $i => $line)
        <tr>
            <td>{{$i+1}}</td>
            <td>
                @if($line->chart_of_account)
                    [{{$line->chart_of_account->gl_code}}] {{$line->chart_of_account->name}}
                @endif
            </td>
            <td>{{ucfirst(optional($line->chart_of_account)->account_type ?? '')}}</td>
            <td>{{$line->notes}}</td>
            <td class="text-right">@if(!empty($line->debit)) {{number_format($line->debit,2)}} @endif</td>
            <td class="text-right">@if(!empty($line->credit)) {{number_format($line->credit,2)}} @endif</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{number_format($totalDebit,2)}}</strong></td>
                <td class="text-right"><strong>{{number_format($totalCredit,2)}}</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Printed: {{date('Y-m-d H:i:s')}} &nbsp;|&nbsp; System: Ultimate Loan Manager &nbsp;|&nbsp; Savoir Microsystems
    </div>
    @endif
</body>
</html>

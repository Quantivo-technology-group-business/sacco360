<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Deposit Receipt</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; margin: 0; padding: 20px; color: #222; }
  .receipt { max-width: 380px; margin: 0 auto; border: 2px solid #333; padding: 20px; }
  .header  { text-align: center; border-bottom: 2px dashed #333; padding-bottom: 12px; margin-bottom: 12px; }
  .header h2 { margin: 0; font-size: 18px; }
  .header p  { margin: 3px 0; font-size: 11px; color: #555; }
  .badge { background: #28a745; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 13px; font-weight: bold; }
  table  { width: 100%; border-collapse: collapse; margin: 10px 0; }
  td     { padding: 5px 2px; vertical-align: top; }
  td:last-child { text-align: right; font-weight: bold; }
  .amount-row td { font-size: 20px; font-weight: bold; color: #155724; border-top: 2px solid #333; border-bottom: 2px solid #333; padding: 8px 2px; }
  .footer { text-align: center; margin-top: 14px; font-size: 10px; color: #777; border-top: 1px dashed #aaa; padding-top: 8px; }
  .no-print { text-align: center; margin-bottom: 12px; }
  @media print { .no-print { display: none !important; } body { padding: 5px; } }
</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">🖨 Print Receipt</button>
    <button onclick="window.close()">✕ Close</button>
</div>
<div class="receipt">
    <div class="header">
        <h2>{{ config('app.name', 'Ultimate Loan Manager') }}</h2>
        <p>SAVINGS DEPOSIT RECEIPT</p>
        <span class="badge">DEPOSIT</span>
    </div>

    <table>
        <tr><td>Receipt #</td>     <td>{{ 'TLR-SAV-' . $txn->id }}</td></tr>
        <tr><td>Date</td>          <td>{{ $txn->submitted_on }}</td></tr>
        <tr><td>Processed</td>     <td>{{ $txn->created_at }}</td></tr>
        <tr><td>Account</td>       <td>{{ optional($txn->savings)->account_number ?? ('SAV-' . $txn->savings_id) }}</td></tr>
        <tr><td>Product</td>       <td>{{ optional(optional($txn->savings)->savings_product)->name }}</td></tr>
        <tr><td>Member</td>        <td>{{ optional(optional($txn->savings)->client)->first_name }} {{ optional(optional($txn->savings)->client)->last_name }}</td></tr>
        <tr><td>Payment Type</td>  <td>{{ optional(optional($txn->payment_detail)->payment_type)->name ?? 'Cash' }}</td></tr>
        @if(optional($txn->payment_detail)->receipt)
        <tr><td>Ref #</td>         <td>{{ $txn->payment_detail->receipt }}</td></tr>
        @endif
        <tr><td>Teller</td>        <td>{{ Auth::user()->first_name ?? '' }} {{ Auth::user()->last_name ?? '' }}</td></tr>
        @if($teller)<tr><td>Till</td><td>{{ $teller->till_number ?? 'Default' }}</td></tr>@endif
    </table>

    <table>
        <tr class="amount-row">
            <td>AMOUNT DEPOSITED</td>
            <td>{{ number_format($txn->amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        Thank you for your deposit.<br>
        Please keep this receipt for your records.<br>
        Powered by Savoir Microsystems
    </div>
</div>
</body>
</html>

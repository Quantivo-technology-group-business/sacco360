@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.deposit_to_fosa') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.mpesa_deposit_instructions') }}</h1></div></section>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="card"><div class="card-body">
                <h5>How to deposit via M-Pesa PayBill</h5>
                <ol>
                    <li>Go to M-Pesa &gt; Lipa na M-Pesa &gt; Pay Bill</li>
                    <li>Enter Business Number: <strong>{{ $paybill ?: 'Contact SACCO for PayBill' }}</strong></li>
                    <li>Enter Account Number: <strong>{{ $memberNumber }}</strong></li>
                    <li>Enter amount and confirm with your M-Pesa PIN</li>
                </ol>
                <p class="text-muted">Funds are credited to your FOSA account automatically.</p>
                @if($fosa)
                    <p>FOSA Account: <strong>#{{ $fosa->account_number ?? $fosa->id }}</strong></p>
                @endif
            </div></div>
        </div>
        <div class="col-md-6">
            <div class="card"><div class="card-header">{{ trans('sacco::general.recent_deposits') }}</div><div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Trans ID</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @forelse($recentDeposits as $d)
                        <tr>
                            <td>{{ $d->trans_id }}</td>
                            <td>{{ number_format($d->trans_amount, 2) }}</td>
                            <td>{{ $d->status }}</td>
                            <td>{{ $d->created_at }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No recent deposits.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div></div>
        </div>
    </div>
</section>
@endsection

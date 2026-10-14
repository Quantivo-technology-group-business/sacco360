@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.atm_transactions') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.atm_transactions') }}</h1></div></section>
<section class="content">
    <div class="alert alert-info">ATM API endpoint: <code>POST /api/v1/sacco/atm</code> — params: terminal_id, member_number, transaction_type, amount, pin</div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-sm">
            <thead><tr><th>Ref</th><th>Terminal</th><th>Member</th><th>Type</th><th>Amount</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>{{$t->reference}}</td>
                    <td>{{$t->terminal_id}}</td>
                    <td>{{$t->member_number}}</td>
                    <td>{{$t->transaction_type}}</td>
                    <td>{{number_format($t->amount,2)}}</td>
                    <td><span class="badge badge-{{$t->status==='completed'?'success':'danger'}}">{{$t->status}}</span></td>
                    <td>{{$t->created_at}}</td>
                </tr>
            @empty
                <tr><td colspan="7">No ATM transactions yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{$transactions->links()}}
    </div></div>
</section>
@endsection

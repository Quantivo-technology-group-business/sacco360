@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.mpesa_transactions') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.mpesa_transactions') }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>{{ trans('core::general.date') }}</th><th>{{ trans('core::general.type') }}</th><th>{{ trans('core::general.amount') }}</th><th>{{ trans('sacco::general.recipient_phone') }}</th><th>{{ trans('sacco::general.source_account') }}</th><th>{{ trans('core::general.status') }}</th><th>{{ trans('core::general.reference') }}</th></tr></thead>
            <tbody>
            @foreach($transactions as $t)
                <tr>
                    <td>{{ $t->created_at }}</td>
                    <td>{{ $t->transaction_type === 'withdraw' ? trans('sacco::general.withdraw_mpesa') : trans('sacco::general.send_mpesa') }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->recipient_phone }}</td>
                    <td>{{ strtoupper($t->source_type) }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->mpesa_receipt ?: $t->reference }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div></div>
</section>
@endsection

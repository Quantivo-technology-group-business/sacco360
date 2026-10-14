@extends('core::layouts.master')
@section('title')
    {{ trans_choice('share::general.share',1) }} {{ trans_choice('core::general.detail',2) }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ $share->share_product->name }} (#{{ $share->id }})</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><strong>{{ trans_choice('share::general.status',1) }}</strong><br>{{ ucfirst($share->status) }}</div>
                    <div class="col-md-3"><strong>{{ trans_choice('share::general.total_shares',1) }}</strong><br>{{ number_format($share->total_shares) }}</div>
                    <div class="col-md-3"><strong>{{ trans('sacco::general.share_value') }}</strong><br>{{ number_format($share->total_shares * ($share->nominal_price ?? $share->share_product->nominal_price ?? 0), $share->decimals ?? 2) }}</div>
                    <div class="col-md-3"><strong>{{ trans('sacco::general.guarantor_locked_shares') }}</strong><br>{{ number_format($share->locked_shares ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h3 class="card-title">{{ trans_choice('share::general.transaction',2) }}</h3></div>
            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>{{ trans_choice('core::general.date',1) }}</th>
                        <th>{{ trans_choice('core::general.name',1) }}</th>
                        <th>{{ trans_choice('core::general.amount',1) }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($share->transactions as $txn)
                        <tr>
                            <td>{{ $txn->submitted_on }}</td>
                            <td>{{ $txn->name }}</td>
                            <td>{{ number_format($txn->amount, $share->decimals ?? 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>
@endsection

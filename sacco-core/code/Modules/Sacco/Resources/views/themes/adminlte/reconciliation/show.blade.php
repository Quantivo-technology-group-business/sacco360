@extends('core::layouts.master')
@section('title') Reconciliation #{{$reconciliation->id}} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Bank Reconciliation #{{$reconciliation->id}}</h1></div></section>
<section class="content">
    <div class="row mb-3">
        <div class="col-md-3"><strong>Account:</strong> {{$reconciliation->chart_of_account->name ?? ''}}</div>
        <div class="col-md-2"><strong>Statement:</strong> {{number_format($reconciliation->statement_balance,2)}}</div>
        <div class="col-md-2"><strong>Book:</strong> {{number_format($reconciliation->book_balance,2)}}</div>
        <div class="col-md-2"><strong>Variance:</strong> {{number_format($reconciliation->variance ?? 0,2)}}</div>
        <div class="col-md-2"><span class="badge badge-{{$reconciliation->status==='reconciled'?'success':'warning'}}">{{$reconciliation->status}}</span></div>
        <div class="col-md-1"><a href="{{url('sacco/reconciliation/'.$reconciliation->id.'/auto-match')}}" class="btn btn-sm btn-primary">Auto-Match</a></div>
    </div>
    <div class="card"><div class="card-body">
        <table class="table table-sm table-bordered">
            <thead><tr><th>Date</th><th>Source</th><th>Description</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
            @foreach($reconciliation->items as $item)
                <tr class="{{$item->match_status==='matched'?'table-success':''}}">
                    <td>{{$item->transaction_date}}</td>
                    <td>{{ucfirst($item->source)}}</td>
                    <td>{{$item->description}}</td>
                    <td>{{$item->entry_type}}</td>
                    <td>{{number_format($item->amount,2)}}</td>
                    <td>{{$item->match_status}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div></div>
</section>
@endsection

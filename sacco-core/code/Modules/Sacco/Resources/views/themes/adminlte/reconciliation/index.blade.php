@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.bank_reconciliation') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.bank_reconciliation') }}</h1></div></section>
<section class="content">
    <a href="{{url('sacco/reconciliation/create')}}" class="btn btn-primary btn-sm mb-2">New Reconciliation</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Bank Account</th><th>Statement Date</th><th>Statement Bal.</th><th>Book Bal.</th><th>Variance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($reconciliations as $r)
                <tr>
                    <td>{{$r->chart_of_account->name ?? 'N/A'}}</td>
                    <td>{{$r->statement_date}}</td>
                    <td>{{number_format($r->statement_balance,2)}}</td>
                    <td>{{number_format($r->book_balance,2)}}</td>
                    <td>{{number_format($r->variance ?? 0,2)}}</td>
                    <td><span class="badge badge-{{$r->status==='reconciled'?'success':($r->status==='discrepancy'?'danger':'warning')}}">{{$r->status}}</span></td>
                    <td><a href="{{url('sacco/reconciliation/'.$r->id.'/show')}}" class="btn btn-xs btn-info">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7">No reconciliations yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{$reconciliations->links()}}
    </div></div>
</section>
@endsection

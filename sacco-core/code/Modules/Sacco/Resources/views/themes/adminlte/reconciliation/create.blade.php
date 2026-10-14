@extends('core::layouts.master')
@section('title') Start {{ trans('sacco::general.bank_reconciliation') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Start Bank Reconciliation</h1></div></section>
<section class="content">
    <form method="post" action="{{url('sacco/reconciliation/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Bank GL Account</label>
                <select name="chart_of_account_id" class="form-control" required>
                    @foreach($bankAccounts as $a)<option value="{{$a->id}}">{{$a->name}} ({{$a->gl_code}})</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Branch (optional)</label>
                <select name="branch_id" class="form-control"><option value="">All Branches</option>
                    @foreach($branches as $b)<option value="{{$b->id}}">{{$b->name}}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Statement Date</label><input type="date" name="statement_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
            <div class="form-group"><label>Statement Closing Balance</label><input type="number" step="0.01" name="statement_balance" class="form-control" required></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Start & Import Book Entries</button></div></div>
    </form>
</section>
@endsection

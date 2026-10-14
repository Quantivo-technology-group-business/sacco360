@extends('core::layouts.master')
@section('title') New Transfer @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>BOSA/FOSA Transfer</h1></div></section>
<section class="content">
    <form method="post" action="{{url('sacco/transfer/store')}}">
        @csrf
        <div class="card"><div class="card-body">
            <div class="form-group">
                <label>Client</label>
                <select name="client_id" class="form-control" required onchange="location='?client_id='+this.value">
                    <option value="">Select</option>
                    @foreach($clients as $c)<option value="{{$c->id}}" @if($clientId==$c->id) selected @endif>{{$c->first_name}} {{$c->last_name}}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Transfer Type</label>
                <select name="transfer_type" class="form-control" required>
                    <option value="savings_to_loan">Savings to Loan Repayment</option>
                    <option value="savings_to_savings">Savings to Savings</option>
                    <option value="share_to_savings">Share to Savings</option>
                </select>
            </div>
            <div class="form-group"><label>Source Account</label>
                <select name="source_id" class="form-control" required>
                    @foreach($savings as $s)<option value="{{$s->id}}">Savings #{{$s->account_number ?: $s->id}}</option>@endforeach
                    @foreach($shares as $sh)<option value="{{$sh->id}}">Share #{{$sh->id}}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Destination</label>
                <select name="destination_id" class="form-control" required>
                    @foreach($loans as $l)<option value="{{$l->id}}">Loan #{{$l->id}}</option>@endforeach
                    @foreach($savings as $s)<option value="{{$s->id}}">Savings #{{$s->account_number ?: $s->id}}</option>@endforeach
                </select>
            </div>
            <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <div class="form-group"><label>Date</label><input type="date" name="transfer_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
            <button type="submit" class="btn btn-primary">Execute Transfer</button>
        </div></div>
    </form>
</section>
@endsection

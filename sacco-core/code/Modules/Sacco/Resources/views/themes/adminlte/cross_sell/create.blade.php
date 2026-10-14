@extends('core::layouts.master')
@section('title') New Cross-Sell Campaign @endsection
@section('content')
<section class="content"><form method="post" action="{{url('sacco/cross_sell/store')}}">@csrf
<div class="card"><div class="card-body">
    <div class="form-group"><label>Campaign Name</label><input name="name" class="form-control" required></div>
    <div class="form-group"><label>Source Wing</label><select name="source_wing" class="form-control"><option value="fosa">FOSA</option><option value="bosa">BOSA</option><option value="any">Any</option></select></div>
    <div class="form-group"><label>Target Wing</label><select name="target_wing" class="form-control"><option value="bosa">BOSA</option><option value="fosa">FOSA</option><option value="share">Shares</option></select></div>
    <div class="form-group"><label>Minimum Source Balance</label><input name="min_balance" type="number" step="0.01" class="form-control" value="10000"></div>
    <div class="form-group"><label>Message</label><textarea name="message" class="form-control"></textarea></div>
    <input type="hidden" name="active" value="1">
    <button class="btn btn-primary">Create</button>
</div></div></form></section>
@endsection

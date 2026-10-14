@extends('core::layouts.master')
@section('title') Declare Dividend @endsection
@section('content')
<section class="content">
    <form method="post" action="{{url('sacco/dividend/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Share Product</label>
                <select name="share_product_id" class="form-control" required>@foreach($products as $p)<option value="{{$p->id}}">{{$p->name}}</option>@endforeach</select>
            </div>
            <div class="form-group"><label>Name</label><input name="name" class="form-control" required></div>
            <div class="form-group"><label>Dividend Per Share</label><input type="number" step="0.0001" name="dividend_per_share" class="form-control" required></div>
            <div class="form-group"><label>Declaration Date</label><input type="date" name="declaration_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
            <div class="form-group"><label>Payment Date</label><input type="date" name="payment_date" class="form-control"></div>
            <div class="form-group"><label>Payout Method</label>
                <select name="payout_method" class="form-control"><option value="savings">To Savings</option><option value="reinvest">Reinvest in Shares</option><option value="cash">Cash</option></select>
            </div>
            <div class="form-group"><label>AGM Resolution</label><textarea name="agm_resolution" class="form-control"></textarea></div>
            <button type="submit" class="btn btn-primary">Declare</button>
        </div></div>
    </form>
</section>
@endsection

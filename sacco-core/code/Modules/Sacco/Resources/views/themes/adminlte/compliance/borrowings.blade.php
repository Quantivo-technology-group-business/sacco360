@extends('core::layouts.master')
@section('title') External Borrowings @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>External Borrowings</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    @if($borrowingRatio)
    <div class="alert alert-{{$borrowingRatio['status']==='green'?'success':($borrowingRatio['status']==='amber'?'warning':'danger')}}">
        External Borrowing Ratio: <strong>{{number_format($borrowingRatio['value'],2)}}%</strong> (max {{$borrowingRatio['threshold']}}%)
    </div>
    @endif
    <div class="card mb-3"><div class="card-body">
        <form method="post" action="{{url('sacco/compliance/borrowings/store')}}">@csrf
            <div class="row">
                <div class="col-md-2"><input name="lender_name" class="form-control" placeholder="Lender" required></div>
                <div class="col-md-2"><input name="principal" type="number" step="0.01" class="form-control" placeholder="Principal" required></div>
                <div class="col-md-2"><input name="outstanding" type="number" step="0.01" class="form-control" placeholder="Outstanding" required></div>
                <div class="col-md-1"><input name="interest_rate" type="number" step="0.01" class="form-control" placeholder="Rate %"></div>
                <div class="col-md-2"><input type="date" name="disbursement_date" class="form-control" required></div>
                <div class="col-md-2"><input type="date" name="maturity_date" class="form-control"></div>
                <div class="col-md-1"><label><input type="checkbox" name="sasra_approved" value="1"> SASRA OK</label></div>
            </div>
            <button class="btn btn-primary mt-2">Add Borrowing</button>
        </form>
    </div></div>
    <div class="card"><div class="card-body"><table class="table"><thead><tr><th>Lender</th><th>Principal</th><th>Outstanding</th><th>Rate</th><th>Disbursed</th><th>Maturity</th><th>SASRA</th><th>Status</th></tr></thead>
    <tbody>@foreach($borrowings as $b)<tr><td>{{$b->lender_name}}</td><td>{{number_format($b->principal,2)}}</td><td>{{number_format($b->outstanding,2)}}</td>
    <td>{{$b->interest_rate}}%</td><td>{{$b->disbursement_date}}</td><td>{{$b->maturity_date}}</td><td>{{$b->sasra_approved?'Yes':'No'}}</td><td>{{$b->status}}</td></tr>@endforeach</tbody></table></div></div>
</section>
@endsection

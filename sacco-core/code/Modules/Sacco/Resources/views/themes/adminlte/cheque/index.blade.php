@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.cheque_processing') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.cheque_processing') }}</h1></div></section>
<section class="content">
    <a href="{{url('sacco/cheque/create')}}" class="btn btn-primary mb-2">Register Cheque</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>#</th><th>Cheque No</th><th>Bank</th><th>Amount</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>@foreach($cheques as $c)<tr>
                <td>{{$c->id}}</td><td>{{$c->cheque_number}}</td><td>{{$c->bank_name}}</td>
                <td>{{number_format($c->amount,2)}}</td><td>{{$c->cheque_date}}</td><td>{{$c->status}}</td>
                <td>
                    @if($c->status==='received')<a href="{{url('sacco/cheque/'.$c->id.'/present')}}" class="btn btn-xs btn-info">Present</a>@endif
                    @if($c->status==='presented')<a href="{{url('sacco/cheque/'.$c->id.'/clear')}}" class="btn btn-xs btn-success">Clear</a>
                    <a href="{{url('sacco/cheque/'.$c->id.'/bounce')}}" class="btn btn-xs btn-danger">Bounce</a>@endif
                </td>
            </tr>@endforeach</tbody>
        </table>{{$cheques->links()}}
    </div></div>
</section>
@endsection

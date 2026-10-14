@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.standing_order',2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.standing_order',2) }}</h1></div></section>
<section class="content">
    @can('sacco.standing_orders.create')<a href="{{url('sacco/standing_order/create')}}" class="btn btn-primary mb-2">New Standing Order</a>@endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Client</th><th>Amount</th><th>Frequency</th><th>Next Run</th><th>Status</th></tr></thead>
            <tbody>@foreach($orders as $o)<tr>
                <td>{{$o->id}}</td><td>@if($o->client){{$o->client->first_name}} {{$o->client->last_name}}@endif</td>
                <td>{{number_format($o->amount,2)}}</td><td>{{$o->frequency}}</td><td>{{$o->next_run_date}}</td><td>{{$o->status}}</td>
            </tr>@endforeach</tbody>
        </table>{{$orders->links()}}
    </div></div>
</section>
@endsection

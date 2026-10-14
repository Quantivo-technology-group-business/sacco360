@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.bosa_fosa_transfer',2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.bosa_fosa_transfer',2) }}</h1></div></section>
<section class="content">
    @can('sacco.transfers.create')<a href="{{url('sacco/transfer/create')}}" class="btn btn-primary mb-2">{{trans_choice('core::general.add',1)}} Transfer</a>@endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Client</th><th>Type</th><th>Amount</th><th>Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @foreach($transfers as $t)
                <tr>
                    <td>{{$t->id}}</td>
                    <td>@if($t->client){{$t->client->first_name}} {{$t->client->last_name}}@endif</td>
                    <td>{{$t->transfer_type}}</td>
                    <td>{{number_format($t->amount,2)}}</td>
                    <td>{{$t->transfer_date}}</td>
                    <td>{{$t->status}}</td>
                    <td><a href="{{url('sacco/transfer/'.$t->id.'/show')}}" class="btn btn-sm btn-info">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        {{$transfers->links()}}
    </div></div>
</section>
@endsection

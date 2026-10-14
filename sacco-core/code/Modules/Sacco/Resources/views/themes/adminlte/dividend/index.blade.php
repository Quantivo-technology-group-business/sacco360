@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.dividend',2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.dividend',2) }}</h1></div></section>
<section class="content">
    @can('sacco.dividends.create')<a href="{{url('sacco/dividend/create')}}" class="btn btn-primary mb-2">Declare Dividend</a>@endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Name</th><th>Per Share</th><th>Pool</th><th>Status</th><th></th></tr></thead>
            <tbody>@foreach($dividends as $d)<tr>
                <td>{{$d->id}}</td><td>{{$d->name}}</td><td>{{number_format($d->dividend_per_share,4)}}</td>
                <td>{{number_format($d->total_pool ?? 0,2)}}</td><td>{{$d->status}}</td>
                <td><a href="{{url('sacco/dividend/'.$d->id.'/show')}}" class="btn btn-sm btn-info">View</a></td>
            </tr>@endforeach</tbody>
        </table>{{$dividends->links()}}
    </div></div>
</section>
@endsection

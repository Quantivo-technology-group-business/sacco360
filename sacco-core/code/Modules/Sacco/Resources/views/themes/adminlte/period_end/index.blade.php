@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.period_end') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.period_end') }}</h1></div></section>
<section class="content">
    <a href="{{url('sacco/period_end/create')}}" class="btn btn-primary mb-2">New Financial Period</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Name</th><th>Start</th><th>End</th><th>Status</th><th></th></tr></thead>
            <tbody>@foreach($periods as $p)<tr>
                <td>{{$p->name}}</td><td>{{$p->start_date}}</td><td>{{$p->end_date}}</td><td>{{$p->status}}</td>
                <td>@if($p->status==='open')<a href="{{url('sacco/period_end/'.$p->id.'/close')}}" class="btn btn-sm btn-danger confirm">Close Period</a>@endif</td>
            </tr>@endforeach</tbody>
        </table>{{$periods->links()}}
    </div></div>
</section>
@endsection

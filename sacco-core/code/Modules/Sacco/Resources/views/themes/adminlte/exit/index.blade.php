@extends('core::layouts.master')
@section('title') Exit Settlements @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Member Exit Settlements</h1>
<a href="{{url('sacco/exit/create')}}" class="btn btn-primary btn-sm">New Settlement</a></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    <div class="card"><div class="card-body"><table class="table table-striped">
        <thead><tr><th>ID</th><th>Client</th><th>Reason</th><th>Exit Date</th><th>Net Payout</th><th>Status</th><th></th></tr></thead>
        <tbody>@foreach($settlements as $s)
        <tr>
            <td>{{$s->id}}</td>
            <td>{{$s->client_id}}</td>
            <td>{{ucfirst($s->exit_reason)}}</td>
            <td>{{$s->exit_date}}</td>
            <td>{{number_format($s->net_payout,2)}}</td>
            <td><span class="badge badge-info">{{$s->status}}</span></td>
            <td><a href="{{url('sacco/exit/'.$s->id.'/show')}}" class="btn btn-xs btn-default">View</a></td>
        </tr>@endforeach</tbody>
    </table>{{$settlements->links()}}</div></div>
</section>
@endsection

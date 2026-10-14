@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.earmark',2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.earmark',2) }}</h1></div></section>
<section class="content">
    <div class="mb-2">
        @can('sacco.earmarks.create')
        <a href="{{url('sacco/earmark/create')}}" class="btn btn-primary btn-sm">Add Earmark</a>
        @endcan
    </div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Member</th><th>Title</th><th>Severity</th><th>Restriction</th><th>Teller Alert</th><th>Expires</th><th></th></tr></thead>
            <tbody>
            @forelse($earmarks as $e)
                <tr class="{{$e->severity==='critical'?'table-danger':($e->severity==='warning'?'table-warning':'')}}">
                    <td><a href="{{url('client/'.$e->client_id.'/show')}}">{{$e->client->first_name ?? ''}} {{$e->client->last_name ?? ''}}</a></td>
                    <td>{{$e->title}}</td>
                    <td>{{ucfirst($e->severity)}}</td>
                    <td>{{str_replace('_',' ',$e->restriction_type)}}</td>
                    <td>{{$e->show_at_teller ? 'Yes' : 'No'}}</td>
                    <td>{{$e->expires_at ?? 'Never'}}</td>
                    <td>
                        @if($e->status==='active')
                        <a href="{{url('sacco/earmark/'.$e->id.'/revoke')}}" class="btn btn-xs btn-danger" onclick="return confirm('Revoke this earmark?')">Revoke</a>
                        @endif
                    </td>
                </tr>
                <tr><td colspan="7"><small>{{$e->message}}</small></td></tr>
            @empty
                <tr><td colspan="7">No active earmarks.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{$earmarks->links()}}
    </div></div>
</section>
@endsection

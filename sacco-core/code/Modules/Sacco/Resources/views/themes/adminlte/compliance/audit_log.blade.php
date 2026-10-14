@extends('core::layouts.master')
@section('title') Compliance Audit Log @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Compliance Audit Log</h1><small>Append-only — no delete permitted</small></div></section>
<section class="content">
    <div class="card"><div class="card-body"><table class="table table-sm"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Module</th><th>Description</th><th>IP</th></tr></thead>
    <tbody>@foreach($logs as $l)<tr>
        <td>{{$l->created_at}}</td><td>{{$l->user_id}}</td><td>{{$l->action}}</td><td>{{$l->module}}</td>
        <td>{{$l->description}}</td><td>{{$l->ip_address}}</td>
    </tr>@endforeach</tbody></table>{{$logs->links()}}</div></div>
</section>
@endsection

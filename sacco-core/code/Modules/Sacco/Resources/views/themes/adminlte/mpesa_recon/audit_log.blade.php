@extends('core::layouts.master')
@section('title') Audit Log — {{$recon->reference}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-7">
            <h1>
                <i class="fas fa-history mr-2 text-secondary"></i>Audit Log
                <small class="text-muted ml-2">{{$recon->reference}}</small>
                <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/show')}}" class="btn btn-sm btn-outline-secondary ml-2">← Back</a>
            </h1>
        </div>
        <div class="col-sm-5"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/'.$recon->id.'/show')}}">{{$recon->reference}}</a></li>
            <li class="breadcrumb-item active">Audit Log</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

<div class="alert alert-info py-2" style="font-size:.83rem">
    <i class="fas fa-lock mr-1"></i>
    Audit logs are <strong>immutable</strong> — records cannot be edited or deleted. Every action is permanently recorded with timestamp, user, IP address, and before/after state.
</div>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            All Events — {{$recon->reference}}
            <span class="badge badge-secondary ml-2">{{$logs->total()}} entries</span>
        </h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:140px">Timestamp</th>
                    <th style="width:160px">Action</th>
                    <th>Description</th>
                    <th style="width:120px">User</th>
                    <th>Before → After</th>
                    <th>Amounts</th>
                    <th style="width:100px">IP</th>
                    <th>Meta</th>
                </tr>
            </thead>
            <tbody>
            @forelse($logs as $log)
            @php
                $actionColors = [
                    'session_created'   => 'success',
                    'csv_imported'      => 'info',
                    'auto_matched'      => 'primary',
                    'item_matched'      => 'success',
                    'item_unmatched'    => 'warning',
                    'exception_flagged' => 'danger',
                    'exception_resolved'=> 'success',
                    'approved'          => 'success',
                    'status_changed'    => 'secondary',
                ];
                $color = $actionColors[$log->action] ?? 'secondary';
            @endphp
            <tr>
                <td style="font-size:.72rem;white-space:nowrap">
                    {{$log->created_at->format('d M Y')}}
                    <br><strong>{{$log->created_at->format('H:i:s')}}</strong>
                </td>
                <td><span class="badge badge-{{$color}}">{{str_replace('_',' ',strtoupper($log->action))}}</span></td>
                <td style="font-size:.82rem">{{$log->description}}</td>
                <td style="font-size:.78rem">
                    @if($log->user_id)
                        @if($log->user) {{$log->user->first_name}} {{$log->user->last_name}} @else User #{{$log->user_id}} @endif
                    @else <span class="text-muted">System</span> @endif
                </td>
                <td style="font-size:.75rem">
                    @if($log->status_before || $log->status_after)
                        <span class="text-muted">{{$log->status_before ?? '—'}}</span>
                        <i class="fas fa-arrow-right mx-1 text-muted"></i>
                        <strong>{{$log->status_after ?? '—'}}</strong>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td style="font-size:.75rem">
                    @if($log->amount_before !== null || $log->amount_after !== null)
                        {{$log->amount_before !== null ? number_format($log->amount_before,2) : '—'}}
                        → {{$log->amount_after !== null ? number_format($log->amount_after,2) : '—'}}
                    @else —
                    @endif
                </td>
                <td style="font-size:.7rem">{{$log->ip_address}}</td>
                <td>
                    @if($log->meta)
                    <button type="button" class="btn btn-xs btn-outline-secondary" data-toggle="popover"
                            data-content='<pre style="font-size:.7rem">{{json_encode($log->meta, JSON_PRETTY_PRINT)}}</pre>'
                            data-html="true" data-placement="left">
                        <i class="fas fa-code"></i>
                    </button>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-3">No audit entries yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-3 py-2">{{$logs->links()}}</div>
    </div>
</div>
</section>
@endsection
@section('scripts')
<script>
$(function(){ $('[data-toggle="popover"]').popover({ trigger: 'click', sanitize: false }); });
</script>
@endsection

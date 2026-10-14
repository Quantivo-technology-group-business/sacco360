@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.aml_alerts') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.aml_alerts') }}</h1></div></section>
<section class="content">
    <a href="{{url('sacco/aml/run')}}" class="btn btn-warning mb-2">Run AML Scan</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Type</th><th>Severity</th><th>Description</th><th>Amount</th><th>Status</th><th>SAR</th><th>Actions</th></tr></thead>
            <tbody>@foreach($alerts as $a)<tr>
                <td>{{$a->id}}</td><td>{{$a->alert_type}}</td>
                <td><span class="badge badge-{{$a->severity==='high'?'danger':($a->severity==='medium'?'warning':'info')}}">{{$a->severity}}</span></td>
                <td>{{$a->description}}</td><td>{{$a->amount ? number_format($a->amount,2) : '-'}}</td><td>{{$a->status}}</td>
                <td><span class="badge badge-{{($a->sar_status??'open')==='reported'?'success':(($a->sar_status??'open')==='reviewed'?'warning':'secondary')}}">{{$a->sar_status ?? 'open'}}</span></td>
                <td>
                    <form method="post" action="{{url('sacco/aml/'.$a->id.'/review')}}" class="d-inline">@csrf
                        <select name="sar_status" class="form-control form-control-sm d-inline-block" style="width:auto">
                            <option value="reviewed">Mark Reviewed</option>
                            <option value="reported">Mark Reported to FRC</option>
                        </select>
                        <button class="btn btn-xs btn-primary">Update SAR</button>
                    </form>
                    @if($a->status==='open')
                    <form method="post" action="{{url('sacco/aml/'.$a->id.'/review')}}" class="d-inline">@csrf
                        <input type="hidden" name="status" value="closed"><button class="btn btn-xs btn-success">Close</button>
                    </form>
                    @endif
                </td>
            </tr>@endforeach</tbody>
        </table>{{$alerts->links()}}
    </div></div>
</section>
@endsection

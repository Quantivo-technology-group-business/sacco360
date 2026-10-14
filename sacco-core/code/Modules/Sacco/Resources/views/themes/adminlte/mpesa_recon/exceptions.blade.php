@extends('core::layouts.master')
@section('title') Exceptions — {{$recon->reference}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6">
            <h1>Exceptions & Unmatched — {{$recon->reference}}
                <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/show')}}" class="btn btn-sm btn-outline-secondary ml-2">← Session</a>
            </h1>
        </div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/'.$recon->id.'/show')}}">{{$recon->reference}}</a></li>
            <li class="breadcrumb-item active">Exceptions</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

{{-- Flagged exceptions --}}
<div class="card card-danger card-outline">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-exclamation-circle mr-1"></i>Flagged Exceptions ({{$exceptions->total()}})</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr>
                <th>Receipt #</th><th>Time</th><th>Amount</th><th>Dir</th>
                <th>Exception Reason</th><th>Notes</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($exceptions as $item)
            <tr style="background:#fef2f2">
                <td><strong>{{$item->receipt_number}}</strong></td>
                <td><small>{{optional($item->completion_time)?->format('d M H:i')}}</small></td>
                <td class="font-weight-bold">{{number_format($item->amount,2)}}</td>
                <td><span class="badge badge-{{$item->direction==='in'?'success':'danger'}}">{{strtoupper($item->direction)}}</span></td>
                <td class="text-danger">{{$item->exception_reason}}</td>
                <td><small>{{$item->match_notes}}</small></td>
                <td>
                    @can('sacco.mpesa_recon.create')
                    <button type="button" class="btn btn-xs btn-success btn-resolve" data-item-id="{{$item->id}}">Resolve</button>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted py-2">No flagged exceptions.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-3 py-2">{{$exceptions->links()}}</div>
    </div>
</div>

{{-- Unmatched items --}}
<div class="card card-warning card-outline">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-question-circle mr-1"></i>Unmatched Statement Items ({{$unmatched->total()}})</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
            <thead><tr>
                <th>Receipt #</th><th>Time</th><th>Details</th><th>Phone</th><th>Acct Ref</th>
                <th class="text-right">Amount</th><th>Dir</th><th>Actions</th>
            </tr></thead>
            <tbody>
            @forelse($unmatched as $item)
            <tr>
                <td><strong>{{$item->receipt_number}}</strong></td>
                <td><small>{{optional($item->completion_time)?->format('d M H:i')}}</small></td>
                <td><small>{{$item->details}}</small></td>
                <td>{{$item->paid_in_number}}</td>
                <td>{{$item->account_reference}}</td>
                <td class="text-right font-weight-bold @if($item->direction==='in') text-success @else text-danger @endif">
                    {{number_format($item->amount,2)}}
                </td>
                <td><span class="badge badge-{{$item->direction==='in'?'success':'danger'}}">{{strtoupper($item->direction)}}</span></td>
                <td>
                    @can('sacco.mpesa_recon.create')
                    <button type="button" class="btn btn-xs btn-warning btn-flag" data-item-id="{{$item->id}}">Flag</button>
                    @endcan
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted py-2">No unmatched items.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="px-3 py-2">{{$unmatched->links()}}</div>
    </div>
</div>

{{-- Flag Modal --}}
<div class="modal fade" id="flagModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-warning"><h5 class="modal-title">Flag Exception</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
        <form id="flagForm" method="post">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Reason <span class="text-danger">*</span></label>
                    <input type="text" name="reason" class="form-control" required placeholder="e.g. Test transaction, reversal..."></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-warning">Flag</button></div>
        </form>
    </div></div>
</div>

{{-- Resolve Modal --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
        <div class="modal-header bg-success text-white"><h5 class="modal-title">Resolve Exception</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
        <form id="resolveForm" method="post">@csrf
            <div class="modal-body">
                <div class="form-group"><label>Resolution <span class="text-danger">*</span></label>
                    <input type="text" name="resolution" class="form-control" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button><button type="submit" class="btn btn-success">Resolve</button></div>
        </form>
    </div></div>
</div>

</section>
@endsection
@section('scripts')
<script>
document.querySelectorAll('.btn-flag').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('flagForm').action = '{{url("sacco/mpesa-recon/".$recon->id."/item/")}}/' + this.dataset.itemId + '/flag-exception';
        $('#flagModal').modal('show');
    });
});
document.querySelectorAll('.btn-resolve').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('resolveForm').action = '{{url("sacco/mpesa-recon/".$recon->id."/item/")}}/' + this.dataset.itemId + '/resolve';
        $('#resolveModal').modal('show');
    });
});
</script>
@endsection

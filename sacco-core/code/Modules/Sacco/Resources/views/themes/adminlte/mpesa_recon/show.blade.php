@extends('core::layouts.master')
@section('title') {{$recon->reference}} @endsection
@section('styles')
<style>
.item-row.matched        { background: #f0fdf4; }
.item-row.variance       { background: #fefce8; }
.item-row.unmatched      { background: #fff; }
.item-row.exception      { background: #fef2f2; }
.status-dot { display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:5px }
.status-dot.matched      { background:#16a34a }
.status-dot.unmatched    { background:#f59e0b }
.status-dot.variance     { background:#d97706 }
.status-dot.exception    { background:#dc2626 }
</style>
@endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-7">
            <h1>{{$recon->reference}}
                @php $sc=['draft'=>'secondary','importing'=>'info','pending_match'=>'warning','matched'=>'primary','under_review'=>'warning','approved'=>'success','discrepancy'=>'danger','archived'=>'dark']; @endphp
                <span class="badge badge-{{$sc[$recon->status]??'secondary'}} ml-2">{{ucfirst(str_replace('_',' ',$recon->status))}}</span>
            </h1>
        </div>
        <div class="col-sm-5"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon')}}">Sessions</a></li>
            <li class="breadcrumb-item active">{{$recon->reference}}</li>
        </ol></div>
    </div></div>
</section>
<section class="content">

{{-- Session header --}}
<div class="row mb-3">
    <div class="col-md-2"><div class="info-box mb-0"><span class="info-box-icon bg-success"><i class="fas fa-arrow-down"></i></span><div class="info-box-content"><span class="info-box-text">Stmt In</span><span class="info-box-number">{{number_format($recon->statement_total_in,2)}}</span><span class="progress-description">{{$recon->statement_count_in}} txns</span></div></div></div>
    <div class="col-md-2"><div class="info-box mb-0"><span class="info-box-icon bg-danger"><i class="fas fa-arrow-up"></i></span><div class="info-box-content"><span class="info-box-text">Stmt Out</span><span class="info-box-number">{{number_format($recon->statement_total_out,2)}}</span><span class="progress-description">{{$recon->statement_count_out}} txns</span></div></div></div>
    <div class="col-md-2"><div class="info-box mb-0"><span class="info-box-icon bg-primary"><i class="fas fa-check-double"></i></span><div class="info-box-content"><span class="info-box-text">Matched</span><span class="info-box-number">{{$recon->matched_count}}</span></div></div></div>
    <div class="col-md-2"><div class="info-box mb-0"><span class="info-box-icon bg-warning"><i class="fas fa-question"></i></span><div class="info-box-content"><span class="info-box-text">Unmatched</span><span class="info-box-number">{{$recon->unmatched_statement}}</span></div></div></div>
    <div class="col-md-2"><div class="info-box mb-0"><span class="info-box-icon bg-orange"><i class="fas fa-exchange-alt"></i></span><div class="info-box-content"><span class="info-box-text">Variance Items</span><span class="info-box-number">{{$recon->discrepancy_count}}</span></div></div></div>
    <div class="col-md-2">
        <div class="info-box mb-0 @if(abs($recon->net_variance)>0.01) bg-danger @else bg-success @endif">
            <span class="info-box-icon"><i class="fas fa-balance-scale"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Net Variance</span>
                <span class="info-box-number">{{number_format($recon->net_variance,2)}}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
{{-- Left: actions panel --}}
<div class="col-md-3">
    {{-- Import CSV --}}
    @if(in_array($recon->status,['draft','pending_match']))
    @can('sacco.mpesa_recon.create')
    <div class="card card-primary card-outline">
        <div class="card-header"><h6 class="mb-0"><i class="fas fa-upload mr-1"></i>Import Statement CSV</h6></div>
        <div class="card-body">
            <form method="post" action="{{url('sacco/mpesa-recon/'.$recon->id.'/import')}}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label>Safaricom CSV Statement</label>
                    <input type="file" name="csv_file" class="form-control-file" accept=".csv,.txt" required>
                    <small class="text-muted">Standard Safaricom portal statement CSV format.</small>
                </div>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-upload mr-1"></i>Import
                </button>
            </form>
            @if($recon->statement_file_name)
            <div class="mt-2"><small class="text-muted"><i class="fas fa-file-csv mr-1"></i>Last: {{$recon->statement_file_name}}</small></div>
            @endif
        </div>
    </div>
    @endcan
    @endif

    {{-- Auto-match --}}
    @if(in_array($recon->status,['pending_match','matched']))
    @can('sacco.mpesa_recon.create')
    <div class="card">
        <div class="card-body">
            <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/auto-match')}}"
               class="btn btn-warning btn-block confirm">
                <i class="fas fa-magic mr-1"></i> Run Auto-Match
            </a>
            <small class="text-muted d-block mt-1 text-center">Matches by receipt number and fuzzy criteria</small>
        </div>
    </div>
    @endcan
    @endif

    {{-- Approve --}}
    @if(in_array($recon->status,['matched','under_review']))
    @can('sacco.mpesa_recon.approve')
    <div class="card card-success card-outline">
        <div class="card-body">
            <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/approve')}}"
               class="btn btn-success btn-block confirm">
                <i class="fas fa-check mr-1"></i> Approve Reconciliation
            </a>
            @if($recon->discrepancy_count > 0 || abs($recon->net_variance) > 0.01)
            <div class="alert alert-warning mt-2 py-1 mb-0" style="font-size:.75rem">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Will be marked as <strong>Approved with Discrepancy</strong>.
            </div>
            @endif
        </div>
    </div>
    @endcan
    @endif

    {{-- Info --}}
    <div class="card">
        <div class="card-body">
            <table class="table table-sm table-borderless mb-0">
                <tr><td class="text-muted">Provider</td>    <td><strong>{{strtoupper($recon->provider)}}</strong></td></tr>
                <tr><td class="text-muted">Short Code</td>  <td>{{$recon->short_code}}</td></tr>
                <tr><td class="text-muted">Type</td>        <td>{{strtoupper($recon->account_type)}}</td></tr>
                <tr><td class="text-muted">Period</td>      <td>{{$recon->period_from}} → {{$recon->period_to}}</td></tr>
                @if($recon->notes)<tr><td class="text-muted">Notes</td><td>{{$recon->notes}}</td></tr>@endif
                @if($recon->approved_at)<tr><td class="text-muted">Approved</td><td>{{$recon->approved_at}}</td></tr>@endif
            </table>
        </div>
        <div class="card-footer p-2">
            <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/audit-log')}}" class="btn btn-xs btn-secondary btn-block">
                <i class="fas fa-history mr-1"></i>Audit Log
            </a>
            <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/exceptions')}}" class="btn btn-xs btn-warning btn-block mt-1">
                <i class="fas fa-exclamation-circle mr-1"></i>Exceptions ({{$recon->unmatched_statement}})
            </a>
        </div>
    </div>
</div>

{{-- Right: items table --}}
<div class="col-md-9">
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Statement Items</h5>
        <div class="card-tools d-flex align-items-center gap-2">
            <span>
                <span class="status-dot matched"></span>Matched
                <span class="status-dot variance ml-2"></span>Variance
                <span class="status-dot unmatched ml-2"></span>Unmatched
                <span class="status-dot exception ml-2"></span>Exception
            </span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive" style="max-height:600px;overflow-y:auto">
        <table class="table table-sm table-bordered mb-0">
            <thead style="position:sticky;top:0;background:#fff;z-index:1">
                <tr>
                    <th>Receipt #</th><th>Time</th><th>Details</th><th>Phone</th><th>Acct Ref</th>
                    <th class="text-right">Amount</th><th>Dir</th>
                    <th>Match Status</th><th>Variance</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($recon->items as $item)
            @php
                $rowClass = 'unmatched';
                if(in_array($item->match_status,['matched','manually_matched'])) $rowClass='matched';
                elseif($item->match_status==='matched_with_variance') $rowClass='variance';
                elseif($item->is_exception) $rowClass='exception';
                $badgeMap=['unmatched'=>'warning','matched'=>'success','matched_with_variance'=>'orange',
                           'manually_matched'=>'info','excluded'=>'secondary'];
            @endphp
            <tr class="item-row {{$rowClass}}">
                <td><small class="font-weight-bold">{{$item->receipt_number}}</small></td>
                <td><small>{{$item->completion_time ? \Carbon\Carbon::parse($item->completion_time)->format('d M H:i') : '—'}}</small></td>
                <td><small>{{Str::limit($item->details,30)}}</small></td>
                <td><small>{{$item->paid_in_number}}</small></td>
                <td><small>{{$item->account_reference}}</small></td>
                <td class="text-right font-weight-bold @if($item->direction==='in') text-success @else text-danger @endif">
                    {{number_format($item->amount,2)}}
                </td>
                <td>
                    @if($item->direction==='in')
                        <span class="badge badge-success">IN</span>
                    @else
                        <span class="badge badge-danger">OUT</span>
                    @endif
                </td>
                <td>
                    <span class="badge badge-{{$badgeMap[$item->match_status]??'secondary'}}">
                        {{ucfirst(str_replace('_',' ',$item->match_status))}}
                    </span>
                    @if($item->is_exception) <span class="badge badge-danger ml-1">EXC</span> @endif
                    @if($item->is_duplicate) <span class="badge badge-warning ml-1">DUP</span> @endif
                </td>
                <td class="text-right @if(($item->variance_amount??0)!=0) text-danger @endif">
                    {{$item->variance_amount !== null ? number_format($item->variance_amount,2) : '—'}}
                </td>
                <td>
                    @if($item->match_status === 'unmatched' && !$item->is_exception)
                    @can('sacco.mpesa_recon.create')
                    <button type="button" class="btn btn-xs btn-primary btn-manual-match"
                            data-item-id="{{$item->id}}"
                            data-amount="{{$item->amount}}"
                            data-receipt="{{$item->receipt_number}}"
                            data-direction="{{$item->direction}}">
                        Match
                    </button>
                    <button type="button" class="btn btn-xs btn-warning btn-flag"
                            data-item-id="{{$item->id}}">Flag</button>
                    @endcan
                    @elseif(in_array($item->match_status,['matched','manually_matched','matched_with_variance']))
                    <a href="{{url('sacco/mpesa-recon/'.$recon->id.'/item/'.$item->id.'/unmatch')}}"
                       class="btn btn-xs btn-outline-danger confirm">Unmatch</a>
                    @endif
                    @if($item->is_exception && !$item->match_status==='excluded')
                    @can('sacco.mpesa_recon.create')
                    <button type="button" class="btn btn-xs btn-success btn-resolve"
                            data-item-id="{{$item->id}}">Resolve</button>
                    @endcan
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted py-4">
                No items imported yet.
                @if(in_array($recon->status,['draft','pending_match'])) Upload a CSV statement above. @endif
            </td></tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
</div>
</div>

{{-- Manual Match Modal --}}
<div class="modal fade" id="manualMatchModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Manual Match</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form method="post" action="{{url('sacco/mpesa-recon/'.$recon->id.'/manual-match')}}">
                @csrf
                <input type="hidden" name="item_id" id="mm_item_id">
                <div class="modal-body">
                    <p>Statement item: <strong id="mm_receipt"></strong> — Amount: <strong id="mm_amount"></strong></p>
                    <div class="form-group">
                        <label>Transaction Type</label>
                        <select name="transaction_type" id="mm_type" class="form-control" required>
                            <option value="c2b">C2B (Inbound)</option>
                            <option value="b2c">B2C (Outbound)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>System Transaction ID <span class="text-danger">*</span></label>
                        <input type="number" name="transaction_id" class="form-control" required placeholder="Enter system transaction ID">
                        <small class="text-muted">Check the unmatched transactions below to find the matching system ID.</small>
                    </div>
                    <div class="form-group">
                        <label>Match Notes</label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional reason for manual match">
                    </div>
                    {{-- Unmatched system transactions --}}
                    <div id="unmatchedList" class="mt-3" style="max-height:200px;overflow-y:auto">
                        <h6>Unmatched System Transactions</h6>
                        <table class="table table-sm table-bordered">
                            <thead><tr><th>ID</th><th>Receipt</th><th>Phone</th><th>Amount</th><th>Date</th></tr></thead>
                            <tbody>
                            @foreach($unmatchedC2b->take(20) as $t)
                            <tr style="cursor:pointer" onclick="document.querySelector('[name=transaction_id]').value={{$t->id}};document.getElementById('mm_type').value='c2b'">
                                <td>{{$t->id}}</td><td>{{$t->trans_id}}</td><td>{{$t->msisdn}}</td>
                                <td>{{number_format($t->trans_amount,2)}}</td><td>{{$t->created_at->format('d M H:i')}}</td>
                            </tr>
                            @endforeach
                            @foreach($unmatchedB2c->take(10) as $t)
                            <tr style="cursor:pointer;background:#fff0f0" onclick="document.querySelector('[name=transaction_id]').value={{$t->id}};document.getElementById('mm_type').value='b2c'">
                                <td>{{$t->id}}</td><td>{{$t->mpesa_receipt}}</td><td>{{$t->phone}}</td>
                                <td>{{number_format($t->amount,2)}}</td><td>{{$t->created_at->format('d M H:i')}}</td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Match</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Flag Exception Modal --}}
<div class="modal fade" id="flagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning"><h5 class="modal-title">Flag Exception</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
            <form id="flagForm" method="post" action="">@csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Reason <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" required placeholder="e.g. Test transaction, reversal, duplicate...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Flag Exception</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Resolve Modal --}}
<div class="modal fade" id="resolveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white"><h5 class="modal-title">Resolve Exception</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <form id="resolveForm" method="post" action="">@csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label>Resolution <span class="text-danger">*</span></label>
                        <input type="text" name="resolution" class="form-control" required placeholder="Describe how this exception was resolved...">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Resolve</button>
                </div>
            </form>
        </div>
    </div>
</div>

</section>
@endsection
@section('scripts')
<script>
document.querySelectorAll('.btn-manual-match').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('mm_item_id').value  = this.dataset.itemId;
        document.getElementById('mm_receipt').textContent = this.dataset.receipt;
        document.getElementById('mm_amount').textContent  = this.dataset.amount;
        document.getElementById('mm_type').value = this.dataset.direction === 'out' ? 'b2c' : 'c2b';
        $('#manualMatchModal').modal('show');
    });
});
document.querySelectorAll('.btn-flag').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('flagForm').action =
            '{{url("sacco/mpesa-recon/".$recon->id."/item/")}}/' + this.dataset.itemId + '/flag-exception';
        $('#flagModal').modal('show');
    });
});
document.querySelectorAll('.btn-resolve').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.getElementById('resolveForm').action =
            '{{url("sacco/mpesa-recon/".$recon->id."/item/")}}/' + this.dataset.itemId + '/resolve';
        $('#resolveModal').modal('show');
    });
});
</script>
@endsection

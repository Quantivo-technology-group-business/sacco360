@extends('core::layouts.master')
@section('title') New Reconciliation Session @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1>New Reconciliation Session</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item active">New Session</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
@if($errors->any())
<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>
@endif
<form method="post" action="{{url('sacco/mpesa-recon/store')}}">
@csrf
<div class="row">
<div class="col-md-7">
<div class="card">
    <div class="card-header"><h5 class="mb-0">Session Details</h5></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Provider <span class="text-danger">*</span></label>
                    <select name="provider" class="form-control" required>
                        <option value="mpesa"        @if(old('provider','mpesa')=='mpesa')        selected @endif>M-Pesa (Safaricom)</option>
                        <option value="airtel_money" @if(old('provider')=='airtel_money') selected @endif>Airtel Money</option>
                        <option value="tkash"        @if(old('provider')=='tkash')        selected @endif>T-Kash</option>
                        <option value="mtn"          @if(old('provider')=='mtn')          selected @endif>MTN Mobile Money</option>
                        <option value="other"        @if(old('provider')=='other')        selected @endif>Other</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Short Code / Till Number <span class="text-danger">*</span></label>
                    <input type="text" name="short_code" class="form-control" value="{{old('short_code')}}" required placeholder="e.g. 400200">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Account Type <span class="text-danger">*</span></label>
                    <select name="account_type" class="form-control" required>
                        <option value="c2b"  @if(old('account_type','c2b')=='c2b')  selected @endif>C2B (Customer to Business / Inbound)</option>
                        <option value="b2c"  @if(old('account_type')=='b2c')  selected @endif>B2C (Business to Customer / Outbound)</option>
                        <option value="both" @if(old('account_type')=='both') selected @endif>Both C2B + B2C</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Period From <span class="text-danger">*</span></label>
                    <input type="date" name="period_from" class="form-control" value="{{old('period_from',date('Y-m-01'))}}" required>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Period To <span class="text-danger">*</span></label>
                    <input type="date" name="period_to" class="form-control" value="{{old('period_to',date('Y-m-t'))}}" required>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Optional description for this session...">{{old('notes')}}</textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer">
        <button type="submit" class="btn btn-primary btn-lg float-right">
            <i class="fas fa-plus mr-1"></i> Create Session
        </button>
        <a href="{{url('sacco/mpesa-recon')}}" class="btn btn-secondary">Cancel</a>
    </div>
</div>
</div>
<div class="col-md-5">
<div class="card card-info card-outline">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-info-circle mr-1"></i>How It Works</h5></div>
    <div class="card-body">
        <ol style="font-size:.85rem;color:#475569;padding-left:1.2rem">
            <li class="mb-2"><strong>Create a session</strong> for the period you want to reconcile.</li>
            <li class="mb-2"><strong>Download</strong> the M-Pesa statement CSV from the Safaricom portal for the same period.</li>
            <li class="mb-2"><strong>Import the CSV</strong> into the session — the system parses all transaction rows.</li>
            <li class="mb-2"><strong>Auto-match</strong> — the system tries to match statement rows to system transactions by receipt number and fuzzy criteria.</li>
            <li class="mb-2"><strong>Review & manually match</strong> any remaining unmatched items.</li>
            <li class="mb-2"><strong>Flag exceptions</strong> for items that cannot be matched (e.g. reversals, test transactions).</li>
            <li class="mb-2"><strong>Approve</strong> once satisfied — creates an immutable audit trail.</li>
        </ol>
    </div>
</div>
</div>
</div>
</form>
</section>
@endsection

@extends('core::layouts.master')
@section('title') Float Accounts @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><div class="row mb-2">
        <div class="col-sm-6"><h1><i class="fas fa-wallet mr-2"></i>M-Pesa Float Accounts</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('sacco/mpesa-recon/dashboard')}}">M-Pesa Recon</a></li>
            <li class="breadcrumb-item active">Float Accounts</li>
        </ol></div>
    </div></div>
</section>
<section class="content">
<div class="row">
{{-- Float account cards --}}
<div class="col-md-8">
@forelse($accounts as $acc)
<div class="card @if($acc->isLowFloat()) card-danger card-outline @endif">
    <div class="card-header">
        <h5 class="card-title mb-0">
            {{$acc->account_name ?? $acc->short_code}}
            <span class="badge badge-success ml-2">{{strtoupper($acc->provider)}}</span>
            <span class="badge badge-secondary ml-1">{{$acc->short_code}}</span>
            @if($acc->isLowFloat()) <span class="badge badge-danger ml-1">⚠ LOW FLOAT</span> @endif
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-xs btn-primary" data-toggle="collapse"
                    data-target="#float_entry_{{$acc->id}}">+ Add Entry</button>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 text-center">
                <div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase">Current Balance</div>
                <div style="font-size:2rem;font-weight:900;color:@if($acc->isLowFloat()) #dc2626 @else #16a34a @endif">
                    {{number_format($acc->current_balance,2)}}
                </div>
                @if($acc->minimum_float > 0)
                <div style="font-size:.72rem;color:#64748b">Min: {{number_format($acc->minimum_float,2)}}</div>
                @endif
            </div>
            <div class="col-md-4 text-center">
                <div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase">Last Confirmed</div>
                <div class="font-weight-bold">{{$acc->last_confirmed_balance !== null ? number_format($acc->last_confirmed_balance,2) : '—'}}</div>
                <div style="font-size:.72rem;color:#64748b">{{$acc->last_confirmed_date?->format('d M Y') ?? '—'}}</div>
                @if($acc->last_confirmed_balance !== null)
                <div style="font-size:.75rem" class="@if($acc->variance>0) text-success @elseif($acc->variance<0) text-danger @endif">
                    Variance: {{number_format($acc->variance,2)}}
                </div>
                @endif
            </div>
            <div class="col-md-4 text-center">
                <div style="font-size:.68rem;color:#94a3b8;font-weight:700;text-transform:uppercase">Type</div>
                <div class="font-weight-bold">{{strtoupper(str_replace('_',' ',$acc->type))}}</div>
            </div>
        </div>

        {{-- Add entry form (collapsed) --}}
        <div class="collapse mt-3" id="float_entry_{{$acc->id}}">
            <form method="post" action="{{url('sacco/mpesa-recon/float/'.$acc->id.'/entry')}}">
                @csrf
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Entry Type</label>
                            <select name="entry_type" class="form-control form-control-sm" required>
                                <option value="top_up">Top-Up (Add float)</option>
                                <option value="deduction">Deduction (Remove)</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="confirmed_balance">Confirmed Balance</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="entry_date" class="form-control form-control-sm" value="{{date('Y-m-d')}}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Reference</label>
                            <input type="text" name="reference" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end pb-3">
                        <button type="submit" class="btn btn-sm btn-primary">Save Entry</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Recent entries --}}
        @if($acc->entries->count())
        <h6 class="mt-2 mb-1" style="font-size:.75rem;text-transform:uppercase;color:#94a3b8">Recent Entries</h6>
        <table class="table table-sm table-bordered mb-0">
            <thead><tr><th>Date</th><th>Type</th><th>Amount</th><th>Balance After</th><th>Ref</th></tr></thead>
            <tbody>
            @foreach($acc->entries as $e)
            <tr>
                <td>{{$e->entry_date}}</td>
                <td><span class="badge badge-{{$e->entry_type==='top_up'?'success':($e->entry_type==='deduction'?'danger':'secondary')}}">
                    {{ucfirst(str_replace('_',' ',$e->entry_type))}}
                </span></td>
                <td class="@if($e->entry_type==='deduction') text-danger @else text-success @endif">
                    {{number_format($e->amount,2)}}
                </td>
                <td>{{number_format($e->balance_after,2)}}</td>
                <td><small>{{$e->reference}}</small></td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@empty
<div class="card"><div class="card-body text-center text-muted py-4">No float accounts. Add one using the form.</div></div>
@endforelse
</div>

{{-- Add new float account form --}}
<div class="col-md-4">
@can('sacco.mpesa_recon.float')
<div class="card card-primary card-outline">
    <div class="card-header"><h5 class="mb-0">Add Float Account</h5></div>
    <div class="card-body">
        <form method="post" action="{{url('sacco/mpesa-recon/float/store')}}">
            @csrf
            <div class="form-group"><label>Provider</label>
                <select name="provider" class="form-control" required>
                    <option value="mpesa">M-Pesa (Safaricom)</option>
                    <option value="airtel_money">Airtel Money</option>
                    <option value="tkash">T-Kash</option>
                    <option value="mtn">MTN Mobile Money</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="form-group"><label>Short Code / Till <span class="text-danger">*</span></label>
                <input type="text" name="short_code" class="form-control" required placeholder="e.g. 400200"></div>
            <div class="form-group"><label>Account Name</label>
                <input type="text" name="account_name" class="form-control" placeholder="Descriptive name"></div>
            <div class="form-group"><label>Type</label>
                <select name="type" class="form-control">
                    <option value="paybill">PayBill</option>
                    <option value="till">Till Number</option>
                    <option value="b2c_account">B2C Account</option>
                </select>
            </div>
            <div class="form-group"><label>Minimum Float Alert Level</label>
                <input type="number" step="0.01" min="0" name="minimum_float" class="form-control" placeholder="0 = no alert" value="0"></div>
            <div class="form-group"><label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea></div>
            <button type="submit" class="btn btn-primary btn-block">Add Float Account</button>
        </form>
    </div>
</div>
@endcan
</div>
</div>
</section>
@endsection

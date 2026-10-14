@extends('core::layouts.master')
@section('title') Welfare Claim #{{ $claim->claim_reference }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Claim: {{ $claim->claim_reference }}</h1>
    </div>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Claim Details</h3>
                    <div class="card-tools">
                        @php
                            $badge = ['submitted'=>'secondary','under_review'=>'warning','approved'=>'info',
                                      'rejected'=>'danger','paid'=>'success','cancelled'=>'dark'][$claim->status] ?? 'secondary';
                        @endphp
                        <span class="badge badge-{{ $badge }} badge-lg">{{ ucfirst(str_replace('_',' ',$claim->status)) }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Member</dt>
                        <dd class="col-sm-8">{{ $claim->client->full_name ?? '—' }} ({{ $claim->client->member_number ?? '' }})</dd>
                        <dt class="col-sm-4">Product</dt>
                        <dd class="col-sm-8">{{ $claim->product->name ?? '—' }} — {{ ucfirst($claim->product->type ?? '') }}</dd>
                        <dt class="col-sm-4">Event Date</dt>
                        <dd class="col-sm-8">{{ $claim->event_date }}</dd>
                        <dt class="col-sm-4">Claim Date</dt>
                        <dd class="col-sm-8">{{ $claim->claim_date }}</dd>
                        <dt class="col-sm-4">Amount Claimed</dt>
                        <dd class="col-sm-8">{{ number_format($claim->amount_claimed, 2) }}</dd>
                        <dt class="col-sm-4">Amount Approved</dt>
                        <dd class="col-sm-8">{{ $claim->amount_approved ? number_format($claim->amount_approved,2) : '—' }}</dd>
                        <dt class="col-sm-4">Beneficiary</dt>
                        <dd class="col-sm-8">{{ $claim->beneficiary_name }} ({{ $claim->beneficiary_relationship }}) — {{ $claim->beneficiary_phone }}</dd>
                        <dt class="col-sm-4">Payout Account</dt>
                        <dd class="col-sm-8">{{ $claim->beneficiary_account ?? '—' }}</dd>
                        <dt class="col-sm-4">Remarks</dt>
                        <dd class="col-sm-8">{{ $claim->remarks ?? '—' }}</dd>
                        @if($claim->rejection_reason)
                            <dt class="col-sm-4 text-danger">Rejection Reason</dt>
                            <dd class="col-sm-8 text-danger">{{ $claim->rejection_reason }}</dd>
                        @endif
                        @if($claim->payment_reference)
                            <dt class="col-sm-4">Payment Reference</dt>
                            <dd class="col-sm-8">{{ $claim->payment_reference }} on {{ $claim->payment_date }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            {{-- Approve/Reject --}}
            @if(in_array($claim->status, ['submitted','under_review']))
                @can('sacco.welfare.approve')
                    <div class="card card-success">
                        <div class="card-header"><h3 class="card-title">Approve Claim</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ url('sacco/welfare/claims/'.$claim->id.'/approve') }}">
                                @csrf
                                <div class="form-group">
                                    <label>Approved Amount</label>
                                    <input type="number" name="amount_approved" class="form-control" step="0.01" value="{{ $claim->amount_claimed }}" required>
                                </div>
                                <button class="btn btn-success btn-block">Approve</button>
                            </form>
                        </div>
                    </div>
                    <div class="card card-danger">
                        <div class="card-header"><h3 class="card-title">Reject Claim</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ url('sacco/welfare/claims/'.$claim->id.'/reject') }}">
                                @csrf
                                <div class="form-group">
                                    <label>Rejection Reason</label>
                                    <textarea name="rejection_reason" class="form-control" rows="2" required></textarea>
                                </div>
                                <button class="btn btn-danger btn-block">Reject</button>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif

            {{-- Mark Paid --}}
            @if($claim->status === 'approved')
                @can('sacco.welfare.approve')
                    <div class="card card-primary">
                        <div class="card-header"><h3 class="card-title">Mark as Paid</h3></div>
                        <div class="card-body">
                            <form method="POST" action="{{ url('sacco/welfare/claims/'.$claim->id.'/paid') }}">
                                @csrf
                                <div class="form-group">
                                    <label>Payment Reference</label>
                                    <input type="text" name="payment_reference" class="form-control" required>
                                </div>
                                <button class="btn btn-primary btn-block">Mark Paid</button>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif

            <a href="{{ url('sacco/welfare/claims') }}" class="btn btn-default btn-block">← Back to Claims</a>
        </div>
    </div>
</section>
@endsection

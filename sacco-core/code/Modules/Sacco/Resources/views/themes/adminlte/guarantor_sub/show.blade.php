@extends('core::layouts.master')
@section('title') Guarantor Sub #{{ $sub->id }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Guarantor Substitution #{{ $sub->id }}</h1></div></section>
<section class="content">
    <div class="row">
        <div class="col-md-7">
            <div class="card"><div class="card-header"><h3 class="card-title">Request Details</h3>
                <div class="card-tools"><span class="badge badge-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$sub->status]??'secondary' }} badge-lg">{{ ucfirst($sub->status) }}</span></div>
            </div><div class="card-body">
                <dl class="row">
                    <dt class="col-5">Loan</dt><dd class="col-7">#{{ $sub->loan_id }} — {{ $sub->loan->client->full_name ?? '' }}</dd>
                    <dt class="col-5">Outgoing Guarantor</dt><dd class="col-7">{{ $sub->outgoingGuarantor->full_name ?? '—' }}</dd>
                    <dt class="col-5">Incoming Guarantor</dt><dd class="col-7">{{ $sub->incomingGuarantor->full_name ?? '—' }}</dd>
                    <dt class="col-5">Guaranteed Amount</dt><dd class="col-7">{{ $sub->guaranteed_amount ? number_format($sub->guaranteed_amount,2) : '—' }}</dd>
                    <dt class="col-5">Reason</dt><dd class="col-7">{{ $sub->reason }}</dd>
                    <dt class="col-5">Request Date</dt><dd class="col-7">{{ $sub->request_date }}</dd>
                    @if($sub->rejection_reason)<dt class="col-5 text-danger">Rejection</dt><dd class="col-7 text-danger">{{ $sub->rejection_reason }}</dd>@endif
                </dl>
                {{-- Consent Status --}}
                <div class="row">
                    <div class="col-6"><div class="card {{ $sub->outgoing_guarantor_consented?'bg-success':'bg-warning' }} text-white card-sm">
                        <div class="card-body p-2 text-center">
                            <i class="fas {{ $sub->outgoing_guarantor_consented?'fa-check-circle':'fa-clock' }}"></i>
                            Outgoing Consent: {{ $sub->outgoing_guarantor_consented?'Given':'Pending' }}
                        </div></div></div>
                    <div class="col-6"><div class="card {{ $sub->incoming_guarantor_consented?'bg-success':'bg-warning' }} text-white card-sm">
                        <div class="card-body p-2 text-center">
                            <i class="fas {{ $sub->incoming_guarantor_consented?'fa-check-circle':'fa-clock' }}"></i>
                            Incoming Consent: {{ $sub->incoming_guarantor_consented?'Given':'Pending' }}
                        </div></div></div>
                </div>
            </div></div>
        </div>
        <div class="col-md-5">
            @if($sub->status === 'pending')
                @can('sacco.guarantor_sub.approve')
                {{-- Record Consents --}}
                @if(!$sub->outgoing_guarantor_consented)
                <div class="card card-warning"><div class="card-header"><h3 class="card-title">Record Outgoing Consent</h3></div><div class="card-body">
                    <form method="POST" action="{{ url('sacco/guarantor-sub/'.$sub->id.'/consent') }}">@csrf
                        <input type="hidden" name="consent_type" value="outgoing">
                        <p class="text-muted">Confirm that <strong>{{ $sub->outgoingGuarantor->full_name ?? '' }}</strong> has signed consent to be released.</p>
                        <button class="btn btn-warning btn-block">Record Outgoing Consent</button>
                    </form></div></div>
                @endif
                @if(!$sub->incoming_guarantor_consented)
                <div class="card card-info"><div class="card-header"><h3 class="card-title">Record Incoming Consent</h3></div><div class="card-body">
                    <form method="POST" action="{{ url('sacco/guarantor-sub/'.$sub->id.'/consent') }}">@csrf
                        <input type="hidden" name="consent_type" value="incoming">
                        <p class="text-muted">Confirm that <strong>{{ $sub->incomingGuarantor->full_name ?? '' }}</strong> has signed consent to guarantee.</p>
                        <button class="btn btn-info btn-block">Record Incoming Consent</button>
                    </form></div></div>
                @endif
                @if($sub->isReadyForApproval())
                <div class="card card-success"><div class="card-header"><h3 class="card-title">Approve Substitution</h3></div><div class="card-body">
                    <p class="text-muted">Both parties have consented. You may now approve and apply the substitution.</p>
                    <a href="{{ url('sacco/guarantor-sub/'.$sub->id.'/approve') }}" class="btn btn-success btn-block" onclick="return confirm('Approve and apply this guarantor substitution?')">Approve &amp; Apply</a>
                </div></div>
                <div class="card card-danger"><div class="card-header"><h3 class="card-title">Reject</h3></div><div class="card-body">
                    <form method="POST" action="{{ url('sacco/guarantor-sub/'.$sub->id.'/reject') }}">@csrf
                        <div class="form-group"><label>Rejection Reason</label><textarea name="rejection_reason" class="form-control" rows="2" required></textarea></div>
                        <button class="btn btn-danger btn-block">Reject Request</button>
                    </form></div></div>
                @endif
                @endcan
            @endif
            <a href="{{ url('sacco/guarantor-sub') }}" class="btn btn-default btn-block">← Back</a>
        </div>
    </div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') KYC — {{$client->first_name}} {{$client->last_name}} @endsection
@section('styles')
<style>
/* ── CRB score ring ─────────────────────────────────────────── */
.crb-score-ring {
    display:inline-flex; flex-direction:column; align-items:center;
    justify-content:center; width:90px; height:90px;
    border-radius:50%; border:5px solid;
    font-weight:800; line-height:1;
}
.crb-score-ring.excellent { border-color:#28a745; color:#28a745; }
.crb-score-ring.good      { border-color:#17a2b8; color:#17a2b8; }
.crb-score-ring.fair      { border-color:#ffc107; color:#856404; }
.crb-score-ring.poor      { border-color:#fd7e14; color:#e44d26; }
.crb-score-ring.very-poor,
.crb-score-ring.listed    { border-color:#dc3545; color:#dc3545; }
.crb-score-ring.simulated { border-color:#6c757d; color:#6c757d; }
.crb-score-ring .score-num { font-size:1.4rem; }
.crb-score-ring .score-lbl { font-size:0.55rem; text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }

.crb-band-badge {
    display:inline-block; padding:.25em .65em; border-radius:2rem;
    font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
}

.listed-alert { border-left:4px solid #dc3545; background:#fff5f5; }
.clean-alert  { border-left:4px solid #28a745; background:#f0fff4; }
.sim-alert    { border-left:4px solid #6c757d; background:#f8f9fa; }

.crb-history-row td { font-size:.84rem; vertical-align:middle; }

/* Compact info rows */
.kyc-meta dt { font-weight:600; color:#64748b; font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
.kyc-meta dd { color:#1e293b; margin-bottom:.5rem; }
</style>
@endsection
@section('content')
@php
$statusBadge = ['verified'=>'success','pending_review'=>'warning','incomplete'=>'danger','rejected'=>'secondary','expired'=>'info'];
$badge = $statusBadge[$kyc_status] ?? 'default';

/* CRB helpers */
$crbScore = $crb_latest ? $crb_latest->credit_score : null;
$crbStatus = $crb_latest ? $crb_latest->status : null;
$crbRingClass = match(true) {
    $crbStatus === 'simulated'             => 'simulated',
    $crbStatus === 'listed'                => 'listed',
    is_null($crbScore)                     => 'simulated',
    $crbScore >= 700                       => 'excellent',
    $crbScore >= 600                       => 'good',
    $crbScore >= 500                       => 'fair',
    $crbScore >= 400                       => 'poor',
    default                                => 'very-poor',
};
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-7">
                <h1 style="font-size:1.25rem;font-weight:800;color:var(--sacco-primary,#0c2340)">
                    <i class="fas fa-id-card-alt mr-2"></i>
                    KYC — {{$client->first_name}} {{$client->last_name}}
                    <span class="badge badge-{{$badge}} ml-2" style="font-size:.7rem;">
                        {{ucfirst(str_replace('_',' ',$kyc_status))}}
                    </span>
                </h1>
            </div>
            <div class="col-sm-5 text-sm-right">
                <a href="{{url('client/'.$client->id.'/show')}}" class="btn btn-sm btn-outline-secondary mr-1">
                    <i class="fas fa-user mr-1"></i>Member Profile
                </a>
                <a href="{{url('sacco/kyc')}}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>KYC Dashboard
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="row">

{{-- ── LEFT COLUMN ─────────────────────────────────────────── --}}
<div class="col-lg-4">

    {{-- Completion card --}}
    <div class="card shadow-sm">
        <div class="card-header" style="background:linear-gradient(135deg,var(--sacco-primary,#0c2340),var(--sacco-primary-dark,#081829));color:#fff;border-radius:10px 10px 0 0;">
            <h3 class="card-title mb-0" style="color:#fff;"><i class="fas fa-tasks mr-2"></i>KYC Completion</h3>
        </div>
        <div class="card-body text-center pt-3">
            <div style="position:relative;display:inline-block;margin-bottom:.75rem;">
                <svg width="80" height="80" style="transform:rotate(-90deg)">
                    <circle cx="40" cy="40" r="34" fill="none" stroke="#e9ecef" stroke-width="8"/>
                    <circle cx="40" cy="40" r="34" fill="none"
                        stroke="{{$completion_percent>=100?'#28a745':($completion_percent>=60?'#ffc107':'#dc3545')}}"
                        stroke-width="8"
                        stroke-dasharray="{{round(213.6*$completion_percent/100)}} 213.6"
                        stroke-linecap="round"/>
                </svg>
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.15rem;font-weight:800;color:var(--sacco-primary,#0c2340);">
                    {{$completion_percent}}%
                </div>
            </div>
            <p class="mb-1" style="font-size:.83rem;color:#64748b;">
                <strong>{{$mandatory_met}}</strong> / <strong>{{$mandatory_total}}</strong> mandatory items
            </p>
            <p class="mb-1" style="font-size:.83rem;">
                IPRS:
                @if($iprs_verified)
                    <span class="text-success font-weight-bold"><i class="fas fa-check-circle"></i> Verified</span>
                @else
                    <span class="text-danger"><i class="fas fa-times-circle"></i> Not verified</span>
                @endif
            </p>
            @if($client->kyc_expiry_date)
            <p class="mb-0" style="font-size:.78rem;color:#6c757d;">Expires: {{$client->kyc_expiry_date}}</p>
            @endif
        </div>
        @can('sacco.kyc.manage')
        <div class="card-footer pt-2 pb-2">
            @if($can_approve && $kyc_status !== 'verified')
            <form method="post" action="{{url('sacco/kyc/'.$client->id.'/approve')}}" class="mb-2">@csrf
                <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Approval notes (optional)"></textarea>
                <button type="submit" class="btn btn-success btn-sm btn-block">
                    <i class="fas fa-check mr-1"></i>Approve KYC
                </button>
            </form>
            @endif
            <button type="button" class="btn btn-danger btn-sm btn-block" data-toggle="collapse" data-target="#rejectForm">
                <i class="fas fa-times mr-1"></i>Reject KYC
            </button>
            <div class="collapse mt-2" id="rejectForm">
                <form method="post" action="{{url('sacco/kyc/'.$client->id.'/reject')}}">@csrf
                    <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Rejection reason" required>
                    <button type="submit" class="btn btn-danger btn-sm btn-block">Confirm Reject</button>
                </form>
            </div>
        </div>
        @endcan
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         CRB CARD
    ═══════════════════════════════════════════════════════════ --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex align-items-center justify-content-between"
             style="background:#0c2340;color:#fff;border-radius:10px 10px 0 0;">
            <h3 class="card-title mb-0" style="color:#fff;">
                <i class="fas fa-shield-alt mr-2"></i>CRB Credit Check
            </h3>
            <span class="badge badge-light" style="font-size:.65rem;">{{ $crb_provider }}</span>
        </div>

        <div class="card-body">

            @if(!$crb_configured)
            <div class="alert alert-warning py-2 mb-3" style="font-size:.82rem;">
                <i class="fas fa-info-circle mr-1"></i>
                Live CRB API not configured. Results are <strong>simulated</strong>.
                Set <code>SACCO_CRB_API_URL</code> and <code>SACCO_CRB_API_KEY</code> in <code>.env</code>.
            </div>
            @endif

            @if($crb_latest)
            {{-- Score display --}}
            <div class="d-flex align-items-center mb-3">
                <div class="crb-score-ring {{$crbRingClass}} mr-3">
                    <span class="score-num">
                        @if($crb_latest->credit_score) {{$crb_latest->credit_score}} @else N/A @endif
                    </span>
                    <span class="score-lbl">score</span>
                </div>
                <div class="flex-grow-1">
                    @if($crb_latest->score_band)
                    <div style="font-weight:700;color:var(--sacco-primary,#0c2340);font-size:.9rem;">{{$crb_latest->score_band}}</div>
                    @endif
                    <div>
                        @php
                        $recClass = ['approve'=>'badge-success','review'=>'badge-warning','decline'=>'badge-danger'][$crb_latest->recommendation ?? ''] ?? 'badge-secondary';
                        @endphp
                        <span class="badge {{$recClass}} mr-1">{{ucfirst($crb_latest->recommendation ?? '—')}}</span>
                        @if($crb_latest->status === 'simulated')
                            <span class="badge badge-secondary">Simulated</span>
                        @elseif($crb_latest->is_listed)
                            <span class="badge badge-danger">Listed</span>
                        @else
                            <span class="badge badge-success">Clean</span>
                        @endif
                    </div>
                    <div style="font-size:.75rem;color:#6c757d;margin-top:.25rem;">
                        Checked: {{$crb_latest->created_at->format('d M Y, H:i')}}
                        @if($crb_latest->valid_until)
                            · Valid until: {{$crb_latest->valid_until->format('d M Y')}}
                        @endif
                    </div>
                </div>
            </div>

            {{-- Alert box --}}
            @if($crb_latest->is_listed || $crb_latest->negative_listings > 0)
            <div class="alert listed-alert py-2 mb-3" style="font-size:.82rem;">
                <i class="fas fa-exclamation-triangle text-danger mr-1"></i>
                <strong>{{$crb_latest->negative_listings}} negative listing(s)</strong>,
                {{$crb_latest->npl_count}} NPL(s).
                @if($crb_latest->summary) <br><em>{{$crb_latest->summary}}</em> @endif
            </div>
            @elseif($crb_latest->status === 'simulated')
            <div class="alert sim-alert py-2 mb-3" style="font-size:.78rem;">
                <i class="fas fa-flask mr-1"></i>{{$crb_latest->summary}}
            </div>
            @else
            <div class="alert clean-alert py-2 mb-3" style="font-size:.82rem;">
                <i class="fas fa-check-circle text-success mr-1"></i>
                No negative listings found.
                @if($crb_latest->summary) {{$crb_latest->summary}} @endif
            </div>
            @endif

            {{-- Key stats --}}
            <div class="row text-center mb-2" style="font-size:.8rem;">
                <div class="col-4 border-right">
                    <div style="font-weight:700;font-size:1.1rem;color:#dc3545;">{{$crb_latest->negative_listings}}</div>
                    <div style="color:#6c757d;">Listings</div>
                </div>
                <div class="col-4 border-right">
                    <div style="font-weight:700;font-size:1.1rem;color:#dc3545;">{{$crb_latest->npl_count}}</div>
                    <div style="color:#6c757d;">NPLs</div>
                </div>
                <div class="col-4">
                    <div style="font-weight:700;font-size:1.1rem;color:var(--sacco-primary,#0c2340);">
                        {{$crb_latest->isValid() ? 'Valid' : 'Expired'}}
                    </div>
                    <div style="color:#6c757d;">Status</div>
                </div>
            </div>

            @if($crb_latest->reference_number)
            <p style="font-size:.75rem;color:#6c757d;" class="mb-2">Ref: {{$crb_latest->reference_number}}</p>
            @endif

            <a href="{{url('sacco/kyc/crb/'.$crb_latest->id.'/detail')}}" class="btn btn-outline-secondary btn-sm btn-block mb-2">
                <i class="fas fa-eye mr-1"></i>Full Report
            </a>

            @else
            <div class="text-center text-muted py-3">
                <i class="fas fa-search fa-2x mb-2 d-block opacity-50"></i>
                <p class="mb-0" style="font-size:.85rem;">No CRB check has been run for this member yet.</p>
            </div>
            @endif

            {{-- Run new check --}}
            @can('sacco.kyc.manage')
            <hr class="my-3">
            <button type="button" class="btn btn-primary btn-sm btn-block" data-toggle="collapse" data-target="#crbCheckForm">
                <i class="fas fa-sync-alt mr-1"></i>Run New CRB Check
            </button>
            <div class="collapse mt-2" id="crbCheckForm">
                <form method="post" action="{{url('sacco/kyc/'.$client->id.'/crb')}}">@csrf
                    <div class="form-group mb-2">
                        <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Check Type</label>
                        <select name="check_type" class="form-control form-control-sm">
                            <option value="full">Full Report</option>
                            <option value="score_only">Credit Score Only</option>
                            <option value="listing_only">Listing Status Only</option>
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label style="font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Consent Reference (optional)</label>
                        <input type="text" name="consent_reference" class="form-control form-control-sm" placeholder="e.g. Form signed on 08/06/2024">
                    </div>
                    <div class="custom-control custom-checkbox mb-2">
                        <input type="checkbox" class="custom-control-input" id="crbConsent" name="consent_obtained" value="1" required>
                        <label class="custom-control-label" for="crbConsent" style="font-size:.8rem;">
                            I confirm member consent has been obtained for this CRB inquiry
                        </label>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-paper-plane mr-1"></i>Submit CRB Check
                    </button>
                </form>
            </div>
            @endcan

            {{-- Mini history --}}
            @if($crb_checks->count() > 1)
            <div class="mt-3">
                <p style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#6c757d;margin-bottom:.5rem;">
                    Recent Checks
                </p>
                @foreach($crb_checks->take(3) as $chk)
                <div class="d-flex justify-content-between align-items-center py-1 border-bottom" style="font-size:.78rem;">
                    <span>
                        @php $rc2 = ['approve'=>'badge-success','review'=>'badge-warning','decline'=>'badge-danger'][$chk->recommendation ?? ''] ?? 'badge-secondary'; @endphp
                        <span class="badge {{$rc2}}">{{ucfirst($chk->recommendation ?? '—')}}</span>
                        @if($chk->credit_score) <strong>{{$chk->credit_score}}</strong> @endif
                    </span>
                    <span style="color:#6c757d;">{{$chk->created_at->format('d M Y')}}</span>
                    <a href="{{url('sacco/kyc/crb/'.$chk->id.'/detail')}}" style="font-size:.72rem;">view</a>
                </div>
                @endforeach
                <a href="{{url('sacco/kyc/'.$client->id.'/crb/history')}}" class="btn btn-link btn-sm btn-block p-0 mt-2" style="font-size:.78rem;">
                    View all {{$crb_checks->count()}} checks
                </a>
            </div>
            @endif
        </div>
    </div>{{-- /CRB card --}}

    @can('sacco.kyc.manage')
    {{-- IPRS / NIDA --}}
    <div class="card shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-fingerprint mr-1"></i>IPRS / NIDA Verification</h3></div>
        <div class="card-body">
            <form method="post" action="{{url('sacco/kyc/'.$client->id.'/iprs')}}">@csrf
                <div class="form-group">
                    <label>National ID Number</label>
                    <input type="text" name="id_number" class="form-control form-control-sm"
                           value="{{optional($client->identifications->first())->identification_value}}"
                           placeholder="Leave blank to use ID on file">
                </div>
                <button type="submit" class="btn btn-info btn-sm btn-block">
                    <i class="fas fa-satellite-dish mr-1"></i>Verify via IPRS/NIDA
                </button>
            </form>
            @if($client->iprs_verified_at)
            <p class="mt-2 mb-0 text-success" style="font-size:.8rem;">
                <i class="fas fa-check-circle mr-1"></i>Last verified: {{$client->iprs_verified_at}}
            </p>
            @endif
        </div>
    </div>

    {{-- Upload document --}}
    <div class="card shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-upload mr-1"></i>Upload Document</h3></div>
        <div class="card-body">
            <form method="post" action="{{url('sacco/kyc/'.$client->id.'/document')}}" enctype="multipart/form-data">@csrf
                <div class="form-group">
                    <label>Document Type</label>
                    <select name="document_type" class="form-control form-control-sm" required>
                        @foreach($requirements as $r)
                        <option value="{{$r->code}}">{{$r->name}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Document Number</label>
                    <input type="text" name="document_number" class="form-control form-control-sm">
                </div>
                <div class="form-group">
                    <label>File <small class="text-muted">(JPG/PNG/PDF, max 5 MB)</small></label>
                    <input type="file" name="file" class="form-control-file" accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <button type="submit" class="btn btn-primary btn-sm btn-block">
                    <i class="fas fa-cloud-upload-alt mr-1"></i>Upload
                </button>
            </form>
        </div>
    </div>
    @endcan

</div>{{-- /col-lg-4 --}}

{{-- ── RIGHT COLUMN ────────────────────────────────────────── --}}
<div class="col-lg-8">

    {{-- KYC Checklist --}}
    <div class="card shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-clipboard-list mr-1"></i>KYC Checklist</h3></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" style="font-size:.84rem;">
                <thead class="thead-light">
                    <tr>
                        <th>Requirement</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Document / Detail</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($checklist as $item)
                @php $r=$item['requirement']; $d=$item['document']; @endphp
                <tr>
                    <td>
                        {{$r->name}}
                        @if($r->mandatory)<span class="text-danger ml-1" title="Mandatory">*</span>@endif
                        @if($r->requires_iprs)<span class="badge badge-info ml-1" style="font-size:.62rem;">IPRS</span>@endif
                    </td>
                    <td><span style="font-size:.75rem;color:#6c757d;">{{ucfirst($r->category)}}</span></td>
                    <td>
                        @php
                        $sc = ['approved'=>'success','pending'=>'warning','rejected'=>'danger','on_file'=>'info','missing'=>'secondary'];
                        $sc2 = $sc[$item['status']] ?? 'secondary';
                        @endphp
                        <span class="badge badge-{{$sc2}}">{{ucfirst(str_replace('_',' ',$item['status']))}}</span>
                    </td>
                    <td>
                        @if($d && $d->file_path)
                            <a href="{{asset('storage/'.$d->file_path)}}" target="_blank" class="btn btn-xs btn-outline-secondary">
                                <i class="fas fa-file mr-1"></i>View
                            </a>
                            @if($d->document_number) <span class="text-muted ml-1">{{$d->document_number}}</span>@endif
                        @elseif($r->code==='signature' && $client->signature)
                            <a href="{{asset('storage/uploads/clients/'.$client->signature)}}" target="_blank">On file</a>
                        @elseif($r->code==='national_id' && $client->identifications->isNotEmpty())
                            <span class="text-muted">{{optional($client->identifications->first())->identification_value}}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @can('sacco.kyc.manage')
                        @if($d && $d->status==='pending')
                        <a href="{{url('sacco/kyc/document/'.$d->id.'/approve')}}"
                           class="btn btn-xs btn-success mr-1"
                           onclick="return confirm('Approve this document?')">
                            <i class="fas fa-check"></i>
                        </a>
                        <button class="btn btn-xs btn-danger" data-toggle="modal" data-target="#reject{{$d->id}}">
                            <i class="fas fa-times"></i>
                        </button>
                        {{-- Reject modal --}}
                        <div class="modal fade" id="reject{{$d->id}}">
                            <div class="modal-dialog modal-sm">
                                <div class="modal-content">
                                    <form method="post" action="{{url('sacco/kyc/document/'.$d->id.'/reject')}}">@csrf
                                        <div class="modal-header"><h5 class="modal-title">Reject Document</h5></div>
                                        <div class="modal-body">
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Reason" required>
                                        </div>
                                        <div class="modal-footer py-1">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endif
                        @endcan
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- IPRS History --}}
    <div class="card shadow-sm">
        <div class="card-header"><h3 class="card-title"><i class="fas fa-history mr-1"></i>IPRS Verification History</h3></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0" style="font-size:.82rem;">
                <thead class="thead-light">
                    <tr><th>Date</th><th>ID Number</th><th>Provider</th><th>Match</th><th>Notes</th></tr>
                </thead>
                <tbody>
                @forelse($verifications as $v)
                <tr>
                    <td>{{$v->created_at->format('d M Y H:i')}}</td>
                    <td>{{$v->id_number}}</td>
                    <td>{{strtoupper($v->provider)}}</td>
                    <td>
                        <span class="badge badge-{{in_array($v->match_status,['matched','simulated'])?'success':'danger'}}">
                            {{ucfirst($v->match_status)}}
                        </span>
                    </td>
                    <td class="text-muted">{{$v->notes}}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-3">No IPRS verifications yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- CRB Check History (compact) --}}
    @if($crb_checks->count())
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0"><i class="fas fa-shield-alt mr-1"></i>CRB Check History</h3>
            <a href="{{url('sacco/kyc/'.$client->id.'/crb/history')}}" class="btn btn-xs btn-outline-secondary">
                View All
            </a>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0 crb-history-row">
                <thead class="thead-light">
                    <tr>
                        <th>Date</th><th>Provider</th><th>Score</th><th>Band</th>
                        <th>Listings</th><th>Status</th><th>Rec.</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($crb_checks as $chk)
                @php
                $rc = ['approve'=>'badge-success','review'=>'badge-warning','decline'=>'badge-danger'][$chk->recommendation ?? ''] ?? 'badge-secondary';
                $sc = ['clean'=>'badge-success','listed'=>'badge-danger','simulated'=>'badge-secondary','error'=>'badge-dark','pending'=>'badge-light'][$chk->status] ?? 'badge-secondary';
                @endphp
                <tr>
                    <td>{{$chk->created_at->format('d M Y')}}</td>
                    <td>{{ucfirst($chk->provider)}}</td>
                    <td><strong>{{$chk->credit_score ?? '—'}}</strong></td>
                    <td>{{$chk->score_band ? explode(' —', $chk->score_band)[0] : '—'}}</td>
                    <td>
                        @if($chk->negative_listings > 0)
                            <span class="text-danger font-weight-bold">{{$chk->negative_listings}}</span>
                        @else
                            <span class="text-success">0</span>
                        @endif
                    </td>
                    <td><span class="badge {{$sc}}">{{ucfirst($chk->status)}}</span></td>
                    <td><span class="badge {{$rc}}">{{ucfirst($chk->recommendation ?? '—')}}</span></td>
                    <td>
                        <a href="{{url('sacco/kyc/crb/'.$chk->id.'/detail')}}"
                           class="btn btn-xs btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>{{-- /col-lg-8 --}}
</div>{{-- /row --}}
</section>
@endsection

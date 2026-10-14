@extends('core::layouts.master')
@section('title') CRB Report #{{ $check->id }} @endsection
@section('styles')
<style>
.crb-score-large {
    display:inline-flex; flex-direction:column; align-items:center; justify-content:center;
    width:110px; height:110px; border-radius:50%; border:6px solid;
    font-weight:900; line-height:1;
}
.score-excellent { border-color:#28a745; color:#28a745; background:#f0fff4; }
.score-good      { border-color:#17a2b8; color:#0d6efd; background:#e8f4fd; }
.score-fair      { border-color:#ffc107; color:#856404; background:#fffbea; }
.score-poor      { border-color:#fd7e14; color:#c44b04; background:#fff3e0; }
.score-very-poor,
.score-listed    { border-color:#dc3545; color:#dc3545; background:#fff5f5; }
.score-simulated { border-color:#adb5bd; color:#6c757d; background:#f8f9fa; }
.crb-section-title { font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.08em; color:#64748b; margin-bottom:.75rem;
    padding-bottom:.35rem; border-bottom:1px solid #e9ecef; }
.crb-dl dt { font-weight:600; color:#64748b; font-size:.8rem; }
.crb-dl dd { color:#1e293b; margin-bottom:.6rem; }
</style>
@endsection
@section('content')
@php
$scoreClass = match(true) {
    $check->status === 'simulated'        => 'score-simulated',
    $check->is_listed                     => 'score-listed',
    is_null($check->credit_score)         => 'score-simulated',
    $check->credit_score >= 700           => 'score-excellent',
    $check->credit_score >= 600           => 'score-good',
    $check->credit_score >= 500           => 'score-fair',
    $check->credit_score >= 400           => 'score-poor',
    default                               => 'score-very-poor',
};
$rc = ['approve'=>'badge-success','review'=>'badge-warning','decline'=>'badge-danger'][$check->recommendation ?? ''] ?? 'badge-secondary';
$sc = ['clean'=>'badge-success','listed'=>'badge-danger','simulated'=>'badge-secondary','error'=>'badge-dark','pending'=>'badge-light'][$check->status] ?? 'badge-secondary';
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-7">
                <h1 style="font-size:1.2rem;font-weight:800;color:var(--sacco-primary,#0c2340);">
                    <i class="fas fa-shield-alt mr-2"></i>CRB Report #{{ $check->id }}
                </h1>
            </div>
            <div class="col-sm-5 text-sm-right">
                <a href="{{ url('sacco/kyc/'.$client->id.'/crb/history') }}" class="btn btn-sm btn-outline-secondary mr-1">
                    <i class="fas fa-list mr-1"></i>All Checks
                </a>
                <a href="{{ url('sacco/kyc/'.$client->id.'/show') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back to KYC
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
<div class="container-fluid">
<div class="row">

    {{-- Score card --}}
    <div class="col-lg-4 mb-3">
        <div class="card shadow-sm h-100">
            <div class="card-body text-center pt-4">

                {{-- Ring --}}
                <div class="crb-score-large {{ $scoreClass }} mx-auto mb-3">
                    <span style="font-size:1.9rem;">{{ $check->credit_score ?? 'N/A' }}</span>
                    <span style="font-size:.6rem;text-transform:uppercase;letter-spacing:.05em;margin-top:3px;">score</span>
                </div>

                @if($check->score_band)
                <div style="font-size:1rem;font-weight:700;color:var(--sacco-primary,#0c2340);">{{ $check->score_band }}</div>
                @endif

                <div class="mt-2">
                    <span class="badge {{ $sc }} px-3 py-2 mr-1" style="font-size:.8rem;">
                        {{ ucfirst($check->status) }}
                    </span>
                    <span class="badge {{ $rc }} px-3 py-2" style="font-size:.8rem;">
                        {{ ucfirst($check->recommendation ?? 'N/A') }}
                    </span>
                </div>

                @if($check->status === 'simulated')
                <div class="alert alert-secondary mt-3 py-2" style="font-size:.78rem;">
                    <i class="fas fa-flask mr-1"></i>Simulated result — API not configured
                </div>
                @endif

                <hr class="my-3">

                {{-- Listing stats --}}
                <div class="row text-center">
                    <div class="col-4">
                        <div style="font-size:1.5rem;font-weight:800;color:{{ $check->negative_listings>0?'#dc3545':'#28a745' }};">
                            {{ $check->negative_listings }}
                        </div>
                        <div style="font-size:.7rem;color:#6c757d;">Listings</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:1.5rem;font-weight:800;color:{{ $check->npl_count>0?'#dc3545':'#28a745' }};">
                            {{ $check->npl_count }}
                        </div>
                        <div style="font-size:.7rem;color:#6c757d;">NPLs</div>
                    </div>
                    <div class="col-4">
                        <div style="font-size:1.5rem;font-weight:800;color:{{ $check->is_listed?'#dc3545':'#28a745' }};">
                            {{ $check->is_listed ? 'Yes' : 'No' }}
                        </div>
                        <div style="font-size:.7rem;color:#6c757d;">Listed</div>
                    </div>
                </div>

                @if($check->summary)
                <div class="alert {{ $check->is_listed?'alert-danger':'alert-success' }} mt-3 py-2 text-left" style="font-size:.82rem;">
                    {{ $check->summary }}
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Details --}}
    <div class="col-lg-8 mb-3">
        <div class="card shadow-sm">
            <div class="card-body">

                {{-- Member info --}}
                <p class="crb-section-title"><i class="fas fa-user mr-1"></i>Member</p>
                <dl class="row crb-dl">
                    <dt class="col-sm-4">Name</dt>
                    <dd class="col-sm-8">
                        <a href="{{ url('client/'.$client->id.'/show') }}" class="font-weight-bold">
                            {{ $client->first_name }} {{ $client->last_name }}
                        </a>
                    </dd>
                    <dt class="col-sm-4">Member Number</dt>
                    <dd class="col-sm-8">{{ $client->member_number ?? '—' }}</dd>
                    <dt class="col-sm-4">ID Number Checked</dt>
                    <dd class="col-sm-8"><code>{{ $check->id_number ?? '—' }}</code></dd>
                    <dt class="col-sm-4">KRA PIN</dt>
                    <dd class="col-sm-8">{{ $client->kra_pin ?? '—' }}</dd>
                </dl>

                <p class="crb-section-title mt-3"><i class="fas fa-cog mr-1"></i>Check Details</p>
                <dl class="row crb-dl">
                    <dt class="col-sm-4">Provider</dt>
                    <dd class="col-sm-8">{{ ucfirst($check->provider) }}</dd>
                    <dt class="col-sm-4">Check Type</dt>
                    <dd class="col-sm-8">{{ ucfirst(str_replace('_',' ',$check->check_type)) }}</dd>
                    <dt class="col-sm-4">Run By</dt>
                    <dd class="col-sm-8">{{ $check->checkedBy ? $check->checkedBy->first_name.' '.$check->checkedBy->last_name : 'System' }}</dd>
                    <dt class="col-sm-4">Date / Time</dt>
                    <dd class="col-sm-8">{{ $check->created_at->format('d M Y, H:i:s') }}</dd>
                    <dt class="col-sm-4">Valid Until</dt>
                    <dd class="col-sm-8">
                        @if($check->valid_until)
                            <span class="{{ $check->isValid() ? 'text-success' : 'text-danger' }}">
                                {{ $check->valid_until->format('d M Y') }}
                                {{ $check->isValid() ? '(valid)' : '(expired)' }}
                            </span>
                        @else —
                        @endif
                    </dd>
                    @if($check->reference_number)
                    <dt class="col-sm-4">CRB Reference</dt>
                    <dd class="col-sm-8"><code>{{ $check->reference_number }}</code></dd>
                    @endif
                </dl>

                <p class="crb-section-title mt-3"><i class="fas fa-user-check mr-1"></i>Consent</p>
                <dl class="row crb-dl">
                    <dt class="col-sm-4">Consent Obtained</dt>
                    <dd class="col-sm-8">
                        @if($check->consent_obtained)
                            <span class="text-success"><i class="fas fa-check-circle mr-1"></i>Yes</span>
                        @else
                            <span class="text-danger"><i class="fas fa-times-circle mr-1"></i>No</span>
                        @endif
                    </dd>
                    @if($check->consent_date)
                    <dt class="col-sm-4">Consent Date</dt>
                    <dd class="col-sm-8">{{ $check->consent_date->format('d M Y, H:i') }}</dd>
                    @endif
                    @if($check->consent_reference)
                    <dt class="col-sm-4">Consent Ref</dt>
                    <dd class="col-sm-8">{{ $check->consent_reference }}</dd>
                    @endif
                </dl>

                @if($check->error_message)
                <div class="alert alert-danger py-2 mt-2" style="font-size:.82rem;">
                    <strong>Error:</strong> {{ $check->error_message }}
                </div>
                @endif

            </div>
        </div>

        {{-- Raw response (collapsed) --}}
        @if($check->raw_response)
        <div class="card shadow-sm mt-0">
            <div class="card-header">
                <h3 class="card-title mb-0" style="font-size:.8rem;">
                    <i class="fas fa-code mr-1"></i>Raw API Response
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
            <div class="card-body" style="display:none;">
                <pre style="font-size:.75rem;max-height:300px;overflow-y:auto;background:#f8f9fa;padding:.75rem;border-radius:6px;">{{ json_encode(json_decode($check->raw_response), JSON_PRETTY_PRINT) }}</pre>
            </div>
        </div>
        @endif
    </div>

</div>
</div>
</section>
@endsection

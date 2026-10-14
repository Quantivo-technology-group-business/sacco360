@extends('core::layouts.master')
@section('title') CRB History — {{ $client->first_name }} {{ $client->last_name }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-sm-7">
                <h1 style="font-size:1.2rem;font-weight:800;color:var(--sacco-primary,#0c2340);">
                    <i class="fas fa-shield-alt mr-2"></i>CRB History — {{ $client->first_name }} {{ $client->last_name }}
                </h1>
            </div>
            <div class="col-sm-5 text-sm-right">
                <a href="{{ url('sacco/kyc/'.$client->id.'/show') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Back to KYC
                </a>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="container-fluid">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="card-title">All CRB Checks</h3>
                <div class="card-tools">
                    <span class="badge badge-secondary">{{ $checks->count() }} total</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" style="font-size:.84rem;">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Provider</th>
                            <th>Type</th>
                            <th>Score</th>
                            <th>Band</th>
                            <th>Listings</th>
                            <th>NPLs</th>
                            <th>Status</th>
                            <th>Rec.</th>
                            <th>Valid Until</th>
                            <th>By</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($checks as $chk)
                    @php
                    $rc = ['approve'=>'badge-success','review'=>'badge-warning','decline'=>'badge-danger'][$chk->recommendation ?? ''] ?? 'badge-secondary';
                    $sc = ['clean'=>'badge-success','listed'=>'badge-danger','simulated'=>'badge-secondary','error'=>'badge-dark','pending'=>'badge-light'][$chk->status] ?? 'badge-secondary';
                    @endphp
                    <tr>
                        <td>{{ $chk->id }}</td>
                        <td>{{ $chk->created_at->format('d M Y H:i') }}</td>
                        <td>{{ ucfirst($chk->provider) }}</td>
                        <td>{{ ucfirst(str_replace('_',' ',$chk->check_type)) }}</td>
                        <td><strong>{{ $chk->credit_score ?? '—' }}</strong></td>
                        <td>{{ $chk->score_band ? explode(' —',$chk->score_band)[0] : '—' }}</td>
                        <td>
                            @if($chk->negative_listings > 0)
                                <span class="text-danger font-weight-bold">{{ $chk->negative_listings }}</span>
                            @else
                                <span class="text-success">0</span>
                            @endif
                        </td>
                        <td>{{ $chk->npl_count }}</td>
                        <td><span class="badge {{ $sc }}">{{ ucfirst($chk->status) }}</span></td>
                        <td><span class="badge {{ $rc }}">{{ ucfirst($chk->recommendation ?? '—') }}</span></td>
                        <td>
                            @if($chk->valid_until)
                                <span class="{{ $chk->isValid() ? 'text-success' : 'text-muted' }}">
                                    {{ $chk->valid_until->format('d M Y') }}
                                </span>
                            @else —
                            @endif
                        </td>
                        <td>{{ $chk->checkedBy ? $chk->checkedBy->first_name : 'System' }}</td>
                        <td>
                            <a href="{{ url('sacco/kyc/crb/'.$chk->id.'/detail') }}"
                               class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="13" class="text-center text-muted py-4">No CRB checks found.</td>
                    </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

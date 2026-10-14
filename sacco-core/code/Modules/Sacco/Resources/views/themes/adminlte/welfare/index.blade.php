@extends('core::layouts.master')
@section('title') Member Welfare Fund @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Member Welfare / Benevolent Fund</h1>
    </div>
</section>
<section class="content">
    {{-- Summary Cards --}}
    <div class="row mb-3">
        @foreach($products as $p)
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header"><h3 class="card-title">{{ $p->name }}</h3></div>
                <div class="card-body">
                    <p class="mb-1"><strong>Type:</strong> {{ ucfirst($p->type) }}</p>
                    <p class="mb-1"><strong>Monthly Contribution:</strong> {{ number_format($p->member_contribution, 2) }}</p>
                    <p class="mb-1"><strong>Max Claim:</strong> {{ $p->max_claim_amount ? number_format($p->max_claim_amount, 2) : 'Unlimited' }}</p>
                    <p class="mb-0"><strong>Fund Balance:</strong>
                        <span class="{{ $p->fund_balance >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ number_format($p->fund_balance, 2) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Stats row --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-clock"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Pending Claims</span>
                    <span class="info-box-number">{{ $pendingClaims }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-check-circle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Approved (Awaiting Payment)</span>
                    <span class="info-box-number">{{ $approvedClaims }}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Paid This Year</span>
                    <span class="info-box-number">{{ number_format($paidThisYear, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="mb-3">
        @can('sacco.welfare.claims')
            <a href="{{ url('sacco/welfare/claims/create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Submit Claim
            </a>
            <a href="{{ url('sacco/welfare/claims') }}" class="btn btn-secondary">
                <i class="fas fa-list"></i> All Claims
            </a>
        @endcan
        @can('sacco.welfare.manage')
            <a href="{{ url('sacco/welfare/products') }}" class="btn btn-info">
                <i class="fas fa-cog"></i> Welfare Products
            </a>
        @endcan
        <a href="{{ url('sacco/welfare/contributions') }}" class="btn btn-default">
            <i class="fas fa-table"></i> Contributions
        </a>
    </div>
</section>
@endsection

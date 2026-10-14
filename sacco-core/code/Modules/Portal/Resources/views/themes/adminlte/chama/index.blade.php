@extends('core::layouts.master')
@section('title') My Groups & Chamas @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-users-cog mr-2"></i>My Groups &amp; Chamas</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($memberships->isEmpty())
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <p class="mb-0">You are not a member of any group yet. Contact your SACCO to be added.</p>
                </div>
            </div>
        @else
            <div class="row">
                @foreach($memberships as $m)
                @php $g = $m->group; @endphp
                <div class="col-md-4 col-sm-6">
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-users mr-1"></i>
                                <strong>{{ $g->name }}</strong>
                            </h3>
                            <div class="card-tools">
                                <span class="badge badge-{{ $g->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ ucfirst($g->status) }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0 small">
                                <dt class="col-5">Type</dt>
                                <dd class="col-7">{{ ucfirst(str_replace('_', ' ', $g->type)) }}</dd>

                                <dt class="col-5">Your Role</dt>
                                <dd class="col-7">
                                    <span class="badge badge-info">{{ ucfirst($m->role) }}</span>
                                </dd>

                                <dt class="col-5">Monthly</dt>
                                <dd class="col-7">KES {{ number_format($g->monthly_contribution, 2) }}</dd>

                                <dt class="col-5">Branch</dt>
                                <dd class="col-7">{{ $g->branch->name ?? '—' }}</dd>

                                <dt class="col-5">Joined</dt>
                                <dd class="col-7">{{ $m->joined_date ? $m->joined_date->format('d M Y') : '—' }}</dd>
                            </dl>
                        </div>
                        <div class="card-footer">
                            <a href="{{ url('portal/sacco/chama/' . $g->id . '/show') }}" class="btn btn-sm btn-primary btn-block">
                                <i class="fas fa-eye mr-1"></i> View Group
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif

        <div class="mt-3">
            <a href="{{ url('portal/sacco/chama/meetings') }}" class="btn btn-outline-info">
                <i class="fas fa-calendar-check mr-1"></i> View All Meetings
            </a>
        </div>

    </div>
</section>

@endsection

@extends('core::layouts.master')
@section('title') {{ $group->name }} @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1>
            <i class="fas fa-users-cog mr-2"></i>{{ $group->name }}
            <span class="badge badge-{{ $group->status === 'active' ? 'success' : 'warning' }} ml-2">
                {{ ucfirst($group->status) }}
            </span>
        </h1>
        <small class="text-muted">Your role: <strong>{{ ucfirst($membership->role) }}</strong></small>
        <div class="mt-2">
            <a href="{{ url('portal/sacco/chama/' . $group->id . '/contribute') }}" class="btn btn-success mr-2">
                <i class="fas fa-hand-holding-usd mr-1"></i> Make Contribution
            </a>
            @if($group->type === 'merry_go_round')
            <a href="{{ url('portal/sacco/chama/my-rotation') }}" class="btn btn-outline-info">
                <i class="fas fa-sync-alt mr-1"></i> My Rotation
            </a>
            @endif
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        <div class="row">

            {{-- Group Info & My Contributions --}}
            <div class="col-md-4">

                <div class="card card-outline card-primary">
                    <div class="card-header"><h3 class="card-title">Group Info</h3></div>
                    <div class="card-body">
                        <dl class="row mb-0 small">
                            <dt class="col-5">Type</dt>
                            <dd class="col-7">{{ ucfirst(str_replace('_', ' ', $group->type)) }}</dd>

                            <dt class="col-5">Code</dt>
                            <dd class="col-7">{{ $group->code ?? '—' }}</dd>

                            <dt class="col-5">Branch</dt>
                            <dd class="col-7">{{ $group->branch->name ?? '—' }}</dd>

                            <dt class="col-5">Contribution</dt>
                            <dd class="col-7">KES {{ number_format($group->monthly_contribution, 2) }}/mo</dd>

                            <dt class="col-5">Rotation</dt>
                            <dd class="col-7">{{ ucfirst($group->rotation_model ?? 'fixed') }}</dd>

                            <dt class="col-5">Chairperson</dt>
                            <dd class="col-7">{{ $group->chairperson->full_name ?? '—' }}</dd>

                            <dt class="col-5">Secretary</dt>
                            <dd class="col-7">{{ $group->secretary->full_name ?? '—' }}</dd>

                            <dt class="col-5">Treasurer</dt>
                            <dd class="col-7">{{ $group->treasurer->full_name ?? '—' }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card card-outline card-success">
                    <div class="card-header"><h3 class="card-title">My Contributions</h3></div>
                    <div class="card-body p-0">
                        @if($contributions->isEmpty())
                            <p class="text-muted text-center py-3">No contributions recorded yet.</p>
                        @else
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($contributions as $c)
                                    <tr>
                                        <td>{{ date('M Y', strtotime($c->period . '-01')) }}</td>
                                        <td>KES {{ number_format($c->amount, 2) }}</td>
                                        <td>
                                            @php
                                                $methodIcons = [
                                                    'mpesa'   => '<i class="fas fa-mobile-alt text-success"></i> M-Pesa',
                                                    'savings' => '<i class="fas fa-wallet text-primary"></i> FOSA',
                                                    'cash'    => '<i class="fas fa-money-bill text-warning"></i> Cash',
                                                    'bank'    => '<i class="fas fa-university text-info"></i> Bank',
                                                    'other'   => '<i class="fas fa-circle text-muted"></i> Other',
                                                ];
                                            @endphp
                                            {!! $methodIcons[$c->payment_method ?? 'cash'] ?? ucfirst($c->payment_method ?? 'cash') !!}
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $c->status === 'posted' ? 'success' : ($c->status === 'pending' ? 'warning' : 'secondary') }}">
                                                {{ ucfirst($c->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td>Total</td>
                                        <td>KES {{ number_format($contributions->sum('amount'), 2) }}</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @endif
                    </div>
                </div>

            </div>

            {{-- Members & MGR Schedule --}}
            <div class="col-md-8">

                {{-- Members List --}}
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            Members
                            <span class="badge badge-primary ml-1">
                                {{ $group->members->where('status', 'active')->count() }}
                            </span>
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($group->members->where('status', 'active') as $m)
                                <tr @if($m->client_id === $client->id) style="background:#f0fff0" @endif>
                                    <td>
                                        {{ $m->client->full_name ?? '—' }}
                                        @if($m->client_id === $client->id)
                                            <span class="badge badge-secondary ml-1">You</span>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-info">{{ ucfirst($m->role) }}</span></td>
                                    <td>{{ $m->joined_date ? \Carbon\Carbon::parse($m->joined_date)->format('d M Y') : '—' }}</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Merry-Go-Round Schedule --}}
                @if($group->merryGoRoundCycles->count())
                @php
                    $nextCycle = $group->merryGoRoundCycles
                        ->whereIn('status', ['upcoming', 'active'])
                        ->sortBy('cycle_number')
                        ->first();
                    $myNextCycle = $group->merryGoRoundCycles
                        ->whereIn('status', ['upcoming', 'active'])
                        ->where('beneficiary_client_id', $client->id)
                        ->sortBy('cycle_number')
                        ->first();
                @endphp
                <div class="card card-outline card-info">
                    <div class="card-header"><h3 class="card-title">Merry-Go-Round Schedule</h3></div>
                    <div class="card-body">

                        @if($nextCycle)
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle mr-1"></i>
                            <strong>Next recipient:</strong>
                            {{ $nextCycle->beneficiary->full_name ?? 'To be assigned' }}
                            — Cycle {{ $nextCycle->cycle_number }}
                            on {{ \Carbon\Carbon::parse($nextCycle->cycle_date)->format('d M Y') }}
                        </div>
                        @endif

                        @if($myNextCycle)
                        <div class="alert alert-success">
                            <i class="fas fa-star mr-1"></i>
                            <strong>Your turn:</strong>
                            Cycle {{ $myNextCycle->cycle_number }}
                            on {{ \Carbon\Carbon::parse($myNextCycle->cycle_date)->format('d M Y') }}
                            — Pot: KES {{ number_format($myNextCycle->pot_amount, 2) }}
                        </div>
                        @endif

                        <table class="table table-sm table-bordered mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Recipient</th>
                                    <th>Pot (KES)</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($group->merryGoRoundCycles->sortBy('cycle_number') as $cycle)
                                <tr @if($cycle->beneficiary_client_id === $client->id) class="table-success" @endif>
                                    <td>{{ $cycle->cycle_number }}</td>
                                    <td>
                                        {{ $cycle->beneficiary->full_name ?? '—' }}
                                        @if($cycle->beneficiary_client_id === $client->id)
                                            <span class="badge badge-success ml-1">You</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format($cycle->pot_amount, 2) }}</td>
                                    <td>{{ $cycle->cycle_date ? \Carbon\Carbon::parse($cycle->cycle_date)->format('d M Y') : '—' }}</td>
                                    <td>
                                        @php
                                            $statusColors = [
                                                'upcoming'  => 'secondary',
                                                'active'    => 'primary',
                                                'completed' => 'success',
                                                'paid'      => 'success',
                                                'skipped'   => 'warning',
                                            ];
                                        @endphp
                                        <span class="badge badge-{{ $statusColors[$cycle->status] ?? 'secondary' }}">
                                            {{ ucfirst($cycle->status) }}
                                        </span>
                                        @if($cycle->status === 'paid' && $cycle->disbursed_at)
                                            <br><small class="text-muted">
                                                Paid {{ \Carbon\Carbon::parse($cycle->disbursed_at)->format('d M Y') }}
                                            </small>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

            </div>
        </div>

        <a href="{{ url('portal/sacco/chama') }}" class="btn btn-default">
            <i class="fas fa-arrow-left mr-1"></i> Back to My Groups
        </a>

    </div>
</section>

@endsection

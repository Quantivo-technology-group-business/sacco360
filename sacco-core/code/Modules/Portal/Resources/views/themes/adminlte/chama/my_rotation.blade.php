@extends('core::layouts.master')
@section('title') My Rotation Schedule @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1><i class="fas fa-sync-alt mr-2"></i>My Rotation Schedule</h1>
        <small class="text-muted">Track when it's your turn to receive money across all your merry-go-round groups.</small>
    </div>
</section>

<section class="content">
    <div class="container-fluid">

        @if($rotations->isEmpty())
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="fas fa-sync-alt fa-3x mb-3 text-muted"></i>
                <p class="mb-0">You are not in any merry-go-round group. Contact your SACCO admin to be added.</p>
            </div>
        </div>
        @else

        @foreach($rotations as $r)
        @php
            $group   = $r['group'];
            $myNext  = $r['my_upcoming'];
            $myPast  = $r['my_past'];
            $next    = $r['next_cycle'];
            $days    = $r['days_until'];
        @endphp

        <div class="card card-outline {{ $myNext ? 'card-success' : 'card-secondary' }} mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-users mr-1"></i>
                    {{ $group->name }}
                    <span class="badge badge-{{ $group->status === 'active' ? 'success' : 'secondary' }} ml-2">
                        {{ ucfirst($group->status) }}
                    </span>
                </h3>
                <div class="card-tools">
                    <a href="{{ url('portal/sacco/chama/' . $group->id . '/show') }}"
                       class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye mr-1"></i> View Group
                    </a>
                </div>
            </div>

            <div class="card-body">

                {{-- ── My Turn Banner ─────────────────────────────────────── --}}
                @if($myNext)
                <div class="row mb-4">
                    <div class="col-md-8 offset-md-2">
                        <div class="info-box bg-gradient-success shadow">
                            <span class="info-box-icon">
                                <i class="fas fa-star"></i>
                            </span>
                            <div class="info-box-content">
                                <span class="info-box-text font-weight-bold" style="font-size:16px">
                                    YOUR TURN — Cycle #{{ $myNext->cycle_number }}
                                </span>
                                <span class="info-box-number" style="font-size:22px">
                                    KES {{ number_format($myNext->pot_amount, 2) }}
                                </span>
                                <div class="progress">
                                    <div class="progress-bar bg-white" style="width:100%"></div>
                                </div>
                                <span class="progress-description">
                                    @if($days !== null)
                                        @if($days > 0)
                                            <i class="fas fa-calendar-alt mr-1"></i>
                                            In <strong>{{ $days }} day{{ $days === 1 ? '' : 's' }}</strong>
                                            — {{ \Carbon\Carbon::parse($myNext->cycle_date)->format('d F Y') }}
                                        @elseif($days === 0)
                                            <i class="fas fa-clock mr-1"></i> <strong>Today!</strong>
                                            — {{ \Carbon\Carbon::parse($myNext->cycle_date)->format('d F Y') }}
                                        @else
                                            <i class="fas fa-exclamation-triangle mr-1"></i>
                                            Overdue by {{ abs($days) }} day{{ abs($days) === 1 ? '' : 's' }}
                                            — {{ \Carbon\Carbon::parse($myNext->cycle_date)->format('d F Y') }}
                                        @endif
                                    @else
                                        Date TBD
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle mr-1"></i>
                    You have no upcoming rotation turn in this group.
                    @if($next)
                        Next pot goes to <strong>{{ $next->beneficiary->full_name ?? 'another member' }}</strong>
                        (Cycle #{{ $next->cycle_number }})
                        on {{ \Carbon\Carbon::parse($next->cycle_date)->format('d M Y') }}.
                    @endif
                </div>
                @endif

                {{-- ── Full Cycle Table ────────────────────────────────────── --}}
                <h5 class="mt-3 mb-2">
                    <i class="fas fa-list-ol mr-1"></i> Full Rotation Schedule
                    <small class="text-muted">({{ $r['total_members'] }} members, KES {{ number_format($group->monthly_contribution, 2) }}/mo each)</small>
                </h5>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Recipient</th>
                                <th>Pot (KES)</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Net Payout</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($r['all_cycles']->sortBy('cycle_number') as $cycle)
                        @php
                            $isMe = $cycle->beneficiary_client_id === $client->id;
                            $rowClass = '';
                            if ($isMe && in_array($cycle->status, ['upcoming','active'])) {
                                $rowClass = 'table-success font-weight-bold';
                            } elseif ($isMe) {
                                $rowClass = 'table-info';
                            }
                            $statusColors = [
                                'upcoming'  => 'secondary',
                                'active'    => 'primary',
                                'completed' => 'success',
                                'paid'      => 'success',
                                'skipped'   => 'warning',
                            ];
                        @endphp
                        <tr class="{{ $rowClass }}">
                            <td>{{ $cycle->cycle_number }}</td>
                            <td>
                                {{ $cycle->beneficiary->full_name ?? '—' }}
                                @if($isMe)
                                    <span class="badge badge-{{ in_array($cycle->status, ['upcoming','active']) ? 'success' : 'info' }} ml-1">
                                        <i class="fas fa-star mr-1"></i>You
                                    </span>
                                @endif
                            </td>
                            <td>{{ number_format($cycle->pot_amount, 2) }}</td>
                            <td>
                                @if($cycle->cycle_date)
                                    {{ \Carbon\Carbon::parse($cycle->cycle_date)->format('d M Y') }}
                                    @if(in_array($cycle->status, ['upcoming','active']) && $isMe && $days !== null)
                                        <br>
                                        <small class="text-{{ $days >= 0 ? 'success' : 'danger' }}">
                                            {{ $days >= 0 ? "in {$days}d" : abs($days) . "d overdue" }}
                                        </small>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $statusColors[$cycle->status] ?? 'secondary' }}">
                                    {{ ucfirst($cycle->status) }}
                                </span>
                                @if($cycle->status === 'paid' && $cycle->disbursed_at)
                                    <br><small class="text-muted">
                                        {{ \Carbon\Carbon::parse($cycle->disbursed_at)->format('d M Y') }}
                                    </small>
                                @endif
                            </td>
                            <td>
                                @if(in_array($cycle->status, ['completed','paid']) && $cycle->net_payout_amount > 0)
                                    <strong class="text-success">
                                        KES {{ number_format($cycle->net_payout_amount, 2) }}
                                    </strong>
                                    @if($cycle->bidding_discount > 0)
                                        <br><small class="text-muted">Discount: {{ number_format($cycle->bidding_discount, 2) }}</small>
                                    @endif
                                    @if($cycle->penalties_deducted > 0)
                                        <br><small class="text-danger">Penalty: -{{ number_format($cycle->penalties_deducted, 2) }}</small>
                                    @endif
                                    @if($cycle->disbursement_reference)
                                        <br><small class="text-muted">Ref: {{ $cycle->disbursement_reference }}</small>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="font-weight-bold table-light">
                                <td colspan="2">Total Pot</td>
                                <td>KES {{ number_format($r['all_cycles']->sum('pot_amount'), 2) }}</td>
                                <td colspan="2"></td>
                                <td>
                                    KES {{ number_format($r['all_cycles']->whereIn('status',['completed','paid'])->sum('net_payout_amount'), 2) }}
                                    <small class="text-muted d-block">paid out so far</small>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ── My Past Payouts ─────────────────────────────────────── --}}
                @if($myPast->count())
                <div class="mt-3">
                    <h6><i class="fas fa-history mr-1"></i> My Past Payouts</h6>
                    @foreach($myPast as $past)
                    <div class="alert alert-success py-2">
                        <strong>Cycle #{{ $past->cycle_number }}</strong>
                        — KES {{ number_format($past->net_payout_amount ?: $past->pot_amount, 2) }}
                        @if($past->cycle_date)
                            on {{ \Carbon\Carbon::parse($past->cycle_date)->format('d M Y') }}
                        @endif
                        @if($past->disbursement_reference)
                            &nbsp;· Ref: <code>{{ $past->disbursement_reference }}</code>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif

            </div>
        </div>

        @endforeach

        @endif

        <div class="mt-2">
            <a href="{{ url('portal/sacco/chama') }}" class="btn btn-default">
                <i class="fas fa-arrow-left mr-1"></i> Back to My Groups
            </a>
        </div>

    </div>
</section>

@endsection

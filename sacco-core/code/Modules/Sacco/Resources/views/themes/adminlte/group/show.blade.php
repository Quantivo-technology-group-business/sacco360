@extends('core::layouts.master')
@section('title') Group: {{ $group->name }} @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>{{ $group->name }}
            <span class="badge badge-{{ $group->status==='active'?'success':'warning' }}">{{ $group->status }}</span>
        </h1>
    </div>
</section>
<section class="content">
    <div class="row">
        {{-- Toolbar: the contribution register was previously unreachable from
             this page, so the approval queue had no entry point in the UI. --}}
        <div class="col-12 mb-3">
            @can('sacco.groups.contributions')
            <a href="{{ url('sacco/group/'.$group->id.'/contributions') }}" class="btn btn-primary">
                <i class="fas fa-book"></i> Contribution Register
                @if(($pendingCount ?? 0) > 0)
                    <span class="badge badge-warning">{{ $pendingCount }} pending</span>
                @endif
            </a>
            @endcan
        </div>
    </div>
    <div class="row">
        {{-- Group Info --}}
        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Group Info</h3></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-5">Type</dt><dd class="col-7">{{ ucfirst(str_replace('_',' ',$group->type)) }}</dd>
                        <dt class="col-5">Code</dt><dd class="col-7">{{ $group->code ?? '—' }}</dd>
                        <dt class="col-5">Branch</dt><dd class="col-7">{{ $group->branch->name ?? '—' }}</dd>
                        <dt class="col-5">Contribution</dt><dd class="col-7">{{ number_format($group->monthly_contribution, 2) }}/mo</dd>
                        <dt class="col-5">Share Value</dt><dd class="col-7">{{ number_format($group->share_value, 2) }}</dd>
                        <dt class="col-5">Rotation Model</dt><dd class="col-7">{{ ucfirst($group->rotation_model ?? 'fixed') }}</dd>
                        <dt class="col-5">Late Penalty</dt><dd class="col-7">{{ number_format($group->late_payment_penalty, 2) }}</dd>
                        <dt class="col-5">Chairperson</dt><dd class="col-7">{{ $group->chairperson->full_name ?? '—' }}</dd>
                        <dt class="col-5">Secretary</dt><dd class="col-7">{{ $group->secretary->full_name ?? '—' }}</dd>
                        <dt class="col-5">Treasurer</dt><dd class="col-7">{{ $group->treasurer->full_name ?? '—' }}</dd>
                    </dl>
                </div>
            </div>

            @can('sacco.groups.manage')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Rotation Settings</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/update-settings') }}">
                        @csrf
                        <div class="form-group">
                            <label>Rotation Model</label>
                            <select name="rotation_model" class="form-control">
                                <option value="fixed" {{ ($group->rotation_model ?? 'fixed')==='fixed'?'selected':'' }}>Fixed</option>
                                <option value="random" {{ ($group->rotation_model ?? 'fixed')==='random'?'selected':'' }}>Random</option>
                                <option value="bidding" {{ ($group->rotation_model ?? 'fixed')==='bidding'?'selected':'' }}>Bidding</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Late Payment Penalty</label>
                            <input type="number" name="late_payment_penalty" class="form-control" step="0.01" min="0" value="{{ $group->late_payment_penalty }}">
                        </div>
                        <button class="btn btn-primary btn-block">Save Settings</button>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Post Contributions --}}
            @can('sacco.groups.create')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Post Monthly Contributions</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/post-contributions') }}">
                        @csrf
                        <div class="form-group">
                            <label>Period (YYYY-MM)</label>
                            <input type="month" name="period" class="form-control" value="{{ date('Y-m') }}" required>
                        </div>
                        <button class="btn btn-warning btn-block">Post Contributions</button>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Generate MGR Schedule --}}
            @if($group->type === 'merry_go_round')
            @can('sacco.groups.create')
            <div class="card">
                <div class="card-header"><h3 class="card-title">Merry-Go-Round Schedule</h3></div>
                <div class="card-body">
                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/generate-schedule') }}">
                        @csrf
                        <div class="form-group">
                            <label>First Cycle Date</label>
                            <input type="date" name="start_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <button class="btn btn-info btn-block">Generate Schedule</button>
                    </form>
                </div>
            </div>
            @endcan
            @endif
        </div>

        {{-- Members --}}
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Members ({{ $group->members->where('status','active')->count() }})</h3>
                </div>
                <div class="card-body">
                    @can('sacco.groups.create')
                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/add-member') }}" class="mb-3">
                        @csrf
                        <div class="input-group">
                            <select name="client_id" class="form-control select2" required>
                                <option value="">— Select member to add —</option>
                                @foreach($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->member_number ?? $c->id }})</option>
                                @endforeach
                            </select>
                            <select name="role" class="form-control" style="max-width:150px">
                                <option value="member">Member</option>
                                <option value="chairperson">Chairperson</option>
                                <option value="secretary">Secretary</option>
                                <option value="treasurer">Treasurer</option>
                                <option value="committee">Committee</option>
                            </select>
                            <div class="input-group-append">
                                <button class="btn btn-primary">Add</button>
                            </div>
                        </div>
                    </form>
                    @endcan

                    <table id="members-table" class="table table-sm table-bordered table-hover">
                        <thead><tr><th>Member</th><th>Role</th><th>Joined</th><th>Shares</th><th>Status</th><th></th></tr></thead>
                        <tbody>
                        @foreach($group->members as $m)
                            <tr>
                                <td>{{ $m->client->full_name ?? '—' }}</td>
                                <td>
                                    @can('sacco.groups.manage')
                                    {{-- Inline role change; posts to updateMemberRole. --}}
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/member/'.$m->id.'/role') }}"
                                          class="form-inline role-form" style="display:inline-flex;gap:4px;">
                                        @csrf
                                        <select name="role" class="form-control form-control-sm" style="width:auto">
                                            @foreach(['member','chairperson','secretary','treasurer','committee'] as $r)
                                                <option value="{{ $r }}" @if($m->role === $r) selected @endif>{{ ucfirst($r) }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Save role">
                                            <i class="fas fa-save"></i>
                                        </button>
                                    </form>
                                    @else
                                        {{ ucfirst($m->role) }}
                                    @endcan
                                </td>
                                <td>{{ $m->joined_date }}</td>
                                <td>{{ $m->shares_held }}</td>
                                <td><span class="badge badge-{{ $m->status==='active'?'success':'secondary' }}">{{ $m->status }}</span></td>
                                <td>
                                    @if($m->status==='active')
                                    @can('sacco.groups.manage')
                                    <a href="{{ url('sacco/group/'.$group->id.'/remove-member/'.$m->id) }}"
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Exit this member from the group?')">Exit</a>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- MGR Cycles --}}
            @if($group->merryGoRoundCycles->count())
            @php($nextCycle = $group->merryGoRoundCycles->whereIn('status', ['upcoming','active'])->sortBy('cycle_number')->first())
            <div class="card">
                <div class="card-header"><h3 class="card-title">Merry-Go-Round Cycles</h3></div>
                <div class="card-body">
                    @if($nextCycle)
                    <div class="alert alert-info">
                        <strong>Next Recipient:</strong> {{ $nextCycle->beneficiary->full_name ?? 'To be assigned' }} for Cycle {{ $nextCycle->cycle_number }} on {{ $nextCycle->cycle_date }}.
                    </div>
                    @endif
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>#</th><th>Beneficiary</th><th>Pot</th><th>Collected</th><th>Fines</th><th>Net Payout</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        @foreach($group->merryGoRoundCycles->sortBy('cycle_number') as $cycle)
                            <tr>
                                <td>{{ $cycle->cycle_number }}</td>
                                <td>{{ $cycle->beneficiary->full_name ?? '—' }}</td>
                                <td>{{ number_format($cycle->pot_amount, 2) }}</td>
                                {{-- Pot is what the schedule expected; Collected is
                                     what was actually posted for that period. A gap
                                     between them is a shortfall the group must fund. --}}
                                <td>
                                    @if($cycle->collected_amount !== null)
                                        {{ number_format($cycle->collected_amount, 2) }}
                                        @if((float) $cycle->collected_amount < (float) $cycle->pot_amount)
                                            <i class="fas fa-exclamation-triangle text-danger"
                                               title="Collected less than the scheduled pot"></i>
                                        @endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if((float) $cycle->penalties_deducted > 0)
                                        <span class="text-danger">{{ number_format($cycle->penalties_deducted, 2) }}</span>
                                        <small class="text-muted">({{ $cycle->penalties()->active()->count() }})</small>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($cycle->status, ['completed', 'paid']))
                                        <strong>{{ number_format($cycle->net_payout_amount, 2) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $cycle->cycle_date }}</td>
                                <td><span class="badge badge-{{ $cycle->status==='completed' || $cycle->status==='paid' ?'success':($cycle->status==='active'?'primary':($cycle->status==='skipped'?'warning':'secondary')) }}">{{ $cycle->status }}</span></td>
                                <td>
                                    @if($cycle->status === 'upcoming' || $cycle->status === 'active')
                                    {{-- Gated to match the permissions the routes actually
                                         enforce. These buttons previously required
                                         sacco.groups.manage while the routes required
                                         bidding/disburse, so an officer could see controls
                                         that returned 403, or not see ones they could use. --}}
                                    @can('sacco.groups.bidding')
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/swap-turns') }}" class="mb-1">
                                        @csrf
                                        <input type="hidden" name="cycle_id_a" value="{{ $cycle->id }}">
                                        <select name="cycle_id_b" class="form-control form-control-sm">
                                            <option value="">Swap with...</option>
                                            @foreach($group->merryGoRoundCycles->whereIn('status', ['upcoming','active'])->where('id','!=',$cycle->id)->sortBy('cycle_number') as $other)
                                                <option value="{{ $other->id }}">Cycle {{ $other->cycle_number }}</option>
                                            @endforeach
                                        </select>
                                        <button class="btn btn-sm btn-outline-primary mt-1">Swap</button>
                                    </form>
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/skip-turn/'.$cycle->id) }}" class="mb-1">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-warning">Skip</button>
                                    </form>
                                    @if($group->rotation_model === 'bidding')
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/submit-bid/'.$cycle->id) }}" class="mb-1">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ auth()->id() }}">
                                        <input type="number" name="bid_amount" class="form-control form-control-sm" step="0.01" min="0" placeholder="Bid amount">
                                        <button class="btn btn-sm btn-outline-success mt-1">Submit Bid</button>
                                    </form>
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/resolve-bid/'.$cycle->id) }}" class="mb-1">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-info">Resolve Bids</button>
                                    </form>
                                    @endif
                                    @endcan
                                    @can('sacco.groups.disburse')
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/complete-cycle/'.$cycle->id) }}" class="mb-1"
                                          onsubmit="return confirm('Complete cycle {{ $cycle->cycle_number }}? This assesses late-payment fines for the period and fixes the payout amount.');">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-success">Complete Cycle</button>
                                    </form>
                                    <form method="POST" action="{{ url('sacco/group/'.$group->id.'/disburse-cycle/'.$cycle->id) }}" class="mb-1">
                                        @csrf
                                        <input type="text" name="reference" class="form-control form-control-sm" placeholder="Payment ref">
                                        <button class="btn btn-sm btn-success mt-1">Disburse</button>
                                    </form>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- Per-member late-payment fines. One row per member per cycle, so
                 the amount deducted from a payout is attributable rather than a
                 single unexplained figure taken from the pot. --}}
            @if(($penalties ?? collect())->isNotEmpty())
            <div class="col-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Late Payment Fines</h3>
                        <span class="badge badge-warning ml-2">
                            {{ ($penalties ?? collect())->where('status', 'assessed')->count() }} outstanding
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-2">
                            Configured fine: <strong>{{ number_format($group->late_payment_penalty, 2) }}</strong> per
                            member per cycle. A member is fined when their contribution for the cycle period is
                            posted after the cycle date, or is missing entirely. The amount deducted from a cycle
                            payout is the sum of that cycle's non-waived fines.
                        </p>
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Member</th><th>Cycle</th><th>Period</th><th>Reason</th>
                                    <th>Amount</th><th>Status</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($penalties as $penalty)
                                <tr>
                                    <td>{{ $penalty->client->full_name ?? '—' }}</td>
                                    <td>{{ $penalty->cycle ? '#' . $penalty->cycle->cycle_number : '—' }}</td>
                                    <td>{{ $penalty->period ?? '—' }}</td>
                                    <td>
                                        <small>
                                            {{ $penalty->type_label }}<br>
                                            <span class="text-muted">{{ $penalty->reason }}</span>
                                        </small>
                                    </td>
                                    <td>{{ number_format($penalty->amount, 2) }}</td>
                                    <td>
                                        @php
                                            $pc = ['assessed' => 'warning', 'collected' => 'success', 'waived' => 'secondary'];
                                        @endphp
                                        <span class="badge badge-{{ $pc[$penalty->status] ?? 'secondary' }}">
                                            {{ ucfirst($penalty->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($penalty->status === 'assessed')
                                        @can('sacco.groups.approve')
                                        <form method="POST" action="{{ url('sacco/group/'.$group->id.'/penalty/'.$penalty->id.'/waive') }}">
                                            @csrf
                                            <input type="text" name="reason" class="form-control form-control-sm mb-1"
                                                   placeholder="Reason for waiver" required maxlength="255">
                                            <button class="btn btn-sm btn-outline-secondary"
                                                    onclick="return confirm('Waive this fine? The cycle payout will be recalculated.');">
                                                Waive
                                            </button>
                                        </form>
                                        @endcan
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#members-table').DataTable({ pageLength: 25, order: [[2,'desc']], responsive: true, autoWidth: false });
    </script>
@endsection

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
                                <td>{{ ucfirst($m->role) }}</td>
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
                        <thead><tr><th>#</th><th>Beneficiary</th><th>Pot</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                        <tbody>
                        @foreach($group->merryGoRoundCycles->sortBy('cycle_number') as $cycle)
                            <tr>
                                <td>{{ $cycle->cycle_number }}</td>
                                <td>{{ $cycle->beneficiary->full_name ?? '—' }}</td>
                                <td>{{ number_format($cycle->pot_amount, 2) }}</td>
                                <td>{{ $cycle->cycle_date }}</td>
                                <td><span class="badge badge-{{ $cycle->status==='completed' || $cycle->status==='paid' ?'success':($cycle->status==='active'?'primary':($cycle->status==='skipped'?'warning':'secondary')) }}">{{ $cycle->status }}</span></td>
                                <td>
                                    @if($cycle->status === 'upcoming' || $cycle->status === 'active')
                                    @can('sacco.groups.manage')
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

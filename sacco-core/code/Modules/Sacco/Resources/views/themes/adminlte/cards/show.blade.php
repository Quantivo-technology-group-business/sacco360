@extends('core::layouts.master')
@section('title') Card: {{ $card->masked_number }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
  <h1>{{ $card->masked_number }}
    @php $b=['pending'=>'secondary','active'=>'success','blocked'=>'warning','expired'=>'dark','cancelled'=>'danger'][$card->status]??'secondary'; @endphp
    <span class="badge badge-{{ $b }} badge-lg">{{ ucfirst($card->status) }}</span>
    @if($card->pin_locked)<span class="badge badge-danger">PIN LOCKED</span>@endif
  </h1>
</div></section>
<section class="content">
  <div class="row">
    {{-- LEFT COLUMN --}}
    <div class="col-md-4">

      {{-- Card Visual --}}
      <div class="card" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;border-radius:16px;">
        <div class="card-body">
          <div class="d-flex justify-content-between mb-3">
            <span class="font-weight-bold" style="font-size:1.1rem;">{{ strtoupper($card->card_network) }}</span>
            <span class="badge badge-{{ $card->card_type==='virtual'?'info':'light' }}">{{ ucfirst($card->card_type) }}</span>
          </div>
          <div style="font-size:1.4rem;letter-spacing:4px;" class="mb-3">**** **** **** {{ $card->last_four }}</div>
          <div class="d-flex justify-content-between">
            <div><small>CARDHOLDER</small><br>{{ strtoupper($card->card_name ?? $card->client->full_name ?? '') }}</div>
            <div><small>EXPIRES</small><br>{{ \Carbon\Carbon::parse($card->expiry_date)->format('m/Y') }}</div>
          </div>
        </div>
      </div>

      {{-- Member & Account --}}
      <div class="card"><div class="card-header"><h3 class="card-title">Member & Account</h3></div><div class="card-body">
        <dl class="row mb-0">
          <dt class="col-5">Member</dt><dd class="col-7">{{ $card->client->full_name ?? '—' }}<br><small class="text-muted">{{ $card->client->member_number ?? '' }}</small></dd>
          <dt class="col-5">FOSA A/C</dt><dd class="col-7">{{ $card->savings->account_number ?? $card->savings_id }}</dd>
          <dt class="col-5">Issued</dt><dd class="col-7">{{ $card->issue_date }}</dd>
          <dt class="col-5">Expires</dt><dd class="col-7 {{ $card->isExpired()?'text-danger':'' }}">{{ $card->expiry_date }}</dd>
          <dt class="col-5">Issued By</dt><dd class="col-7">{{ $card->issued_by_id ?? 'System' }}</dd>
          @if($card->blocked_reason)<dt class="col-5 text-danger">Block Reason</dt><dd class="col-7 text-danger">{{ $card->blocked_reason }}</dd>@endif
          @if($card->cancelled_reason)<dt class="col-5 text-danger">Cancel Reason</dt><dd class="col-7 text-danger">{{ $card->cancelled_reason }}</dd>@endif
        </dl>
      </div></div>

      {{-- Delivery --}}
      @if($card->card_type === 'physical')
      <div class="card"><div class="card-header"><h3 class="card-title">Delivery</h3></div><div class="card-body">
        <p><strong>Status:</strong> <span class="badge badge-{{ $card->delivery_status==='delivered'?'success':($card->delivery_status==='dispatched'?'info':'secondary') }}">{{ str_replace('_',' ',ucfirst($card->delivery_status??'')) }}</span></p>
        @if($card->dispatched_date)<p><strong>Dispatched:</strong> {{ $card->dispatched_date }}</p>@endif
        @if($card->delivered_date)<p><strong>Delivered:</strong> {{ $card->delivered_date }}</p>@endif
        @if($card->delivery_address)<p><strong>Address:</strong> {{ $card->delivery_address }}</p>@endif
        @can('sacco.cards.manage')
        <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/delivery') }}" class="mt-2">@csrf
          <div class="input-group input-group-sm">
            <select name="delivery_status" class="form-control">
              @foreach(['not_dispatched','dispatched','delivered','returned'] as $s)
                <option value="{{ $s }}" {{ $card->delivery_status===$s?'selected':'' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
              @endforeach
            </select>
            <input type="date" name="delivery_date" class="form-control" value="{{ date('Y-m-d') }}">
            <div class="input-group-append"><button class="btn btn-warning btn-sm">Update</button></div>
          </div>
        </form>
        @endcan
      </div></div>
      @endif

      {{-- PIN Management --}}
      <div class="card"><div class="card-header"><h3 class="card-title">PIN Management</h3></div><div class="card-body">
        <p>
          <strong>PIN Status:</strong>
          @if($card->pin_locked)<span class="badge badge-danger">Locked ({{ $card->pin_failed_attempts }} failed attempts)</span>
          @elseif($card->pin_set)<span class="badge badge-success">Set</span>
          @else<span class="badge badge-warning">Not Set</span>@endif
        </p>
        @can('sacco.cards.manage')
        <div class="btn-group-vertical btn-block">
          @if(!$card->pin_set || !$card->pin_locked)
          <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/pin/set') }}">@csrf<button class="btn btn-sm btn-success mb-1 btn-block">Mark PIN as Set</button></form>
          @endif
          <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/pin/reset') }}">@csrf<button class="btn btn-sm btn-warning mb-1 btn-block" onclick="return confirm('Reset PIN?')">Reset PIN (Staff)</button></form>
          @if($card->pin_locked)
          <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/pin/unlock') }}">@csrf<button class="btn btn-sm btn-info btn-block">Unlock PIN</button></form>
          @endif
        </div>
        @endcan
        @if($card->pinLogs->count())
        <p class="text-muted mt-2 mb-0"><small>Last action: {{ $card->pinLogs->sortByDesc('created_at')->first()->action }} on {{ $card->pinLogs->sortByDesc('created_at')->first()->created_at->format('Y-m-d H:i') }}</small></p>
        @endif
      </div></div>
    </div>

    {{-- RIGHT COLUMN --}}
    <div class="col-md-8">

      {{-- Spend Summary --}}
      <div class="row mb-3">
        <div class="col-md-4"><div class="info-box bg-info"><span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
          <div class="info-box-content"><span class="info-box-text">Today's Spend</span><span class="info-box-number">{{ number_format($todaySpend,2) }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box bg-primary"><span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
          <div class="info-box-content"><span class="info-box-text">Month Spend</span><span class="info-box-number">{{ number_format($monthlySpend,2) }}</span></div></div></div>
        <div class="col-md-4"><div class="info-box {{ $openDisputes?'bg-danger':'bg-success' }}"><span class="info-box-icon"><i class="fas fa-exclamation-circle"></i></span>
          <div class="info-box-content"><span class="info-box-text">Open Disputes</span><span class="info-box-number">{{ $openDisputes }}</span></div></div></div>
      </div>

      {{-- Spending Limits --}}
      @can('sacco.cards.manage')
      <div class="card card-primary card-outline"><div class="card-header"><h3 class="card-title">Spending Limits</h3></div><div class="card-body">
        <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/limits') }}">@csrf
          <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Overall Daily</label><input type="number" name="daily_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->daily_limit }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>ATM Daily</label><input type="number" name="atm_daily_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->atm_daily_limit }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>POS Daily</label><input type="number" name="pos_daily_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->pos_daily_limit }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Online Daily</label><input type="number" name="online_daily_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->online_daily_limit }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Per-Tap (Contactless)</label><input type="number" name="contactless_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->contactless_limit }}"></div></div>
            <div class="col-md-4"><div class="form-group"><label>Monthly Limit</label><input type="number" name="monthly_limit" class="form-control form-control-sm" step="0.01" value="{{ $card->monthly_limit }}" placeholder="Unlimited"></div></div>
          </div>
          <button class="btn btn-sm btn-primary">Save Limits</button>
        </form>
      </div></div>
      @endcan

      {{-- Channel Controls --}}
      @can('sacco.cards.manage')
      <div class="card card-secondary card-outline"><div class="card-header"><h3 class="card-title">Channel Controls</h3></div><div class="card-body">
        <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/channels') }}">@csrf
          <div class="row">
            @foreach(['atm'=>'ATM','pos'=>'POS','online'=>'Online','contactless'=>'Contactless','international'=>'International'] as $key=>$label)
            <div class="col-md-4 mb-2">
              <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="ch_{{ $key }}" name="{{ $key }}_enabled" value="1"
                  {{ $card->{$key.'_enabled'} ? 'checked' : '' }}>
                <label class="custom-control-label" for="ch_{{ $key }}">{{ $label }}</label>
              </div>
            </div>
            @endforeach
          </div>
          <button class="btn btn-sm btn-secondary mt-2">Save Channel Settings</button>
        </form>
      </div></div>
      @endcan

      {{-- Card Actions --}}
      @can('sacco.cards.manage')
      <div class="card"><div class="card-header"><h3 class="card-title">Card Actions</h3></div><div class="card-body">
        <div class="row">
          @if($card->status === 'active')
          <div class="col-md-4">
            <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/block') }}">@csrf
              <div class="form-group"><input type="text" name="blocked_reason" class="form-control form-control-sm" placeholder="Block reason"></div>
              <button class="btn btn-warning btn-sm btn-block" onclick="return confirm('Block this card?')"><i class="fas fa-ban"></i> Block Card</button>
            </form>
          </div>
          <div class="col-md-4">
            <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/lost') }}">@csrf
              <div class="form-group"><select name="reason" class="form-control form-control-sm">
                <option value="lost">Lost</option><option value="stolen">Stolen</option></select></div>
              <button class="btn btn-danger btn-sm btn-block" onclick="return confirm('Report card? This will block it and create a replacement request.')"><i class="fas fa-exclamation-triangle"></i> Report Lost/Stolen</button>
            </form>
          </div>
          @endif
          @if($card->status === 'blocked' || $card->status === 'pending')
          <div class="col-md-4">
            <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/activate') }}">@csrf
              <button class="btn btn-success btn-sm btn-block mt-4" onclick="return confirm('Activate card?')"><i class="fas fa-check"></i> Activate Card</button>
            </form>
          </div>
          @endif
          @if($card->status !== 'cancelled')
          <div class="col-md-4">
            <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/cancel') }}">@csrf
              <div class="form-group"><input type="text" name="cancelled_reason" class="form-control form-control-sm" placeholder="Cancellation reason" required></div>
              <button class="btn btn-danger btn-sm btn-block" onclick="return confirm('Permanently cancel this card?')"><i class="fas fa-times"></i> Cancel Card</button>
            </form>
          </div>
          @endif
        </div>
      </div></div>
      @endcan

      {{-- Recent Transactions --}}
      <div class="card"><div class="card-header">
        <h3 class="card-title">Recent Transactions</h3>
        <div class="card-tools"><a href="{{ url('sacco/cards/'.$card->id.'/transactions') }}" class="btn btn-sm btn-default">View All</a></div>
      </div><div class="card-body p-0">
        <table class="table table-sm table-bordered mb-0">
          <thead><tr><th>Date/Time</th><th>Channel</th><th>Merchant</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody>
          @forelse($recentTransactions as $t)
            <tr>
              <td>{{ \Carbon\Carbon::parse($t->transaction_at)->format('d M H:i') }}</td>
              <td><span class="badge badge-secondary">{{ strtoupper($t->channel) }}</span></td>
              <td>{{ $t->merchant_name ?? '—' }}</td>
              <td class="{{ in_array($t->transaction_type,['purchase','withdrawal'])?'text-danger':'text-success' }}">{{ number_format($t->amount,2) }}</td>
              <td><span class="badge badge-{{ $t->status==='approved'?'success':($t->status==='declined'?'danger':'secondary') }}">{{ $t->status }}</span></td>
            </tr>
          @empty
            <tr><td colspan="5" class="text-center text-muted">No transactions yet.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div></div>

      {{-- Quick Links --}}
      <div class="mb-2">
        <a href="{{ url('sacco/cards/'.$card->id.'/transactions') }}" class="btn btn-default btn-sm"><i class="fas fa-list"></i> All Transactions</a>
        <a href="{{ url('sacco/cards/'.$card->id.'/disputes') }}" class="btn btn-default btn-sm"><i class="fas fa-exclamation-circle"></i> Disputes</a>
        <a href="{{ url('sacco/cards') }}" class="btn btn-default btn-sm">← Back</a>
      </div>
    </div>
  </div>
</section>
@endsection

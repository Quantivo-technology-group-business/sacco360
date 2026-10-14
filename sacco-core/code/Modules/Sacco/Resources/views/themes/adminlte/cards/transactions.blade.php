@extends('core::layouts.master')
@section('title') Card Transactions — {{ $card->masked_number }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
  <h1>Transactions: {{ $card->masked_number }}<small class="ml-2 text-muted">{{ $card->client->full_name ?? '' }}</small></h1>
</div></section>
<section class="content">
  <div class="row mb-3">
    <div class="col-md-3"><div class="info-box bg-success"><span class="info-box-icon"><i class="fas fa-money-bill-wave"></i></span>
      <div class="info-box-content"><span class="info-box-text">Total Spend</span><span class="info-box-number">{{ number_format($totalSpend,2) }}</span></div></div></div>
    <div class="col-md-3"><div class="info-box bg-info"><span class="info-box-icon"><i class="fas fa-receipt"></i></span>
      <div class="info-box-content"><span class="info-box-text">Total Fees</span><span class="info-box-number">{{ number_format($totalFees,2) }}</span></div></div></div>
    <div class="col-md-6 d-flex align-items-center">
      <a href="{{ url('sacco/cards/'.$card->id.'/show') }}" class="btn btn-default mr-2">← Card Details</a>
      @can('sacco.cards.manage')
      <button class="btn btn-primary" data-toggle="modal" data-target="#recordTxnModal"><i class="fas fa-plus"></i> Record Transaction</button>
      @endcan
    </div>
  </div>

  {{-- Filters --}}
  <div class="card card-outline card-secondary mb-2"><div class="card-body p-2">
    <form method="GET" class="form-inline">
      <select name="channel" class="form-control form-control-sm mr-2">
        <option value="">All Channels</option>
        @foreach(['atm','pos','online','contactless','mobile','other'] as $ch)
          <option value="{{ $ch }}" {{ request('channel')==$ch?'selected':'' }}>{{ strtoupper($ch) }}</option>
        @endforeach
      </select>
      <select name="status" class="form-control form-control-sm mr-2">
        <option value="">All Statuses</option>
        @foreach(['approved','declined','pending','reversed','failed'] as $s)
          <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>
        @endforeach
      </select>
      <input type="date" name="from" class="form-control form-control-sm mr-1" value="{{ request('from') }}" placeholder="From">
      <input type="date" name="to"   class="form-control form-control-sm mr-2" value="{{ request('to') }}" placeholder="To">
      <button class="btn btn-sm btn-primary mr-1">Filter</button>
      <a href="{{ url('sacco/cards/'.$card->id.'/transactions') }}" class="btn btn-sm btn-default">Reset</a>
    </form>
  </div></div>

  <div class="card"><div class="card-body p-0">
    <table class="table table-bordered table-striped mb-0">
      <thead class="thead-dark"><tr>
        <th>Date & Time</th><th>Ref</th><th>Channel</th><th>Type</th>
        <th>Merchant</th><th>Location</th><th>Amount</th><th>Fee</th><th>Balance After</th><th>Status</th>
      </tr></thead>
      <tbody>
      @forelse($transactions as $t)
        <tr>
          <td>{{ \Carbon\Carbon::parse($t->transaction_at)->format('d M Y H:i') }}</td>
          <td><small>{{ $t->transaction_reference }}</small></td>
          <td><span class="badge badge-secondary">{{ strtoupper($t->channel) }}</span>{{ $t->is_international?'<span class="badge badge-info ml-1">INTL</span>':'' }}</td>
          <td>{{ ucfirst(str_replace('_',' ',$t->transaction_type)) }}</td>
          <td>{{ $t->merchant_name ?? '—' }}<br><small class="text-muted">{{ $t->merchant_category ?? '' }}</small></td>
          <td><small>{{ $t->merchant_location ?? '—' }}</small></td>
          <td class="text-right {{ in_array($t->transaction_type,['purchase','withdrawal'])&&$t->status==='approved'?'text-danger font-weight-bold':($t->transaction_type==='refund'?'text-success':'') }}">
            {{ number_format($t->amount,2) }} {{ $t->currency }}
          </td>
          <td class="text-right">{{ $t->fee_amount>0?number_format($t->fee_amount,2):'—' }}</td>
          <td class="text-right">{{ $t->balance_after?number_format($t->balance_after,2):'—' }}</td>
          <td>
            @php $sb=['approved'=>'success','declined'=>'danger','pending'=>'warning','reversed'=>'info','failed'=>'dark'][$t->status]??'secondary'; @endphp
            <span class="badge badge-{{ $sb }}">{{ ucfirst($t->status) }}</span>
            @if($t->decline_reason)<br><small class="text-danger">{{ $t->decline_reason }}</small>@endif
          </td>
        </tr>
      @empty
        <tr><td colspan="10" class="text-center text-muted py-3">No transactions found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div><div class="card-footer">{{ $transactions->appends(request()->query())->links() }}</div></div>

  {{-- Record Transaction Modal --}}
  @can('sacco.cards.manage')
  <div class="modal fade" id="recordTxnModal"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h4 class="modal-title">Record Transaction</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/transactions/record') }}">@csrf
      <div class="modal-body">
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label>Channel *</label>
            <select name="channel" class="form-control" required>
              @foreach(['atm','pos','online','contactless','mobile','other'] as $ch)<option value="{{ $ch }}">{{ strtoupper($ch) }}</option>@endforeach
            </select></div></div>
          <div class="col-md-4"><div class="form-group"><label>Transaction Type *</label>
            <select name="transaction_type" class="form-control" required>
              @foreach(['purchase','withdrawal','refund','fee','transfer','other'] as $tp)<option value="{{ $tp }}">{{ ucfirst($tp) }}</option>@endforeach
            </select></div></div>
          <div class="col-md-4"><div class="form-group"><label>Date & Time *</label>
            <input type="datetime-local" name="transaction_at" class="form-control" value="{{ now()->format('Y-m-d\TH:i') }}" required></div></div>
        </div>
        <div class="row">
          <div class="col-md-3"><div class="form-group"><label>Amount *</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" required></div></div>
          <div class="col-md-3"><div class="form-group"><label>Fee</label><input type="number" name="fee_amount" class="form-control" step="0.01" min="0" value="0"></div></div>
          <div class="col-md-3"><div class="form-group"><label>Currency</label><input type="text" name="currency" class="form-control" value="KES" maxlength="3"></div></div>
          <div class="col-md-3"><div class="form-group"><label>External Reference</label><input type="text" name="external_reference" class="form-control"></div></div>
        </div>
        <div class="row">
          <div class="col-md-4"><div class="form-group"><label>Merchant Name</label><input type="text" name="merchant_name" class="form-control"></div></div>
          <div class="col-md-4"><div class="form-group"><label>Merchant Category</label><input type="text" name="merchant_category" class="form-control" placeholder="e.g. Supermarket, Fuel"></div></div>
          <div class="col-md-4"><div class="form-group"><label>Location</label><input type="text" name="merchant_location" class="form-control"></div></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Record Transaction</button>
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div></div></div>
  @endcan
</section>
@endsection

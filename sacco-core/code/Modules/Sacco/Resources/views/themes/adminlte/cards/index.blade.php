@extends('core::layouts.master')
@section('title') Debit Card Management @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Debit Card Management</h1></div></section>
<section class="content">
  <div class="row mb-3">
    <div class="col-md-2"><div class="info-box bg-primary"><span class="info-box-icon"><i class="fas fa-credit-card"></i></span><div class="info-box-content"><span class="info-box-text">Total</span><span class="info-box-number">{{ $stats['total'] }}</span></div></div></div>
    <div class="col-md-2"><div class="info-box bg-success"><span class="info-box-icon"><i class="fas fa-check-circle"></i></span><div class="info-box-content"><span class="info-box-text">Active</span><span class="info-box-number">{{ $stats['active'] }}</span></div></div></div>
    <div class="col-md-2"><div class="info-box bg-secondary"><span class="info-box-icon"><i class="fas fa-clock"></i></span><div class="info-box-content"><span class="info-box-text">Pending</span><span class="info-box-number">{{ $stats['pending'] }}</span></div></div></div>
    <div class="col-md-2"><div class="info-box bg-warning"><span class="info-box-icon"><i class="fas fa-ban"></i></span><div class="info-box-content"><span class="info-box-text">Blocked</span><span class="info-box-number">{{ $stats['blocked'] }}</span></div></div></div>
    <div class="col-md-2"><div class="info-box bg-danger"><span class="info-box-icon"><i class="fas fa-exclamation-triangle"></i></span><div class="info-box-content"><span class="info-box-text">Expiring 60d</span><span class="info-box-number">{{ $stats['expiring'] }}</span></div></div></div>
    <div class="col-md-2">
      @can('sacco.cards.manage')<a href="{{ url('sacco/cards/create') }}" class="btn btn-primary btn-block mt-1"><i class="fas fa-plus"></i> Issue Card</a>@endcan
      @can('sacco.cards.requests')<a href="{{ url('sacco/cards/requests') }}" class="btn btn-secondary btn-block mt-1">Card Requests</a>@endcan
    </div>
  </div>
  <div class="card card-outline card-secondary mb-2"><div class="card-body p-2">
    <form method="GET" action="{{ url('sacco/cards') }}" class="form-inline">
      <input type="text" name="search" class="form-control form-control-sm mr-2" placeholder="Member / card last 4" value="{{ request('search') }}">
      <select name="status" class="form-control form-control-sm mr-2"><option value="">All Statuses</option>
        @foreach(['pending','active','blocked','expired','cancelled'] as $s)<option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>{{ ucfirst($s) }}</option>@endforeach</select>
      <select name="card_type" class="form-control form-control-sm mr-2"><option value="">All Types</option>
        <option value="physical" {{ request('card_type')=='physical'?'selected':'' }}>Physical</option>
        <option value="virtual"  {{ request('card_type')=='virtual'?'selected':'' }}>Virtual</option></select>
      <button class="btn btn-sm btn-primary">Filter</button>
      <a href="{{ url('sacco/cards') }}" class="btn btn-sm btn-default ml-1">Reset</a>
    </form>
  </div></div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-bordered table-striped mb-0">
      <thead class="thead-dark"><tr>
        <th>Card</th><th>Member</th><th>Type/Network</th><th>Issued</th><th>Expires</th>
        <th>PIN</th><th>Delivery</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
      @forelse($cards as $c)
        <tr>
          <td><strong>{{ $c->masked_number }}</strong><br><small class="text-muted">{{ $c->card_name }}</small></td>
          <td>{{ $c->client->full_name ?? '—' }}<br><small class="text-muted">{{ $c->client->member_number ?? '' }}</small></td>
          <td><span class="badge badge-{{ $c->card_type==='virtual'?'info':'secondary' }}">{{ ucfirst($c->card_type) }}</span> {{ strtoupper($c->card_network) }}</td>
          <td>{{ $c->issue_date }}</td>
          <td class="{{ $c->isExpired()?'text-danger':'' }}">{{ $c->expiry_date }}</td>
          <td>@if($c->pin_locked)<span class="badge badge-danger">Locked</span>@elseif($c->pin_set)<span class="badge badge-success">Set</span>@else<span class="badge badge-warning">Not Set</span>@endif</td>
          <td><span class="badge badge-{{ $c->delivery_status==='delivered'?'success':($c->delivery_status==='dispatched'?'info':'secondary') }}">{{ str_replace('_',' ',ucfirst($c->delivery_status??'')) }}</span></td>
          <td>@php $b=['pending'=>'secondary','active'=>'success','blocked'=>'warning','expired'=>'dark','cancelled'=>'danger'][$c->status]??'secondary';@endphp
            <span class="badge badge-{{ $b }}">{{ ucfirst($c->status) }}</span></td>
          <td><a href="{{ url('sacco/cards/'.$c->id.'/show') }}" class="btn btn-xs btn-info">View</a></td>
        </tr>
      @empty
        <tr><td colspan="9" class="text-center text-muted py-3">No cards found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div><div class="card-footer">{{ $cards->appends(request()->query())->links() }}</div></div>
</section>
@endsection

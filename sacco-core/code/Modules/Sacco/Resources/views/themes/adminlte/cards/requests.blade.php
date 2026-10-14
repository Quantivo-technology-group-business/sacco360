@extends('core::layouts.master')
@section('title') Card Requests @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Card Requests</h1></div></section>
<section class="content">
  <div class="mb-3">
    <a href="{{ url('sacco/cards/requests/create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Card Request</a>
    <a href="{{ url('sacco/cards') }}" class="btn btn-default">← All Cards</a>
  </div>
  <div class="card"><div class="card-body p-0">
    <table class="table table-bordered table-striped mb-0">
      <thead class="thead-dark"><tr>
        <th>ID</th><th>Member</th><th>Type</th><th>Card Type</th><th>Network</th>
        <th>Replacement Reason</th><th>Delivery</th><th>Fee</th><th>Requested</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
      @forelse($requests as $r)
        <tr>
          <td>#{{ $r->id }}</td>
          <td>{{ $r->client->full_name ?? '—' }}<br><small class="text-muted">{{ $r->client->member_number ?? '' }}</small></td>
          <td>{{ ucfirst($r->request_type) }}</td>
          <td>{{ ucfirst($r->card_type) }}</td>
          <td>{{ strtoupper($r->card_network) }}</td>
          <td>{{ $r->replacement_reason ? ucfirst($r->replacement_reason) : '—' }}</td>
          <td>{{ ucfirst(str_replace('_',' ',$r->delivery_method ?? '')) }}</td>
          <td>{{ $r->fee_waived ? '<s>'.number_format($r->card_fee,2).'</s> Waived' : number_format($r->card_fee,2) }}</td>
          <td>{{ $r->requested_date }}</td>
          <td>
            @php $sb=['pending'=>'warning','approved'=>'info','processing'=>'primary','dispatched'=>'secondary','completed'=>'success','rejected'=>'danger'][$r->status]??'secondary'; @endphp
            <span class="badge badge-{{ $sb }}">{{ ucfirst($r->status) }}</span>
          </td>
          <td>
            @if($r->status === 'pending')@can('sacco.cards.requests')
              <a href="{{ url('sacco/cards/requests/'.$r->id.'/approve') }}" class="btn btn-xs btn-success" onclick="return confirm('Approve and issue card?')">Approve</a>
              <button class="btn btn-xs btn-danger" data-toggle="modal" data-target="#rejectModal{{ $r->id }}">Reject</button>
            @endcan@endif
            @if($r->sacco_debit_card_id)<a href="{{ url('sacco/cards/'.$r->sacco_debit_card_id.'/show') }}" class="btn btn-xs btn-info">Card</a>@endif
          </td>
        </tr>
        @if($r->status === 'pending')
        <div class="modal fade" id="rejectModal{{ $r->id }}"><div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h4>Reject Request #{{ $r->id }}</h4><button class="close" data-dismiss="modal">&times;</button></div>
          <form method="POST" action="{{ url('sacco/cards/requests/'.$r->id.'/reject') }}">@csrf
            <div class="modal-body"><div class="form-group"><label>Reason *</label><textarea name="notes" class="form-control" rows="2" required></textarea></div></div>
            <div class="modal-footer"><button class="btn btn-danger">Reject</button><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button></div>
          </form>
        </div></div></div>
        @endif
      @empty
        <tr><td colspan="11" class="text-center text-muted py-3">No card requests found.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div><div class="card-footer">{{ $requests->links() }}</div></div>
</section>
@endsection

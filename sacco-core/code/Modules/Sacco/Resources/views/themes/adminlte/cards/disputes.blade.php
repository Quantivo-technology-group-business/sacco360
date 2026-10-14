@extends('core::layouts.master')
@section('title') Card Disputes — {{ $card->masked_number }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
  <h1>Disputes: {{ $card->masked_number }}<small class="ml-2 text-muted">{{ $card->client->full_name ?? '' }}</small></h1>
</div></section>
<section class="content">
  <div class="mb-3">
    <a href="{{ url('sacco/cards/'.$card->id.'/show') }}" class="btn btn-default">← Card Details</a>
    @can('sacco.cards.disputes')
    <button class="btn btn-primary" data-toggle="modal" data-target="#newDisputeModal"><i class="fas fa-plus"></i> Submit Dispute</button>
    @endcan
  </div>

  {{-- Disputes Table --}}
  <div class="card"><div class="card-header"><h3 class="card-title">Dispute Register</h3></div><div class="card-body p-0">
    <table class="table table-bordered table-striped mb-0">
      <thead class="thead-dark"><tr>
        <th>Reference</th><th>Type</th><th>Disputed Amt</th><th>Settled Amt</th>
        <th>Transaction Date</th><th>Submitted</th><th>Status</th><th>Days to Resolve</th><th></th>
      </tr></thead>
      <tbody>
      @forelse($disputes as $d)
        <tr>
          <td>{{ $d->dispute_reference }}</td>
          <td>{{ ucwords(str_replace('_',' ',$d->dispute_type)) }}</td>
          <td>{{ number_format($d->disputed_amount,2) }}</td>
          <td>{{ $d->settled_amount?number_format($d->settled_amount,2):'—' }}</td>
          <td>{{ $d->transaction_date ?? '—' }}</td>
          <td>{{ $d->submitted_date }}</td>
          <td>
            @php $sb=['submitted'=>'warning','under_investigation'=>'info','resolved_in_favour'=>'success','resolved_against'=>'danger','cancelled'=>'secondary'][$d->status]??'secondary'; @endphp
            <span class="badge badge-{{ $sb }}">{{ ucwords(str_replace('_',' ',$d->status)) }}</span>
          </td>
          <td>{{ $d->resolution_days ?? '—' }}</td>
          <td>
            @if($d->isOpen())@can('sacco.cards.disputes')
            <button class="btn btn-xs btn-success" data-toggle="modal" data-target="#resolveModal{{ $d->id }}">Resolve</button>
            @endcan@endif
          </td>
        </tr>
        {{-- Resolve Modal --}}
        @if($d->isOpen())
        <div class="modal fade" id="resolveModal{{ $d->id }}"><div class="modal-dialog"><div class="modal-content">
          <div class="modal-header"><h4 class="modal-title">Resolve {{ $d->dispute_reference }}</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>
          <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/disputes/'.$d->id.'/resolve') }}">@csrf
            <div class="modal-body">
              <div class="form-group"><label>Outcome *</label>
                <select name="outcome" class="form-control" required>
                  <option value="resolved_in_favour">Resolved in Favour of Member</option>
                  <option value="resolved_against">Resolved Against Member</option>
                </select></div>
              <div class="form-group"><label>Settled Amount (if applicable)</label><input type="number" name="settled_amount" class="form-control" step="0.01" min="0" value="{{ $d->disputed_amount }}"></div>
              <div class="form-group"><label>Resolution Notes *</label><textarea name="resolution_notes" class="form-control" rows="3" required></textarea></div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-success">Submit Resolution</button>
              <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
            </div>
          </form>
        </div></div></div>
        @endif
      @empty
        <tr><td colspan="9" class="text-center text-muted py-3">No disputes raised for this card.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div><div class="card-footer">{{ $disputes->links() }}</div></div>

  {{-- Submit Dispute Modal --}}
  @can('sacco.cards.disputes')
  <div class="modal fade" id="newDisputeModal"><div class="modal-dialog modal-lg"><div class="modal-content">
    <div class="modal-header"><h4 class="modal-title">Submit Card Dispute</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <form method="POST" action="{{ url('sacco/cards/'.$card->id.'/disputes/store') }}">@csrf
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6"><div class="form-group"><label>Dispute Type *</label>
            <select name="dispute_type" class="form-control" required>
              <option value="unauthorized_transaction">Unauthorized Transaction</option>
              <option value="amount_mismatch">Amount Mismatch</option>
              <option value="goods_not_received">Goods/Services Not Received</option>
              <option value="duplicate_charge">Duplicate Charge</option>
              <option value="atm_dispense_error">ATM Dispense Error</option>
              <option value="fraud">Fraud</option>
              <option value="other">Other</option>
            </select></div></div>
          <div class="col-md-3"><div class="form-group"><label>Disputed Amount *</label><input type="number" name="disputed_amount" class="form-control" step="0.01" min="0.01" required></div></div>
          <div class="col-md-3"><div class="form-group"><label>Transaction Date</label><input type="date" name="transaction_date" class="form-control"></div></div>
        </div>
        <div class="row">
          <div class="col-md-6"><div class="form-group"><label>Merchant Name</label><input type="text" name="merchant_name" class="form-control"></div></div>
          <div class="col-md-6"><div class="form-group"><label>Linked Transaction (optional)</label>
            <select name="sacco_card_transaction_id" class="form-control">
              <option value="">— None —</option>
              @foreach($cardTransactions as $t)<option value="{{ $t->id }}">{{ \Carbon\Carbon::parse($t->transaction_at)->format('d M H:i') }} — {{ $t->merchant_name ?? $t->channel }} — {{ number_format($t->amount,2) }}</option>@endforeach
            </select></div></div>
        </div>
        <div class="form-group"><label>Description *</label><textarea name="description" class="form-control" rows="3" required placeholder="Describe the dispute in detail..."></textarea></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Submit Dispute</button><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button></div>
    </form>
  </div></div></div>
  @endcan
</section>
@endsection

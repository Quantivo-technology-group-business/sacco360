@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.bosa_fosa_transfer', 1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.bosa_fosa_transfer', 2) }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{session('error')}}</div>@endif

    <div class="row">
        <div class="col-md-4">
            <div class="card"><div class="card-body">
                <h5>Account Balances</h5>
                <p>{{ trans('sacco::general.bosa') }}: {{$bosa ? number_format((new \Modules\Sacco\Services\WingService())->getAccountBalance($bosa->id),2) : 'N/A'}}</p>
                <p>{{ trans('sacco::general.fosa') }}: {{$fosa ? number_format((new \Modules\Sacco\Services\WingService())->getAccountBalance($fosa->id),2) : 'N/A'}}</p>
            </div></div>
            @if(!empty($suggestions))
            <div class="card mt-3"><div class="card-header">{{ trans('sacco::general.cross_sell_suggestions') }}</div><div class="card-body">
                @foreach($suggestions as $s)
                    <p class="mb-1"><strong>{{$s['campaign']}}</strong>: {{$s['message']}}</p>
                    @if(($s['target'] ?? '') === 'bosa')
                        <form method="post" action="{{url('portal/sacco/open-bosa')}}" class="mb-2">@csrf
                            <button class="btn btn-sm btn-primary">{{ trans('sacco::general.open_bosa_account') }}</button>
                        </form>
                    @endif
                @endforeach
            </div></div>
            @endif
        </div>
        <div class="col-md-8">
            <div class="card"><div class="card-body">
                <form method="post" action="{{url('portal/sacco/transfer/store')}}">@csrf
                    <div class="form-group">
                        <label>Transfer Type</label>
                        <select name="transfer_type" class="form-control" id="transfer_type">
                            <option value="savings_to_savings">BOSA to FOSA</option>
                            <option value="savings_to_loan">FOSA to Loan Repayment</option>
                            <option value="share_to_savings">Share to BOSA</option>
                            <option value="savings_to_share">BOSA to Share Purchase</option>
                        </select>
                    </div>
                    <div class="form-group" id="source_group">
                        <label>From</label>
                        <select name="source_id" id="source_select" class="form-control"></select>
                    </div>
                    <div class="form-group" id="dest_group">
                        <label>To</label>
                        <select name="destination_id" id="dest_select" class="form-control"></select>
                    </div>
                    <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
                    @include('portal::sacco.partials.transaction_auth', ['purpose' => 'transfer'])
                    <button class="btn btn-primary">Transfer</button>
                </form>
            </div></div>
        </div>
    </div>
</section>
<script>
(function() {
    var bosa = @json($transferFormData['bosa']);
    var fosa = @json($transferFormData['fosa']);
    var loans = @json($transferFormData['loans']);
    var shares = @json($transferFormData['shares']);

    function asList(item) { return item ? [item] : []; }

    function fillSelect(el, items, placeholder) {
        el.innerHTML = '';
        if (!items || !items.length) {
            var o = document.createElement('option'); o.value = ''; o.textContent = placeholder || 'None available'; el.appendChild(o);
            return;
        }
        items.forEach(function(item) {
            var o = document.createElement('option'); o.value = item.id; o.textContent = item.label; el.appendChild(o);
        });
    }

    function updateForm() {
        var type = document.getElementById('transfer_type').value;
        var source = document.getElementById('source_select');
        var dest = document.getElementById('dest_select');
        if (type === 'savings_to_savings') {
            fillSelect(source, asList(bosa), 'No BOSA account');
            fillSelect(dest, asList(fosa), 'No FOSA account');
        } else if (type === 'savings_to_loan') {
            fillSelect(source, asList(fosa), 'No FOSA account');
            fillSelect(dest, loans, 'No active loans');
        } else if (type === 'share_to_savings') {
            fillSelect(source, shares, 'No share accounts');
            fillSelect(dest, asList(bosa), 'No BOSA account');
        } else if (type === 'savings_to_share') {
            fillSelect(source, asList(bosa), 'No BOSA account');
            fillSelect(dest, shares, 'No share accounts');
        }
    }

    document.getElementById('transfer_type').addEventListener('change', updateForm);
    updateForm();
})();
</script>
@endsection

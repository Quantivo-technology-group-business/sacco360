@extends('core::layouts.master')
@section('title') Agent Portal - Utility Payment @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-bolt mr-2 text-primary"></i>Pay Utilities</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Utility Payment</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-wallet mr-2"></i>
            <strong>Float Balance:</strong> {{number_format($agent->float_balance, 2)}}
        </div>
        <a href="{{route('agent.dashboard')}}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card card-primary">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-bolt mr-2"></i>Utility Payment</h5>
                </div>
                <div class="card-body">
                    <form id="utilityForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Select Provider <span class="text-danger">*</span></label>
                                    <select id="providerId" class="form-control form-control-lg" required>
                                        <option value="">-- Select Utility Provider --</option>
                                        @foreach($providers as $provider)
                                        <option value="{{$provider->id}}" data-fee="{{$provider->fee ?? 0}}">
                                            {{$provider->name}}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Account/Meter Number <span class="text-danger">*</span></label>
                                    <input type="text" id="accountNumber" class="form-control form-control-lg" placeholder="Enter account number" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Customer Name (Optional)</label>
                                    <input type="text" id="customerName" class="form-control" placeholder="Customer name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                        <input type="number" step="0.01" min="1" id="amount" class="form-control form-control-lg" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Notes (Optional)</label>
                                    <input type="text" id="notes" class="form-control" placeholder="Any notes...">
                                </div>
                            </div>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body py-2">
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted">Amount</small>
                                        <h5 class="mb-0" id="displayAmount">0.00</h5>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Fee</small>
                                        <h5 class="mb-0" id="displayFee">0.00</h5>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Total</small>
                                        <h5 class="mb-0 text-primary" id="displayTotal">0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="utilityAlert" class="alert d-none"></div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block" id="btnProcessPayment">
                            <i class="fas fa-bolt mr-2"></i> Process Payment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Recent Utility Payments</h5>
                </div>
                <div class="card-body p-0">
                    <a href="{{route('agent.utility_payments')}}" class="btn btn-outline-primary btn-sm btn-block m-2" style="width:auto;">
                        View All Payments
                    </a>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0">Available Providers</h5>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        @forelse($providers as $provider)
                        <li class="list-group-item d-flex justify-content-between">
                            <span><i class="fas fa-building mr-2 text-muted"></i>{{$provider->name}}</span>
                            @if($provider->fee > 0)
                            <small class="text-muted">Fee: {{number_format($provider->fee, 2)}}</small>
                            @endif
                        </li>
                        @empty
                        <li class="list-group-item text-muted">No providers configured</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Payment Successful</h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h4 id="successMessage"></h4>
                <p class="text-muted">Reference: <strong id="successReference"></strong></p>
                <p>Commission Earned: <span class="badge badge-info" id="successCommission"></span></p>
                <p>New Float Balance: <strong id="successFloat"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" onclick="location.reload()">
                    <i class="fas fa-plus mr-1"></i> New Payment
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var csrfToken = '{{csrf_token()}}';
var currentFee = 0;

document.getElementById('providerId').addEventListener('change', function() {
    var option = this.options[this.selectedIndex];
    currentFee = parseFloat(option.dataset.fee || 0);
    updateTotals();
});

document.getElementById('amount').addEventListener('input', updateTotals);

function updateTotals() {
    var amount = parseFloat(document.getElementById('amount').value) || 0;
    var total = amount + currentFee;
    
    document.getElementById('displayAmount').textContent = amount.toFixed(2);
    document.getElementById('displayFee').textContent = currentFee.toFixed(2);
    document.getElementById('displayTotal').textContent = total.toFixed(2);
}

document.getElementById('utilityForm').addEventListener('submit', function(e) {
    e.preventDefault();

    var providerId = document.getElementById('providerId').value;
    var accountNumber = document.getElementById('accountNumber').value.trim();
    var amount = parseFloat(document.getElementById('amount').value);

    if (!providerId || !accountNumber || !amount || amount <= 0) {
        showAlert('utilityAlert', 'danger', 'Please fill in all required fields');
        return;
    }

    var btn = document.getElementById('btnProcessPayment');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

    fetch('{{route("agent.utility_payment.process")}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
            provider_id: providerId,
            account_number: accountNumber,
            customer_name: document.getElementById('customerName').value,
            amount: amount,
            fee: currentFee,
            notes: document.getElementById('notes').value
        })
    })
    .then(r => r.json())
    .then(function(data) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt mr-2"></i> Process Payment';

        if (data.success) {
            document.getElementById('successMessage').textContent = data.message;
            document.getElementById('successReference').textContent = data.reference;
            document.getElementById('successCommission').textContent = parseFloat(data.commission || 0).toFixed(2);
            document.getElementById('successFloat').textContent = parseFloat(data.new_float_balance).toFixed(2);
            $('#successModal').modal('show');
        } else {
            showAlert('utilityAlert', 'danger', data.message);
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-bolt mr-2"></i> Process Payment';
        showAlert('utilityAlert', 'danger', 'Payment failed: ' + err.message);
    });
});

function showAlert(id, type, message) {
    var el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = message;
}
</script>
@endsection

@extends('core::layouts.master')
@section('title') Agent Portal - Cash Withdrawal @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-arrow-up mr-2 text-warning"></i>Cash Withdrawal</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Withdrawal</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-wallet mr-2"></i>
            <strong>Float Balance:</strong> {{number_format($agent->float_balance, 2)}}
            <span class="mx-3">|</span>
            <small>Withdrawals will deduct from your float balance</small>
        </div>
        <a href="{{route('agent.dashboard')}}" class="btn btn-outline-dark btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card card-warning card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i>Member Lookup</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="memberSearch" class="form-control form-control-lg" placeholder="Search by name, phone, ID...">
                        <div class="input-group-append">
                            <button class="btn btn-warning btn-lg" id="btnSearch"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div id="searchResults" class="list-group" style="max-height:400px;overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        <div class="col-md-7">
            <div id="memberPanel" style="display:none;">
                <div class="card card-primary card-outline mb-3">
                    <div class="card-body py-3">
                        <div class="d-flex align-items-center">
                            <div id="memberPhotoPlaceholder" class="mr-3 bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;font-size:24px;"></div>
                            <div class="flex-grow-1">
                                <h5 class="mb-0"><strong id="memberName"></strong></h5>
                                <small class="text-muted">
                                    <i class="fas fa-id-card mr-1"></i><span id="memberNumber"></span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-phone mr-1"></i><span id="memberPhone"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card card-warning">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-piggy-bank mr-2"></i>Select Account for Withdrawal</h5>
                    </div>
                    <div class="card-body">
                        <div id="savingsAccounts"></div>
                    </div>
                </div>
            </div>

            <div id="noMemberSelected" class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-search fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Search and select a member to process withdrawal</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title"><i class="fas fa-arrow-up mr-2"></i>Process Withdrawal</h5>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" id="withdrawalAccountInfo"></div>
                <div class="alert alert-warning py-2 mb-3">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Your Float Balance: <strong>{{number_format($agent->float_balance, 2)}}</strong>
                </div>
                
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="0.01" min="1" id="withdrawalAmount" class="form-control form-control-lg" placeholder="0.00" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" id="withdrawalDate" class="form-control" value="{{date('Y-m-d')}}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payment Type <span class="text-danger">*</span></label>
                            <select id="withdrawalPaymentType" class="form-control" required>
                                @foreach($paymentTypes as $pt)
                                <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <input type="text" id="withdrawalNotes" class="form-control" placeholder="Any notes...">
                </div>

                <div id="withdrawalAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning btn-lg" id="btnConfirmWithdrawal">
                    <i class="fas fa-check mr-1"></i> Confirm Withdrawal
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Withdrawal Successful</h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h4 id="successMessage"></h4>
                <p class="text-muted">Reference: <strong id="successReference"></strong></p>
                <p>Commission Earned: <span class="badge badge-info" id="successCommission"></span></p>
                <p>New Float Balance: <strong id="successFloat"></strong></p>
            </div>
            <div class="modal-footer">
                <a href="#" id="printReceiptBtn" class="btn btn-outline-secondary" target="_blank">
                    <i class="fas fa-print mr-1"></i> Print Receipt
                </a>
                <button type="button" class="btn btn-success" onclick="location.reload()">
                    <i class="fas fa-plus mr-1"></i> New Transaction
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
var selectedClientId = null;
var selectedSavingsId = null;
var selectedBalance = 0;
var csrfToken = '{{csrf_token()}}';
var agentFloat = {{$agent->float_balance}};

document.getElementById('btnSearch').addEventListener('click', doSearch);
document.getElementById('memberSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doSearch();
});

function doSearch() {
    var q = document.getElementById('memberSearch').value.trim();
    if (q.length < 2) return;

    fetch('{{route("agent.member.lookup")}}?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function(data) {
            var html = '';
            if (!data.length) {
                html = '<div class="list-group-item text-muted">No members found</div>';
            }
            data.forEach(function(m) {
                html += '<a href="#" class="list-group-item list-group-item-action member-select" data-id="'+m.id+'">'
                      + '<strong>'+m.name+'</strong><br><small class="text-muted">'+m.member_number+' | '+m.phone+'</small></a>';
            });
            document.getElementById('searchResults').innerHTML = html;
        });
}

document.getElementById('searchResults').addEventListener('click', function(e) {
    var el = e.target.closest('.member-select');
    if (!el) return;
    e.preventDefault();
    loadMemberDetails(el.dataset.id);
});

function loadMemberDetails(clientId) {
    selectedClientId = clientId;

    fetch('{{route("agent.member.details")}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({client_id: clientId})
    })
    .then(r => r.json())
    .then(function(data) {
        document.getElementById('memberName').textContent = data.client.name;
        document.getElementById('memberNumber').textContent = data.client.member_number;
        document.getElementById('memberPhone').textContent = data.client.phone || 'N/A';
        
        var initials = data.client.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        document.getElementById('memberPhotoPlaceholder').textContent = initials;

        var savingsHtml = '';
        if (!data.savings.length) {
            savingsHtml = '<div class="alert alert-warning">No active savings accounts found.</div>';
        } else {
            data.savings.forEach(function(s) {
                var canWithdraw = parseFloat(s.balance) > 0 && agentFloat > 0;
                savingsHtml += '<div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">'
                    + '<div><strong>'+s.account_no+'</strong> <span class="badge badge-secondary">'+s.product+'</span><br>'
                    + '<span class="text-success"><i class="fas fa-wallet mr-1"></i>Balance: <strong>'+parseFloat(s.balance).toFixed(2)+'</strong></span></div>'
                    + '<button class="btn btn-warning btn-withdraw" data-id="'+s.id+'" data-account="'+s.account_no+'" data-product="'+s.product+'" data-balance="'+s.balance+'"'
                    + (canWithdraw ? '' : ' disabled') + '>'
                    + '<i class="fas fa-arrow-up mr-1"></i> Withdraw</button></div>';
            });
        }
        document.getElementById('savingsAccounts').innerHTML = savingsHtml;
        document.getElementById('memberPanel').style.display = 'block';
        document.getElementById('noMemberSelected').style.display = 'none';

        document.querySelectorAll('.btn-withdraw').forEach(function(btn) {
            btn.addEventListener('click', function() {
                selectedSavingsId = this.dataset.id;
                selectedBalance = parseFloat(this.dataset.balance);
                document.getElementById('withdrawalAccountInfo').innerHTML = 
                    '<strong>'+this.dataset.account+'</strong> - '+this.dataset.product+
                    '<br>Available Balance: <strong>'+selectedBalance.toFixed(2)+'</strong>';
                document.getElementById('withdrawalAmount').value = '';
                document.getElementById('withdrawalAmount').max = Math.min(selectedBalance, agentFloat);
                document.getElementById('withdrawalAlert').className = 'alert d-none';
                $('#withdrawalModal').modal('show');
            });
        });
    });
}

document.getElementById('btnConfirmWithdrawal').addEventListener('click', function() {
    var amount = parseFloat(document.getElementById('withdrawalAmount').value);

    if (!amount || amount <= 0) {
        showAlert('withdrawalAlert', 'danger', 'Please enter a valid amount');
        return;
    }
    if (amount > selectedBalance) {
        showAlert('withdrawalAlert', 'danger', 'Amount exceeds account balance');
        return;
    }
    if (amount > agentFloat) {
        showAlert('withdrawalAlert', 'danger', 'Amount exceeds your float balance');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

    fetch('{{route("agent.withdrawal.process")}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
            savings_id: selectedSavingsId,
            amount: amount,
            date: document.getElementById('withdrawalDate').value,
            payment_type_id: document.getElementById('withdrawalPaymentType').value,
            notes: document.getElementById('withdrawalNotes').value
        })
    })
    .then(r => r.json())
    .then(function(data) {
        document.getElementById('btnConfirmWithdrawal').disabled = false;
        document.getElementById('btnConfirmWithdrawal').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Withdrawal';

        if (data.success) {
            $('#withdrawalModal').modal('hide');
            document.getElementById('successMessage').textContent = data.message;
            document.getElementById('successReference').textContent = data.reference;
            document.getElementById('successCommission').textContent = parseFloat(data.commission || 0).toFixed(2);
            document.getElementById('successFloat').textContent = parseFloat(data.new_float_balance).toFixed(2);
            document.getElementById('printReceiptBtn').href = '{{url("agent/receipt")}}/' + data.transaction_id;
            $('#successModal').modal('show');
        } else {
            showAlert('withdrawalAlert', 'danger', data.message);
        }
    })
    .catch(function(err) {
        document.getElementById('btnConfirmWithdrawal').disabled = false;
        document.getElementById('btnConfirmWithdrawal').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Withdrawal';
        showAlert('withdrawalAlert', 'danger', 'Transaction failed: ' + err.message);
    });
});

function showAlert(id, type, message) {
    var el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = message;
}
</script>
@endsection

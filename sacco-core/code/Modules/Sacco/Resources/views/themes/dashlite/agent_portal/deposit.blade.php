@extends('core::layouts.master')
@section('title') Agent Portal - Receive Deposit @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-arrow-down mr-2 text-success"></i>Receive Deposit</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Deposit</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    {{-- Agent Status Bar --}}
    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
        <div>
            <i class="fas fa-wallet mr-2"></i>
            <strong>Float Balance:</strong> {{number_format($agent->float_balance, 2)}}
            @if($agent->daily_transaction_limit)
            <span class="mx-3">|</span>
            <strong>Daily Limit Remaining:</strong> {{number_format($agent->daily_transaction_limit - $agent->getTodayTransactionTotal(), 2)}}
            @endif
        </div>
        <a href="{{route('agent.dashboard')}}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        {{-- Member Lookup --}}
        <div class="col-md-5">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i>Member Lookup</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="memberSearch" class="form-control form-control-lg" placeholder="Search by name, phone, ID...">
                        <div class="input-group-append">
                            <button class="btn btn-success btn-lg" id="btnSearch"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div id="searchResults" class="list-group" style="max-height:400px;overflow-y:auto;"></div>
                </div>
            </div>
        </div>

        {{-- Member Panel & Deposit Form --}}
        <div class="col-md-7">
            <div id="memberPanel" style="display:none;">
                {{-- Member Info --}}
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

                {{-- Savings Accounts --}}
                <div class="card card-success">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-piggy-bank mr-2"></i>Select Savings Account</h5>
                    </div>
                    <div class="card-body">
                        <div id="savingsAccounts"></div>
                    </div>
                </div>
            </div>

            <div id="noMemberSelected" class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-search fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Search and select a member to process deposit</h5>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Deposit Modal --}}
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-arrow-down mr-2"></i>Process Deposit</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" id="depositAccountInfo"></div>
                
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="0.01" min="1" id="depositAmount" class="form-control form-control-lg" placeholder="0.00" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" id="depositDate" class="form-control" value="{{date('Y-m-d')}}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payment Type <span class="text-danger">*</span></label>
                            <select id="depositPaymentType" class="form-control" required>
                                @foreach($paymentTypes as $pt)
                                <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <input type="text" id="depositNotes" class="form-control" placeholder="Any notes...">
                </div>

                <div id="depositAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success btn-lg" id="btnConfirmDeposit">
                    <i class="fas fa-check mr-1"></i> Confirm Deposit
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Success Modal --}}
<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Deposit Successful</h5>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-check-circle fa-5x text-success mb-3"></i>
                <h4 id="successMessage"></h4>
                <p class="text-muted">Reference: <strong id="successReference"></strong></p>
                <p>Commission Earned: <span class="badge badge-info badge-lg" id="successCommission"></span></p>
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
var clientData = null;
var csrfToken = '{{csrf_token()}}';

document.getElementById('btnSearch').addEventListener('click', doSearch);
document.getElementById('memberSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doSearch();
});

function doSearch() {
    var q = document.getElementById('memberSearch').value.trim();
    if (q.length < 2) return;

    document.getElementById('searchResults').innerHTML = '<div class="text-center py-3"><i class="fas fa-spinner fa-spin"></i> Searching...</div>';

    fetch('{{route("agent.member.lookup")}}?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function(data) {
            var html = '';
            if (!data.length) {
                html = '<div class="list-group-item text-muted">No members found</div>';
            }
            data.forEach(function(m) {
                html += '<a href="#" class="list-group-item list-group-item-action member-select" data-id="'+m.id+'">'
                      + '<div class="d-flex justify-content-between">'
                      + '<div><strong>'+m.name+'</strong><br><small class="text-muted">'+m.member_number+'</small></div>'
                      + '<div class="text-right"><small class="text-muted">'+m.phone+'</small></div>'
                      + '</div></a>';
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
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({client_id: clientId})
    })
    .then(r => r.json())
    .then(function(data) {
        clientData = data.client;
        
        document.getElementById('memberName').textContent = data.client.name;
        document.getElementById('memberNumber').textContent = data.client.member_number;
        document.getElementById('memberPhone').textContent = data.client.phone || 'N/A';
        
        var initials = data.client.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2);
        document.getElementById('memberPhotoPlaceholder').textContent = initials;

        var savingsHtml = '';
        if (!data.savings.length) {
            savingsHtml = '<div class="alert alert-warning">No active savings accounts found for this member.</div>';
        } else {
            data.savings.forEach(function(s) {
                savingsHtml += '<div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">'
                    + '<div>'
                    + '<strong>'+s.account_no+'</strong> <span class="badge badge-secondary">'+s.product+'</span><br>'
                    + '<span class="text-success"><i class="fas fa-wallet mr-1"></i>Balance: <strong>'+parseFloat(s.balance).toFixed(2)+'</strong></span>'
                    + '</div>'
                    + '<button class="btn btn-success btn-deposit" data-id="'+s.id+'" data-account="'+s.account_no+'" data-product="'+s.product+'" data-balance="'+s.balance+'">'
                    + '<i class="fas fa-arrow-down mr-1"></i> Deposit'
                    + '</button></div>';
            });
        }
        document.getElementById('savingsAccounts').innerHTML = savingsHtml;

        document.getElementById('memberPanel').style.display = 'block';
        document.getElementById('noMemberSelected').style.display = 'none';

        document.querySelectorAll('.btn-deposit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                selectedSavingsId = this.dataset.id;
                document.getElementById('depositAccountInfo').innerHTML = 
                    '<strong>'+this.dataset.account+'</strong> - '+this.dataset.product+
                    '<br>Current Balance: <strong>'+parseFloat(this.dataset.balance).toFixed(2)+'</strong>';
                document.getElementById('depositAmount').value = '';
                document.getElementById('depositNotes').value = '';
                document.getElementById('depositAlert').className = 'alert d-none';
                $('#depositModal').modal('show');
            });
        });
    })
    .catch(function(err) {
        alert('Failed to load member details: ' + err.message);
    });
}

document.getElementById('btnConfirmDeposit').addEventListener('click', function() {
    var amount = document.getElementById('depositAmount').value;
    var date = document.getElementById('depositDate').value;
    var paymentType = document.getElementById('depositPaymentType').value;
    var notes = document.getElementById('depositNotes').value;

    if (!amount || parseFloat(amount) <= 0) {
        showAlert('depositAlert', 'danger', 'Please enter a valid amount');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

    fetch('{{route("agent.deposit.process")}}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            savings_id: selectedSavingsId,
            amount: amount,
            date: date,
            payment_type_id: paymentType,
            notes: notes
        })
    })
    .then(r => r.json())
    .then(function(data) {
        document.getElementById('btnConfirmDeposit').disabled = false;
        document.getElementById('btnConfirmDeposit').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Deposit';

        if (data.success) {
            $('#depositModal').modal('hide');
            document.getElementById('successMessage').textContent = data.message;
            document.getElementById('successReference').textContent = data.reference;
            document.getElementById('successCommission').textContent = parseFloat(data.commission || 0).toFixed(2);
            document.getElementById('successFloat').textContent = parseFloat(data.new_float_balance).toFixed(2);
            document.getElementById('printReceiptBtn').href = '{{url("agent/receipt")}}/' + data.transaction_id;
            $('#successModal').modal('show');
        } else {
            showAlert('depositAlert', 'danger', data.message);
        }
    })
    .catch(function(err) {
        document.getElementById('btnConfirmDeposit').disabled = false;
        document.getElementById('btnConfirmDeposit').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Deposit';
        showAlert('depositAlert', 'danger', 'Transaction failed: ' + err.message);
    });
});

function showAlert(id, type, message) {
    var el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = message;
}

@if(request('client_id'))
loadMemberDetails('{{request("client_id")}}');
@endif
</script>
@endsection

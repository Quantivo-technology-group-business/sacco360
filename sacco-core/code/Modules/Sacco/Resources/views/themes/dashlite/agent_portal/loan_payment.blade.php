@extends('core::layouts.master')
@section('title') Agent Portal - Loan Repayment @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-hand-holding-usd mr-2 text-info"></i>Receive Loan Repayment</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Loan Repayment</li>
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
            <span class="mx-3">|</span>
            <small>Loan repayments will add to your float balance</small>
        </div>
        <a href="{{route('agent.dashboard')}}" class="btn btn-outline-light btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
        </a>
    </div>

    <div class="row">
        <div class="col-md-5">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i>Member Lookup</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-3">
                        <input type="text" id="memberSearch" class="form-control form-control-lg" placeholder="Search by name, phone, ID...">
                        <div class="input-group-append">
                            <button class="btn btn-info btn-lg" id="btnSearch"><i class="fas fa-search"></i></button>
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

                <div class="card card-info">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-file-invoice-dollar mr-2"></i>Active Loans</h5>
                    </div>
                    <div class="card-body">
                        <div id="loanAccounts"></div>
                    </div>
                </div>
            </div>

            <div id="noMemberSelected" class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-user-search fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Search and select a member to process loan repayment</h5>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="repaymentModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="fas fa-hand-holding-usd mr-2"></i>Process Loan Repayment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" id="repaymentLoanInfo"></div>
                
                <div class="form-group">
                    <label>Repayment Amount <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                        <input type="number" step="0.01" min="1" id="repaymentAmount" class="form-control form-control-lg" placeholder="0.00" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date <span class="text-danger">*</span></label>
                            <input type="date" id="repaymentDate" class="form-control" value="{{date('Y-m-d')}}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payment Type <span class="text-danger">*</span></label>
                            <select id="repaymentPaymentType" class="form-control" required>
                                @foreach($paymentTypes as $pt)
                                <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notes (Optional)</label>
                    <input type="text" id="repaymentNotes" class="form-control" placeholder="Any notes...">
                </div>

                <div id="repaymentAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info btn-lg" id="btnConfirmRepayment">
                    <i class="fas fa-check mr-1"></i> Confirm Repayment
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="successModal" tabindex="-1" data-backdrop="static">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-check-circle mr-2"></i>Repayment Successful</h5>
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
var selectedLoanId = null;
var csrfToken = '{{csrf_token()}}';

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

        var loansHtml = '';
        if (!data.loans.length) {
            loansHtml = '<div class="alert alert-warning">No active loans found for this member.</div>';
        } else {
            data.loans.forEach(function(l) {
                loansHtml += '<div class="d-flex justify-content-between align-items-center border rounded p-3 mb-2">'
                    + '<div><strong>'+l.account_no+'</strong> <span class="badge badge-secondary">'+l.product+'</span><br>'
                    + '<span class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>Outstanding: <strong>'+parseFloat(l.outstanding).toFixed(2)+'</strong></span></div>'
                    + '<button class="btn btn-info btn-repay" data-id="'+l.id+'" data-account="'+l.account_no+'" data-product="'+l.product+'" data-outstanding="'+l.outstanding+'">'
                    + '<i class="fas fa-hand-holding-usd mr-1"></i> Pay</button></div>';
            });
        }
        document.getElementById('loanAccounts').innerHTML = loansHtml;
        document.getElementById('memberPanel').style.display = 'block';
        document.getElementById('noMemberSelected').style.display = 'none';

        document.querySelectorAll('.btn-repay').forEach(function(btn) {
            btn.addEventListener('click', function() {
                selectedLoanId = this.dataset.id;
                document.getElementById('repaymentLoanInfo').innerHTML = 
                    '<strong>'+this.dataset.account+'</strong> - '+this.dataset.product+
                    '<br>Outstanding Balance: <strong class="text-danger">'+parseFloat(this.dataset.outstanding).toFixed(2)+'</strong>';
                document.getElementById('repaymentAmount').value = '';
                document.getElementById('repaymentAlert').className = 'alert d-none';
                $('#repaymentModal').modal('show');
            });
        });
    });
}

document.getElementById('btnConfirmRepayment').addEventListener('click', function() {
    var amount = parseFloat(document.getElementById('repaymentAmount').value);

    if (!amount || amount <= 0) {
        showAlert('repaymentAlert', 'danger', 'Please enter a valid amount');
        return;
    }

    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Processing...';

    fetch('{{route("agent.loan_payment.process")}}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({
            loan_id: selectedLoanId,
            amount: amount,
            date: document.getElementById('repaymentDate').value,
            payment_type_id: document.getElementById('repaymentPaymentType').value,
            notes: document.getElementById('repaymentNotes').value
        })
    })
    .then(r => r.json())
    .then(function(data) {
        document.getElementById('btnConfirmRepayment').disabled = false;
        document.getElementById('btnConfirmRepayment').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Repayment';

        if (data.success) {
            $('#repaymentModal').modal('hide');
            document.getElementById('successMessage').textContent = data.message;
            document.getElementById('successReference').textContent = data.reference;
            document.getElementById('successCommission').textContent = parseFloat(data.commission || 0).toFixed(2);
            document.getElementById('successFloat').textContent = parseFloat(data.new_float_balance).toFixed(2);
            document.getElementById('printReceiptBtn').href = '{{url("agent/receipt")}}/' + data.transaction_id;
            $('#successModal').modal('show');
        } else {
            showAlert('repaymentAlert', 'danger', data.message);
        }
    })
    .catch(function(err) {
        document.getElementById('btnConfirmRepayment').disabled = false;
        document.getElementById('btnConfirmRepayment').innerHTML = '<i class="fas fa-check mr-1"></i> Confirm Repayment';
        showAlert('repaymentAlert', 'danger', 'Transaction failed: ' + err.message);
    });
});

function showAlert(id, type, message) {
    var el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = message;
}
</script>
@endsection

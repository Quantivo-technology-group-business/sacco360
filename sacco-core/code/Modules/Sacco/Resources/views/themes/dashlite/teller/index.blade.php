@extends('core::layouts.master')
@section('title') Teller Desk @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1><i class="fas fa-cash-register mr-2"></i>Teller Desk</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Teller Desk</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content" id="tellerApp">

{{-- ── SESSION BAR ──────────────────────────────────────────────────────── --}}
<div class="card @if($session) card-success @else card-warning @endif card-outline mb-3">
    <div class="card-header">
        <h5 class="card-title mb-0">
            @if($session)
                <i class="fas fa-circle text-success mr-1"></i>
                Session Open &mdash; Till: <strong>{{$teller->till_number ?? 'Default'}}</strong>
                &nbsp;|&nbsp; Opened: {{$session->opened_at}}
                &nbsp;|&nbsp; Cash on Hand: <strong>{{number_format($teller->current_balance ?? 0, 2)}}</strong>
                &nbsp;|&nbsp; Today Deposits: <strong class="text-success">{{number_format($session->daily_deposit_total ?? 0, 2)}}</strong>
                &nbsp;|&nbsp; Today Withdrawals: <strong class="text-danger">{{number_format($session->daily_withdrawal_total ?? 0, 2)}}</strong>
            @else
                <i class="fas fa-circle text-warning mr-1"></i> No Active Session
            @endif
        </h5>
        <div class="card-tools">
            <a href="{{url('sacco/teller/ledger')}}" class="btn btn-sm btn-default mr-1">
                <i class="fas fa-list"></i> Daily Ledger
            </a>
            <button class="btn btn-sm btn-default" data-toggle="collapse" data-target="#sessionPanel">
                <i class="fas fa-cog"></i> Session Settings
            </button>
        </div>
    </div>
    <div class="card-body collapse @if(!$session) show @endif" id="sessionPanel">
        <div class="row">
            @if($session)
            {{-- Close session form --}}
            <div class="col-md-5">
                <form method="post" action="{{url('sacco/teller/close')}}">
                    @csrf
                    <input type="hidden" name="session_id" value="{{$session->id}}">
                    <h6><strong>Close Teller Session</strong></h6>
                    <div class="form-group">
                        <label>Physical Cash Count (Closing Balance)</label>
                        <input type="number" step="0.01" name="closing_balance" class="form-control" required placeholder="0.00">
                        <small class="text-muted">Count your till and enter the actual cash amount.</small>
                    </div>
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="Any discrepancies or handover notes..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger btn-block">
                        <i class="fas fa-lock mr-1"></i> Close Session & Balance Till
                    </button>
                </form>
            </div>
            @else
            {{-- Open session form --}}
            <div class="col-md-5">
                <form method="post" action="{{url('sacco/teller/open')}}">
                    @csrf
                    <h6><strong>Open Teller Session</strong></h6>
                    <div class="form-group">
                        <label>Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-control" required>
                            @foreach($branches as $b)
                                <option value="{{$b->id}}">{{$b->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Till Number</label>
                        <input name="till_number" class="form-control" placeholder="e.g. TILL-01">
                    </div>
                    <div class="form-group">
                        <label>Opening Cash Balance <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" required placeholder="0.00">
                        <small class="text-muted">Enter the physical cash you are starting with.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-block">
                        <i class="fas fa-unlock mr-1"></i> Open Teller Session
                    </button>
                </form>
            </div>
            @endif

            @if($teller)
            {{-- Teller limits --}}
            <div class="col-md-4 offset-md-1">
                <form method="post" action="{{url('sacco/teller/limits')}}">
                    @csrf
                    <input type="hidden" name="teller_id" value="{{$teller->id}}">
                    <h6><strong>Transaction Limits</strong></h6>
                    <div class="form-group">
                        <label>Max Single Transaction</label>
                        <input type="number" step="0.01" name="max_single_transaction"
                               value="{{$teller->max_single_transaction}}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Daily Withdrawal</label>
                        <input type="number" step="0.01" name="max_daily_withdrawal"
                               value="{{$teller->max_daily_withdrawal}}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Max Cash on Hand</label>
                        <input type="number" step="0.01" name="max_cash_on_hand"
                               value="{{$teller->max_cash_on_hand}}" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-secondary btn-sm">Update Limits</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>

@if(!$session)
<div class="alert alert-warning">
    <i class="fas fa-exclamation-triangle mr-1"></i>
    You must <strong>open a teller session</strong> before processing transactions.
</div>
@endif

{{-- ── CLIENT LOOKUP ────────────────────────────────────────────────────── --}}
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="fas fa-search mr-1"></i> Client Lookup</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="input-group">
                    <input type="text" id="clientSearch" class="form-control form-control-lg"
                           placeholder="Search by name, phone, ID number..." autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" id="btnSearch" type="button">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
                <div id="searchResults" class="mt-2"></div>
            </div>
            <div class="col-md-6">
                <div id="clientPanel" style="display:none">
                    <div class="card card-primary card-outline">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-user mr-1"></i>
                                <span id="clientName"></span>
                                <small class="text-muted ml-2" id="clientPhone"></small>
                            </h6>
                        </div>
                        <div class="card-body p-2">
                            {{-- Savings accounts --}}
                            <h6 class="mb-1 mt-1"><strong>Savings Accounts</strong></h6>
                            <div id="savingsList"></div>
                            {{-- Loans --}}
                            <h6 class="mb-1 mt-2"><strong>Active Loans</strong></h6>
                            <div id="loansList"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── DEPOSIT MODAL ────────────────────────────────────────────────────── --}}
<div class="modal fade" id="depositModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fas fa-arrow-down mr-1"></i> Receive Deposit</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Member Info Card --}}
                <div class="card card-outline card-primary mb-3" id="depositMemberCard">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <img id="depositMemberPhoto" src="" alt="Photo" class="img-circle mr-3" style="width:50px;height:50px;object-fit:cover;display:none;">
                            <div id="depositMemberPhotoPlaceholder" class="mr-3 bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-size:20px;"></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><strong id="depositMemberName"></strong></h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-card mr-1"></i><span id="depositMemberNumber"></span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-phone mr-1"></i><span id="depositMemberPhone"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info py-2 mb-3" id="depositAccountInfo"></div>
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="depositAmount" class="form-control form-control-lg" placeholder="0.00" required>
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
                                <option value="">Select...</option>
                                @foreach($payment_types as $pt)
                                    <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Receipt / Ref #</label>
                            <input type="text" id="depositReceipt" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" id="depositNotes" class="form-control" placeholder="Optional">
                        </div>
                    </div>
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

{{-- ── REPAYMENT MODAL ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="repaymentModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-hand-holding-usd mr-1"></i> Receive Loan Repayment</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Member Info Card --}}
                <div class="card card-outline card-primary mb-3" id="repaymentMemberCard">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <img id="repaymentMemberPhoto" src="" alt="Photo" class="img-circle mr-3" style="width:50px;height:50px;object-fit:cover;display:none;">
                            <div id="repaymentMemberPhotoPlaceholder" class="mr-3 bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-size:20px;"></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><strong id="repaymentMemberName"></strong></h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-card mr-1"></i><span id="repaymentMemberNumber"></span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-phone mr-1"></i><span id="repaymentMemberPhone"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info py-2 mb-3" id="repaymentAccountInfo"></div>
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="repaymentAmount" class="form-control form-control-lg" placeholder="0.00" required>
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
                                <option value="">Select...</option>
                                @foreach($payment_types as $pt)
                                    <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Receipt / Ref #</label>
                            <input type="text" id="repaymentReceipt" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" id="repaymentNotes" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div id="repaymentAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-lg" id="btnConfirmRepayment">
                    <i class="fas fa-check mr-1"></i> Confirm Repayment
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── WITHDRAWAL MODAL ──────────────────────────────────────────────────── --}}
<div class="modal fade" id="withdrawalModal" tabindex="-1">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-arrow-up mr-1"></i> Cash Withdrawal</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                {{-- Member Info Card --}}
                <div class="card card-outline card-primary mb-3" id="withdrawalMemberCard">
                    <div class="card-body py-2">
                        <div class="d-flex align-items-center">
                            <img id="withdrawalMemberPhoto" src="" alt="Photo" class="img-circle mr-3" style="width:50px;height:50px;object-fit:cover;display:none;">
                            <div id="withdrawalMemberPhotoPlaceholder" class="mr-3 bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:50px;height:50px;font-size:20px;"></div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0"><strong id="withdrawalMemberName"></strong></h6>
                                <small class="text-muted">
                                    <i class="fas fa-id-card mr-1"></i><span id="withdrawalMemberNumber"></span>
                                    <span class="mx-2">|</span>
                                    <i class="fas fa-phone mr-1"></i><span id="withdrawalMemberPhone"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info py-2 mb-3" id="withdrawalAccountInfo"></div>
                <div class="form-group">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" id="withdrawalAmount" class="form-control form-control-lg" placeholder="0.00" required>
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
                                <option value="">Select...</option>
                                @foreach($payment_types as $pt)
                                    <option value="{{$pt->id}}">{{$pt->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Receipt / Ref #</label>
                            <input type="text" id="withdrawalReceipt" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" id="withdrawalNotes" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
                <div id="withdrawalAlert" class="alert d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger btn-lg" id="btnConfirmWithdrawal">
                    <i class="fas fa-check mr-1"></i> Confirm Withdrawal
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ── SUCCESS TOAST ────────────────────────────────────────────────────── --}}
<div id="successToast" class="alert alert-success alert-dismissible fixed-bottom m-3"
     style="display:none;max-width:420px;right:0;left:auto;z-index:9999">
    <button type="button" class="close" onclick="document.getElementById('successToast').style.display='none'">&times;</button>
    <i class="fas fa-check-circle mr-1"></i>
    <span id="successMsg"></span>
    <br><a id="receiptLink" href="#" target="_blank" class="btn btn-sm btn-success mt-1">
        <i class="fas fa-print mr-1"></i> Print Receipt
    </a>
</div>

</section>
@endsection

@section('scripts')
<script>
var sessionOpen = {{ $session ? 'true' : 'false' }};
var activeClientId  = null;
var activeClientData = null;
var activeSavingsId = null;
var activeLoanId    = null;
var csrfToken       = '{{ csrf_token() }}';

// Helper to populate member info in modals
function populateMemberInfo(prefix) {
    if (!activeClientData) return;
    var c = activeClientData;
    document.getElementById(prefix + 'MemberName').textContent = c.name || '';
    document.getElementById(prefix + 'MemberNumber').textContent = c.member_number || '';
    document.getElementById(prefix + 'MemberPhone').textContent = c.phone || 'N/A';
    
    var photoEl = document.getElementById(prefix + 'MemberPhoto');
    var placeholderEl = document.getElementById(prefix + 'MemberPhotoPlaceholder');
    if (c.photo) {
        photoEl.src = c.photo;
        photoEl.style.display = 'block';
        placeholderEl.style.display = 'none';
    } else {
        photoEl.style.display = 'none';
        placeholderEl.style.display = 'flex';
        placeholderEl.textContent = (c.first_name ? c.first_name.charAt(0).toUpperCase() : '') + (c.last_name ? c.last_name.charAt(0).toUpperCase() : '');
    }
}

function tellerJsonFetch(url, options) {
    options = options || {};
    options.headers = Object.assign({
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'X-Requested-With': 'XMLHttpRequest'
    }, options.headers || {});
    return fetch(url, options).then(function (response) {
        return response.json().then(function (data) {
            if (!response.ok) {
                var message = (data && (data.message || data.error))
                    || (data && data.errors && Object.values(data.errors).flat().join(' '))
                    || ('Request failed (' + response.status + ')');
                throw new Error(message);
            }
            return data;
        });
    });
}

// ── Client Search ──────────────────────────────────────────────────────────
document.getElementById('btnSearch').addEventListener('click', doSearch);
document.getElementById('clientSearch').addEventListener('keydown', function(e){ if(e.key==='Enter') doSearch(); });

function doSearch() {
    var q = document.getElementById('clientSearch').value.trim();
    if (q.length < 2) return;
    fetch('{{ url("sacco/teller/member-lookup") }}?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(function(data) {
            var html = '';
            if (!data.length) { html = '<p class="text-muted">No clients found.</p>'; }
            data.forEach(function(m) {
                html += '<a href="#" class="list-group-item list-group-item-action client-pick" data-id="'+m.id+'">'
                      + '<strong>'+m.label+'</strong>'
                      + (m.member_number ? ' <small class="text-muted">#'+m.member_number+'</small>' : '')
                      + '</a>';
            });
            document.getElementById('searchResults').innerHTML = '<div class="list-group">'+html+'</div>';
        });
}

document.getElementById('searchResults').addEventListener('click', function(e) {
    var el = e.target.closest('.client-pick');
    if (!el) return;
    e.preventDefault();
    loadClientPanel(el.dataset.id);
    document.getElementById('searchResults').innerHTML = '';
});

function loadClientPanel(clientId) {
    activeClientId = clientId;
    tellerJsonFetch('{{ url("sacco/teller/client-panel") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({client_id: clientId})
    })
    .then(function(d) {
        if (!d.client) {
            throw new Error('Client details were not returned.');
        }
        activeClientData = d.client;
        document.getElementById('clientName').textContent  = d.client.name;
        document.getElementById('clientPhone').textContent = d.client.phone ?? '';
        document.getElementById('clientPanel').style.display = 'block';

        // Savings
        var savHtml = '';
        if (!d.savings.length) { savHtml = '<p class="text-muted small">No active savings accounts.</p>'; }
        d.savings.forEach(function(s) {
            savHtml += '<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">'
                     + '<div><strong>'+s.account_no+'</strong> <small class="text-muted">'+s.product+'</small>'
                     + '<br><span class="badge badge-success">Balance: '+parseFloat(s.balance).toFixed(2)+'</span></div>'
                     + '<div class="btn-group">'
                     + '<button class="btn btn-success btn-sm btn-deposit" data-id="'+s.id+'"'
                     + ' data-info="'+s.account_no+' — '+s.product+' (Bal: '+parseFloat(s.balance).toFixed(2)+')"'
                     + (sessionOpen ? '' : ' disabled title="Open a teller session first"')+'>'
                     + '<i class="fas fa-arrow-down mr-1"></i>Deposit</button>'
                     + '<button class="btn btn-danger btn-sm btn-withdraw" data-id="'+s.id+'"'
                     + ' data-info="'+s.account_no+' — '+s.product+' (Bal: '+parseFloat(s.balance).toFixed(2)+')"'
                     + ' data-balance="'+parseFloat(s.balance).toFixed(2)+'"'
                     + (sessionOpen ? '' : ' disabled title="Open a teller session first"')+'>'
                     + '<i class="fas fa-arrow-up mr-1"></i>Withdraw</button>'
                     + '</div></div>';
        });
        document.getElementById('savingsList').innerHTML = savHtml;

        // Loans
        var lnHtml = '';
        if (!d.loans.length) { lnHtml = '<p class="text-muted small">No active loans.</p>'; }
        d.loans.forEach(function(l) {
            lnHtml += '<div class="d-flex justify-content-between align-items-center border rounded p-2 mb-1">'
                    + '<div><strong>'+l.account_no+'</strong> <small class="text-muted">'+l.product+'</small>'
                    + '<br><span class="badge badge-warning">Outstanding: '+parseFloat(l.outstanding).toFixed(2)+'</span>'
                    + (l.next_payment ? ' <small class="text-muted">Due: '+l.next_payment+'</small>' : '')
                    + '</div>'
                    + '<button class="btn btn-primary btn-sm ml-2 btn-repay" data-id="'+l.id+'"'
                    + ' data-info="'+l.account_no+' — '+l.product+' (Outstanding: '+parseFloat(l.outstanding).toFixed(2)+')"'
                    + (sessionOpen ? '' : ' disabled title="Open a teller session first"')+'>'
                    + '<i class="fas fa-hand-holding-usd mr-1"></i>Repay</button></div>';
        });
        document.getElementById('loansList').innerHTML = lnHtml;

        // Wire buttons
        document.querySelectorAll('.btn-deposit').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeSavingsId = this.dataset.id;
                document.getElementById('depositAccountInfo').textContent = this.dataset.info;
                document.getElementById('depositAmount').value = '';
                document.getElementById('depositAlert').className = 'alert d-none';
                populateMemberInfo('deposit');
                $('#depositModal').modal('show');
            });
        });
        document.querySelectorAll('.btn-withdraw').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeSavingsId = this.dataset.id;
                document.getElementById('withdrawalAccountInfo').textContent = this.dataset.info;
                document.getElementById('withdrawalAmount').value = '';
                document.getElementById('withdrawalAlert').className = 'alert d-none';
                populateMemberInfo('withdrawal');
                $('#withdrawalModal').modal('show');
            });
        });
        document.querySelectorAll('.btn-repay').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeLoanId = this.dataset.id;
                document.getElementById('repaymentAccountInfo').textContent = this.dataset.info;
                document.getElementById('repaymentAmount').value = '';
                document.getElementById('repaymentAlert').className = 'alert d-none';
                populateMemberInfo('repayment');
                $('#repaymentModal').modal('show');
            });
        });
    })
    .catch(function (err) {
        document.getElementById('clientPanel').style.display = 'none';
        showToast(err.message || 'Failed to load client details.', null);
    });
}

// ── Confirm Deposit ────────────────────────────────────────────────────────
document.getElementById('btnConfirmDeposit').addEventListener('click', function() {
    var amount = document.getElementById('depositAmount').value;
    var date   = document.getElementById('depositDate').value;
    var ptype  = document.getElementById('depositPaymentType').value;
    if (!amount || !date || !ptype || !activeSavingsId) {
        showModalAlert('depositAlert','danger','Please fill in all required fields.');
        return;
    }
    this.disabled = true;
    tellerJsonFetch('{{ url("sacco/teller/deposit") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            savings_id: activeSavingsId, amount: amount, date: date,
            payment_type_id: ptype,
            receipt_no: document.getElementById('depositReceipt').value,
            notes:      document.getElementById('depositNotes').value,
        })
    })
    .then(function(d) {
        document.getElementById('btnConfirmDeposit').disabled = false;
        if (d.success) {
            $('#depositModal').modal('hide');
            showToast(d.message, d.receipt_url);
            if (activeClientId) {
                loadClientPanel(activeClientId);
            }
        } else {
            showModalAlert('depositAlert','danger', d.message ?? 'Transaction failed.');
        }
    })
    .catch(function(err){
        document.getElementById('btnConfirmDeposit').disabled = false;
        showModalAlert('depositAlert','danger', err.message || 'Transaction failed.');
    });
});

// ── Confirm Repayment ──────────────────────────────────────────────────────
document.getElementById('btnConfirmRepayment').addEventListener('click', function() {
    var amount = document.getElementById('repaymentAmount').value;
    var date   = document.getElementById('repaymentDate').value;
    var ptype  = document.getElementById('repaymentPaymentType').value;
    if (!amount || !date || !ptype || !activeLoanId) {
        showModalAlert('repaymentAlert','danger','Please fill in all required fields.');
        return;
    }
    this.disabled = true;
    tellerJsonFetch('{{ url("sacco/teller/repayment") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            loan_id: activeLoanId, amount: amount, date: date,
            payment_type_id: ptype,
            receipt_no: document.getElementById('repaymentReceipt').value,
            notes:      document.getElementById('repaymentNotes').value,
        })
    })
    .then(function(d) {
        document.getElementById('btnConfirmRepayment').disabled = false;
        if (d.success) {
            $('#repaymentModal').modal('hide');
            showToast(d.message, d.receipt_url);
            if (activeClientId) {
                loadClientPanel(activeClientId);
            }
        } else {
            showModalAlert('repaymentAlert','danger', d.message ?? 'Transaction failed.');
        }
    })
    .catch(function(err){
        document.getElementById('btnConfirmRepayment').disabled = false;
        showModalAlert('repaymentAlert','danger', err.message || 'Transaction failed.');
    });
});

// ── Confirm Withdrawal ────────────────────────────────────────────────────
document.getElementById('btnConfirmWithdrawal').addEventListener('click', function() {
    var amount = document.getElementById('withdrawalAmount').value;
    var date   = document.getElementById('withdrawalDate').value;
    var ptype  = document.getElementById('withdrawalPaymentType').value;
    if (!amount || !date || !ptype || !activeSavingsId) {
        showModalAlert('withdrawalAlert','danger','Please fill in all required fields.');
        return;
    }
    this.disabled = true;
    tellerJsonFetch('{{ url("sacco/teller/withdrawal") }}', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            savings_id: activeSavingsId, amount: amount, date: date,
            payment_type_id: ptype,
            receipt_no: document.getElementById('withdrawalReceipt').value,
            notes:      document.getElementById('withdrawalNotes').value,
        })
    })
    .then(function(d) {
        document.getElementById('btnConfirmWithdrawal').disabled = false;
        if (d.success) {
            $('#withdrawalModal').modal('hide');
            showToast(d.message, d.receipt_url);
            if (activeClientId) {
                loadClientPanel(activeClientId);
            }
        } else {
            showModalAlert('withdrawalAlert','danger', d.message ?? 'Transaction failed.');
        }
    })
    .catch(function(err){
        document.getElementById('btnConfirmWithdrawal').disabled = false;
        showModalAlert('withdrawalAlert','danger', err.message || 'Transaction failed.');
    });
});

function showModalAlert(id, type, msg) {
    var el = document.getElementById(id);
    el.className = 'alert alert-' + type;
    el.textContent = msg;
}

function showToast(msg, receiptUrl) {
    document.getElementById('successMsg').textContent = msg;
    var receiptLink = document.getElementById('receiptLink');
    if (receiptUrl) {
        receiptLink.href = receiptUrl;
        receiptLink.style.display = 'inline-block';
    } else {
        receiptLink.style.display = 'none';
    }
    var t = document.getElementById('successToast');
    t.style.display = 'block';
    setTimeout(function(){ t.style.display = 'none'; }, 8000);
}
</script>
@endsection

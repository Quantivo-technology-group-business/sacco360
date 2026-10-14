@extends('core::layouts.master')
@section('title') Agent Portal - Dashboard @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-user-tie mr-2"></i>Agent Portal</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Agent Portal</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    {{-- Agent Info Card --}}
    <div class="card card-primary card-outline mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h4 class="mb-1">
                        <i class="fas fa-user-circle mr-2 text-primary"></i>
                        Welcome, <strong>{{$agent->agent_name}}</strong>
                    </h4>
                    <p class="text-muted mb-0">
                        <span class="badge badge-primary">{{$agent->agent_code}}</span>
                        <span class="mx-2">|</span>
                        <i class="fas fa-map-marker-alt mr-1"></i>{{$agent->location ?? 'N/A'}}
                        <span class="mx-2">|</span>
                        <i class="fas fa-phone mr-1"></i>{{$agent->phone}}
                    </p>
                </div>
                <div class="col-md-6 text-md-right mt-3 mt-md-0">
                    <div class="d-inline-block text-center mx-3">
                        <small class="text-muted d-block">Float Balance</small>
                        <h4 class="mb-0 text-success">{{number_format($agent->float_balance, 2)}}</h4>
                    </div>
                    <div class="d-inline-block text-center mx-3">
                        <small class="text-muted d-block">Commission Balance</small>
                        <h4 class="mb-0 text-info">{{number_format($agent->commission_balance, 2)}}</h4>
                    </div>
                    @if($stats['daily_limit_remaining'] !== null)
                    <div class="d-inline-block text-center mx-3">
                        <small class="text-muted d-block">Daily Limit Remaining</small>
                        <h4 class="mb-0 text-warning">{{number_format($stats['daily_limit_remaining'], 2)}}</h4>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <a href="{{route('agent.deposit')}}" class="small-box bg-success">
                <div class="inner">
                    <h3><i class="fas fa-arrow-down"></i></h3>
                    <p>Receive Deposit</p>
                </div>
                <div class="icon"><i class="fas fa-piggy-bank"></i></div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{route('agent.withdrawal')}}" class="small-box bg-warning">
                <div class="inner">
                    <h3><i class="fas fa-arrow-up"></i></h3>
                    <p>Cash Withdrawal</p>
                </div>
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{route('agent.loan_payment')}}" class="small-box bg-info">
                <div class="inner">
                    <h3><i class="fas fa-hand-holding-usd"></i></h3>
                    <p>Loan Repayment</p>
                </div>
                <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            </a>
        </div>
        <div class="col-lg-3 col-6">
            <a href="{{route('agent.utility_payment')}}" class="small-box bg-primary">
                <div class="inner">
                    <h3><i class="fas fa-bolt"></i></h3>
                    <p>Pay Utilities</p>
                </div>
                <div class="icon"><i class="fas fa-lightbulb"></i></div>
            </a>
        </div>
    </div>

    {{-- Statistics Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-success elevation-1"><i class="fas fa-calendar-day"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Today's Transactions</span>
                    <span class="info-box-number">{{$stats['today']['transactions']}}</span>
                    <small class="text-muted">Volume: {{number_format($stats['today']['volume'], 2)}}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-info elevation-1"><i class="fas fa-coins"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Today's Commission</span>
                    <span class="info-box-number">{{number_format($stats['today']['commissions'], 2)}}</span>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-warning elevation-1"><i class="fas fa-calendar-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Monthly Transactions</span>
                    <span class="info-box-number">{{$stats['month']['count']}}</span>
                    <small class="text-muted">Commissions: {{number_format($stats['month']['commissions'], 2)}}</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="info-box">
                <span class="info-box-icon bg-primary elevation-1"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Monthly Volume</span>
                    <span class="info-box-number">{{number_format($stats['month']['deposits'] + $stats['month']['withdrawals'], 2)}}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        {{-- Commission Summary --}}
        <div class="col-md-4">
            <div class="card card-info card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-percentage mr-2"></i>Commission Summary (This Month)</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <td>Earned</td>
                            <td class="text-right text-success"><strong>+{{number_format($commissionSummary['earned'], 2)}}</strong></td>
                        </tr>
                        <tr>
                            <td>Withdrawn</td>
                            <td class="text-right text-danger"><strong>-{{number_format($commissionSummary['withdrawn'], 2)}}</strong></td>
                        </tr>
                        @if($commissionSummary['bonuses'] > 0)
                        <tr>
                            <td>Bonuses</td>
                            <td class="text-right text-info"><strong>+{{number_format($commissionSummary['bonuses'], 2)}}</strong></td>
                        </tr>
                        @endif
                        <tr class="border-top">
                            <td><strong>Current Balance</strong></td>
                            <td class="text-right"><strong class="text-primary">{{number_format($commissionSummary['balance'], 2)}}</strong></td>
                        </tr>
                    </table>
                    <a href="{{route('agent.commissions')}}" class="btn btn-info btn-block btn-sm mt-2">
                        <i class="fas fa-list mr-1"></i> View Commission Ledger
                    </a>
                </div>
            </div>
        </div>

        {{-- Quick Member Lookup --}}
        <div class="col-md-4">
            <div class="card card-success card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-search mr-2"></i>Quick Member Lookup</h5>
                </div>
                <div class="card-body">
                    <div class="input-group mb-2">
                        <input type="text" id="quickSearch" class="form-control" placeholder="Search by name, phone, ID...">
                        <div class="input-group-append">
                            <button class="btn btn-success" id="btnQuickSearch"><i class="fas fa-search"></i></button>
                        </div>
                    </div>
                    <div id="quickSearchResults" class="list-group" style="max-height:200px;overflow-y:auto;"></div>
                    <hr>
                    <a href="{{route('agent.register_member')}}" class="btn btn-outline-success btn-block btn-sm">
                        <i class="fas fa-user-plus mr-1"></i> Register New Member
                    </a>
                </div>
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Recent Transactions</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height:280px;overflow-y:auto;">
                        <table class="table table-sm table-striped mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $txn)
                                <tr>
                                    <td>{!! $txn->type_badge !!}</td>
                                    <td>{{number_format($txn->amount, 2)}}</td>
                                    <td><small>{{$txn->created_at->diffForHumans()}}</small></td>
                                </tr>
                                @empty
                                <tr><td colspan="3" class="text-center text-muted">No transactions yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{route('agent.transactions')}}" class="btn btn-primary btn-sm btn-block">
                        <i class="fas fa-list mr-1"></i> View All Transactions
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Additional Quick Links --}}
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-link mr-2"></i>Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{route('agent.transactions')}}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-list mr-1"></i> All Transactions
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{route('agent.float_history')}}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-wallet mr-1"></i> Float History
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{route('agent.member_registrations')}}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-users mr-1"></i> Member Registrations
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-2">
                            <a href="{{route('agent.profile')}}" class="btn btn-outline-secondary btn-block">
                                <i class="fas fa-user-cog mr-1"></i> Profile & Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
document.getElementById('btnQuickSearch').addEventListener('click', doQuickSearch);
document.getElementById('quickSearch').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') doQuickSearch();
});

function doQuickSearch() {
    var q = document.getElementById('quickSearch').value.trim();
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
                      + '<strong>'+m.name+'</strong><br>'
                      + '<small class="text-muted">'+m.member_number+' | '+m.phone+'</small>'
                      + '</a>';
            });
            document.getElementById('quickSearchResults').innerHTML = html;
        });
}

document.getElementById('quickSearchResults').addEventListener('click', function(e) {
    var el = e.target.closest('.member-select');
    if (!el) return;
    e.preventDefault();
    var clientId = el.dataset.id;
    window.location.href = '{{route("agent.deposit")}}?client_id=' + clientId;
});
</script>
@endsection

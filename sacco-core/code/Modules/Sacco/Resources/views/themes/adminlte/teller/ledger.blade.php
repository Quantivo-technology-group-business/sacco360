@extends('core::layouts.master')
@section('title') Teller Daily Ledger @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-list-alt mr-2"></i>Daily Teller Ledger</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('sacco/teller')}}">Teller Desk</a></li>
                    <li class="breadcrumb-item active">Daily Ledger</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">

    {{-- Date filter --}}
    <div class="card">
        <div class="card-body py-2">
            <form method="get" class="form-inline">
                <label class="mr-2">Date:</label>
                <input type="date" name="date" value="{{$date}}" class="form-control form-control-sm mr-2">
                <button type="submit" class="btn btn-sm btn-primary">View</button>
                <a href="{{url('sacco/teller/ledger?date='.date('Y-m-d'))}}" class="btn btn-sm btn-secondary ml-2">Today</a>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row">
        <div class="col-md-3">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-arrow-down"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Savings Deposits</span>
                    <span class="info-box-number">{{ number_format($totalDeposits, 2) }}</span>
                    <span class="progress-description">{{ $deposits->count() }} transactions</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-primary">
                <span class="info-box-icon"><i class="fas fa-hand-holding-usd"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Loan Repayments</span>
                    <span class="info-box-number">{{ number_format($totalRepayments, 2) }}</span>
                    <span class="progress-description">{{ $repayments->count() }} transactions</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-arrow-up"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Withdrawals Paid</span>
                    <span class="info-box-number">{{ number_format($totalWithdrawals, 2) }}</span>
                    <span class="progress-description">{{ $withdrawals->count() }} transactions</span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="info-box {{ $netCash >= 0 ? 'bg-teal' : 'bg-danger' }}">
                <span class="info-box-icon"><i class="fas fa-cash-register"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Net Cash Received</span>
                    <span class="info-box-number">{{ number_format($netCash, 2) }}</span>
                    <span class="progress-description">Deposits + Repayments − Withdrawals</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sessions for the day --}}
    @if($sessions->count())
    <div class="card">
        <div class="card-header"><h5 class="card-title mb-0">Session Summary for {{$date}}</h5></div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered mb-0">
                <thead><tr>
                    <th>Opened</th><th>Closed</th><th>Opening Balance</th>
                    <th>Expected</th><th>Actual Close</th><th>Variance</th><th>Status</th>
                </tr></thead>
                <tbody>
                @foreach($sessions as $s)
                <tr>
                    <td>{{$s->opened_at}}</td>
                    <td>{{$s->closed_at ?? '—'}}</td>
                    <td class="text-right">{{number_format($s->opening_balance,2)}}</td>
                    <td class="text-right">{{number_format($s->expected_balance ?? 0,2)}}</td>
                    <td class="text-right">{{$s->closing_balance ? number_format($s->closing_balance,2) : '—'}}</td>
                    <td class="text-right @if(($s->variance??0) != 0) text-danger @else text-success @endif">
                        {{ $s->variance !== null ? number_format($s->variance,2) : '—' }}
                    </td>
                    <td><span class="badge badge-{{ $s->status=='balanced'?'success':($s->status=='open'?'warning':'secondary') }}">{{ucfirst($s->status)}}</span></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Savings Deposits --}}
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0"><i class="fas fa-arrow-down mr-1"></i> Savings Deposits — {{$date}}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead><tr>
                    <th>#</th><th>Time</th><th>Member</th><th>Account</th>
                    <th>Product</th><th class="text-right">Amount</th><th>Receipt</th>
                </tr></thead>
                <tbody>
                @forelse($deposits as $txn)
                <tr>
                    <td><a href="{{url('sacco/teller/receipt/savings/'.$txn->id)}}" target="_blank">TLR-SAV-{{$txn->id}}</a></td>
                    <td>{{$txn->created_at}}</td>
                    <td>{{optional(optional($txn->savings)->client)->first_name}} {{optional(optional($txn->savings)->client)->last_name}}</td>
                    <td>{{optional($txn->savings)->account_number ?? 'SAV-'.$txn->savings_id}}</td>
                    <td>{{optional(optional($txn->savings)->savings_product)->name}}</td>
                    <td class="text-right text-success font-weight-bold">{{number_format($txn->amount,2)}}</td>
                    <td>
                        <a href="{{url('sacco/teller/receipt/savings/'.$txn->id)}}" target="_blank" class="btn btn-xs btn-success">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">No deposits today.</td></tr>
                @endforelse
                </tbody>
                @if($deposits->count())
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="5" class="text-right">Total Deposits</td>
                        <td class="text-right text-success">{{number_format($totalDeposits,2)}}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Loan Repayments --}}
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-1"></i> Loan Repayments — {{$date}}</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-bordered table-hover mb-0">
                <thead><tr>
                    <th>#</th><th>Time</th><th>Member</th><th>Loan Account</th>
                    <th>Product</th><th class="text-right">Amount</th><th>Receipt</th>
                </tr></thead>
                <tbody>
                @forelse($repayments as $txn)
                <tr>
                    <td><a href="{{url('sacco/teller/receipt/loan/'.$txn->id)}}" target="_blank">TLR-LN-{{$txn->id}}</a></td>
                    <td>{{$txn->created_at}}</td>
                    <td>{{optional(optional($txn->loan)->client)->first_name}} {{optional(optional($txn->loan)->client)->last_name}}</td>
                    <td>LN-{{$txn->loan_id}}</td>
                    <td>{{optional(optional($txn->loan)->loan_product)->name}}</td>
                    <td class="text-right text-primary font-weight-bold">{{number_format($txn->amount,2)}}</td>
                    <td>
                        <a href="{{url('sacco/teller/receipt/loan/'.$txn->id)}}" target="_blank" class="btn btn-xs btn-primary">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">No repayments today.</td></tr>
                @endforelse
                </tbody>
                @if($repayments->count())
                <tfoot>
                    <tr class="font-weight-bold bg-light">
                        <td colspan="5" class="text-right">Total Repayments</td>
                        <td class="text-right text-primary">{{number_format($totalRepayments,2)}}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

</section>
@endsection

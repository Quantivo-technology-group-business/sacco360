@extends('core::layouts.master')
@section('title') Agent Portal - Transactions @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-list mr-2"></i>My Transactions</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Transactions</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="card">
        <div class="card-header">
            <form method="GET" class="row">
                <div class="col-md-2">
                    <select name="type" class="form-control form-control-sm">
                        <option value="">All Types</option>
                        <option value="deposit" {{request('type') == 'deposit' ? 'selected' : ''}}>Deposit</option>
                        <option value="withdrawal" {{request('type') == 'withdrawal' ? 'selected' : ''}}>Withdrawal</option>
                        <option value="loan_repayment" {{request('type') == 'loan_repayment' ? 'selected' : ''}}>Loan Repayment</option>
                        <option value="utility" {{request('type') == 'utility' ? 'selected' : ''}}>Utility</option>
                        <option value="registration" {{request('type') == 'registration' ? 'selected' : ''}}>Registration</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control form-control-sm">
                        <option value="">All Status</option>
                        <option value="completed" {{request('status') == 'completed' ? 'selected' : ''}}>Completed</option>
                        <option value="pending" {{request('status') == 'pending' ? 'selected' : ''}}>Pending</option>
                        <option value="failed" {{request('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                        <option value="reversed" {{request('status') == 'reversed' ? 'selected' : ''}}>Reversed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="date" name="from" class="form-control form-control-sm" placeholder="From" value="{{request('from')}}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="to" class="form-control form-control-sm" placeholder="To" value="{{request('to')}}">
                </div>
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search reference, client..." value="{{request('search')}}">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date/Time</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Client</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Commission</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                        <tr>
                            <td>
                                <small>{{$txn->created_at->format('d M Y')}}</small><br>
                                <small class="text-muted">{{$txn->created_at->format('H:i')}}</small>
                            </td>
                            <td><code>{{$txn->reference}}</code></td>
                            <td>{!! $txn->type_badge !!}</td>
                            <td>
                                @if($txn->client)
                                {{$txn->client->first_name}} {{$txn->client->last_name}}
                                @else
                                <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-right"><strong>{{number_format($txn->amount, 2)}}</strong></td>
                            <td class="text-right text-success">{{number_format($txn->commission, 2)}}</td>
                            <td>{!! $txn->status_badge !!}</td>
                            <td>
                                <a href="{{route('agent.receipt', $txn->id)}}" class="btn btn-sm btn-outline-secondary" target="_blank">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-2"></i><br>
                                No transactions found
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">
            {{$transactions->withQueryString()->links()}}
        </div>
        @endif
    </div>
</section>

@endsection

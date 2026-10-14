@extends('core::layouts.master')
@section('title') Agent Portal - Commissions @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-percentage mr-2"></i>My Commissions</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Commissions</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{number_format($summary['earned'], 2)}}</h3>
                    <p>Earned This Month</p>
                </div>
                <div class="icon"><i class="fas fa-plus-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{number_format($summary['withdrawn'], 2)}}</h3>
                    <p>Withdrawn This Month</p>
                </div>
                <div class="icon"><i class="fas fa-minus-circle"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{number_format($summary['balance'], 2)}}</h3>
                    <p>Current Balance</p>
                </div>
                <div class="icon"><i class="fas fa-wallet"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{number_format($agent->total_commissions_earned, 2)}}</h3>
                    <p>Total Lifetime Earnings</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-list mr-2"></i>Commission Ledger</h5>
                    <div class="card-tools">
                        <form method="GET" class="form-inline">
                            <select name="type" class="form-control form-control-sm mr-1">
                                <option value="">All Types</option>
                                <option value="earned" {{request('type') == 'earned' ? 'selected' : ''}}>Earned</option>
                                <option value="withdrawn" {{request('type') == 'withdrawn' ? 'selected' : ''}}>Withdrawn</option>
                                <option value="bonus" {{request('type') == 'bonus' ? 'selected' : ''}}>Bonus</option>
                            </select>
                            <input type="date" name="from" class="form-control form-control-sm mr-1" value="{{request('from')}}">
                            <input type="date" name="to" class="form-control form-control-sm mr-1" value="{{request('to')}}">
                            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter"></i></button>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th class="text-right">Amount</th>
                                    <th class="text-right">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($ledger as $entry)
                                <tr>
                                    <td><small>{{$entry->created_at->format('d M Y H:i')}}</small></td>
                                    <td>{!! $entry->type_badge !!}</td>
                                    <td><small>{{$entry->description}}</small></td>
                                    <td class="text-right">
                                        @if($entry->type == 'earned' || $entry->type == 'bonus')
                                        <span class="text-success">+{{number_format($entry->amount, 2)}}</span>
                                        @else
                                        <span class="text-danger">-{{number_format($entry->amount, 2)}}</span>
                                        @endif
                                    </td>
                                    <td class="text-right"><strong>{{number_format($entry->balance_after, 2)}}</strong></td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No commission entries found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($ledger->hasPages())
                <div class="card-footer">
                    {{$ledger->withQueryString()->links()}}
                </div>
                @endif
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-success">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-hand-holding-usd mr-2"></i>Withdraw Commission</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{route('agent.commissions.withdraw')}}">
                        @csrf
                        <div class="form-group">
                            <label>Available Balance</label>
                            <h3 class="text-success mb-3">{{number_format($summary['balance'], 2)}}</h3>
                        </div>
                        <div class="form-group">
                            <label>Withdrawal Amount <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                <input type="number" step="0.01" min="1" max="{{$summary['balance']}}" name="amount" class="form-control" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Withdrawal Method</label>
                            <select name="method" class="form-control">
                                <option value="cash">Cash</option>
                                <option value="transfer">Bank Transfer</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success btn-block" @if($summary['balance'] <= 0) disabled @endif>
                            <i class="fas fa-hand-holding-usd mr-1"></i> Withdraw
                        </button>
                    </form>
                </div>
            </div>

            <div class="card card-info card-outline">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-info-circle mr-2"></i>Commission Rates</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Transaction</th><th>Rate</th></tr>
                        </thead>
                        <tbody>
                            @foreach($rates as $type => $typeRates)
                            <tr>
                                <td colspan="2" class="bg-light"><strong>{{ucfirst(str_replace('_', ' ', $type))}}</strong></td>
                            </tr>
                            @foreach($typeRates as $rate)
                            <tr>
                                <td><small>{{$rate['amount_range'] ?? 'All amounts'}}</small></td>
                                <td><small>{{$rate['formatted_rate'] ?? ($rate['rate_value'] . ($rate['rate_type'] == 'percentage' ? '%' : ' flat'))}}</small></td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

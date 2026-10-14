@extends('core::layouts.master')
@section('title') Agency Transactions @endsection

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-exchange-alt mr-2"></i>Agency Transactions</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('sacco/agency')}}">Agency Banking</a></li>
                    <li class="breadcrumb-item active">Transactions</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle mr-2"></i>{{session('success')}}
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle mr-2"></i>{{session('error')}}
        </div>
    @endif

    {{-- Summary Cards --}}
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{$summary['total_count'] ?? 0}}</h3>
                    <p>Total Transactions</p>
                </div>
                <div class="icon"><i class="fas fa-list"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{number_format($summary['deposits']['amount'] ?? 0, 0)}}</h3>
                    <p>Deposits ({{$summary['deposits']['count'] ?? 0}})</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-down"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{number_format($summary['withdrawals']['amount'] ?? 0, 0)}}</h3>
                    <p>Withdrawals ({{$summary['withdrawals']['count'] ?? 0}})</p>
                </div>
                <div class="icon"><i class="fas fa-arrow-up"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{number_format($summary['total_commissions'] ?? 0, 0)}}</h3>
                    <p>Total Commissions</p>
                </div>
                <div class="icon"><i class="fas fa-percentage"></i></div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-filter mr-2"></i>Filters</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="get" action="{{url('sacco/agency/transactions')}}" class="row align-items-end">
                <div class="col-md-2">
                    <label>Agent</label>
                    <select name="agent_id" class="form-control select2">
                        <option value="">All Agents</option>
                        @foreach($agents as $agent)
                        <option value="{{$agent->id}}" {{request('agent_id') == $agent->id ? 'selected' : ''}}>
                            {{$agent->agent_code}} - {{$agent->agent_name}}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="deposit" {{request('type') == 'deposit' ? 'selected' : ''}}>Deposit</option>
                        <option value="withdrawal" {{request('type') == 'withdrawal' ? 'selected' : ''}}>Withdrawal</option>
                        <option value="float_topup" {{request('type') == 'float_topup' ? 'selected' : ''}}>Float Top-up</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="completed" {{request('status') == 'completed' ? 'selected' : ''}}>Completed</option>
                        <option value="pending" {{request('status') == 'pending' ? 'selected' : ''}}>Pending</option>
                        <option value="failed" {{request('status') == 'failed' ? 'selected' : ''}}>Failed</option>
                        <option value="reversed" {{request('status') == 'reversed' ? 'selected' : ''}}>Reversed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label>From Date</label>
                    <input type="date" name="from" class="form-control" value="{{request('from')}}">
                </div>
                <div class="col-md-2">
                    <label>To Date</label>
                    <input type="date" name="to" class="form-control" value="{{request('to')}}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search mr-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-6">
                    <form method="get" action="{{url('sacco/agency/transactions')}}" class="form-inline">
                        <input type="hidden" name="agent_id" value="{{request('agent_id')}}">
                        <input type="hidden" name="type" value="{{request('type')}}">
                        <input type="hidden" name="status" value="{{request('status')}}">
                        <input type="hidden" name="from" value="{{request('from')}}">
                        <input type="hidden" name="to" value="{{request('to')}}">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search reference, agent, client..."
                                   value="{{request('search')}}">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{{url('sacco/agency/transactions')}}" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times mr-1"></i> Clear Filters
                    </a>
                    <a href="{{url('sacco/agency/transactions/export')}}?{{http_build_query(request()->all())}}" class="btn btn-success btn-sm">
                        <i class="fas fa-download mr-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="card">
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-striped">
                <thead class="thead-light">
                    <tr>
                        <th>Reference</th>
                        <th>Agent</th>
                        <th>Client</th>
                        <th>Account</th>
                        <th>Type</th>
                        <th class="text-right">Amount</th>
                        <th class="text-right">Commission</th>
                        <th class="text-center">Status</th>
                        <th>Date/Time</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $txn)
                    <tr class="{{$txn->status === 'reversed' ? 'table-secondary' : ''}}">
                        <td>
                            <code class="{{$txn->status === 'reversed' ? 'text-decoration-line-through' : ''}}">
                                {{$txn->reference}}
                            </code>
                        </td>
                        <td>
                            @if($txn->agent)
                            <a href="{{url('sacco/agency/'.$txn->agent->id.'/show')}}">
                                {{$txn->agent->agent_code}}
                            </a>
                            <br><small class="text-muted">{{$txn->agent->agent_name}}</small>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($txn->client)
                            <a href="{{url('client/'.$txn->client_id.'/show')}}">
                                {{$txn->client->first_name}} {{$txn->client->last_name}}
                            </a>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            @if($txn->savings)
                            <a href="{{url('savings/'.$txn->savings_id.'/show')}}">
                                #{{$txn->savings_id}}
                            </a>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>{!! $txn->type_badge !!}</td>
                        <td class="text-right font-weight-bold">{{number_format($txn->amount, 2)}}</td>
                        <td class="text-right">{{number_format($txn->commission, 2)}}</td>
                        <td class="text-center">{!! $txn->status_badge !!}</td>
                        <td>
                            <small>{{$txn->created_at->format('d M Y')}}</small><br>
                            <small class="text-muted">{{$txn->created_at->format('H:i:s')}}</small>
                        </td>
                        <td class="text-center">
                            @if($txn->isReversible())
                            <button type="button" class="btn btn-danger btn-xs" title="Reverse"
                                    data-toggle="modal" data-target="#reverseModal{{$txn->id}}">
                                <i class="fas fa-undo"></i>
                            </button>

                            {{-- Reverse Modal --}}
                            <div class="modal fade" id="reverseModal{{$txn->id}}" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="post" action="{{url('sacco/agency/transaction/'.$txn->id.'/reverse')}}">
                                            @csrf
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Reverse Transaction</h5>
                                                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
                                            </div>
                                            <div class="modal-body text-left">
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                                    This action will reverse the transaction and adjust the agent's float balance.
                                                </div>
                                                <table class="table table-sm">
                                                    <tr><th>Reference:</th><td>{{$txn->reference}}</td></tr>
                                                    <tr><th>Amount:</th><td>KES {{number_format($txn->amount, 2)}}</td></tr>
                                                    <tr><th>Type:</th><td>{{ucfirst($txn->transaction_type)}}</td></tr>
                                                </table>
                                                <div class="form-group">
                                                    <label>Reason for Reversal <span class="text-danger">*</span></label>
                                                    <textarea name="reason" class="form-control" rows="3" required
                                                              placeholder="Enter the reason for reversing this transaction..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger">Reverse Transaction</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            @elseif($txn->status === 'reversed')
                            <span class="text-muted" title="Reversed: {{$txn->reversal_reason}}">
                                <i class="fas fa-ban"></i>
                            </span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @if($txn->status === 'reversed' && $txn->reversal_reason)
                    <tr class="bg-light">
                        <td colspan="10" class="py-1 px-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle mr-1"></i>
                                <strong>Reversal Reason:</strong> {{$txn->reversal_reason}}
                                @if($txn->reversed_at)
                                | <strong>Reversed:</strong> {{$txn->reversed_at->format('d M Y H:i')}}
                                @endif
                            </small>
                        </td>
                    </tr>
                    @endif
                    @if($txn->notes)
                    <tr class="bg-light">
                        <td colspan="10" class="py-1 px-3">
                            <small class="text-muted">
                                <i class="fas fa-sticky-note mr-1"></i>
                                <strong>Notes:</strong> {{$txn->notes}}
                            </small>
                        </td>
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-0">No transactions found</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer clearfix">
            <div class="float-left">
                Showing {{$transactions->firstItem()}} to {{$transactions->lastItem()}} of {{$transactions->total()}} transactions
            </div>
            <div class="float-right">
                {{$transactions->withQueryString()->links()}}
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});
</script>
@endpush

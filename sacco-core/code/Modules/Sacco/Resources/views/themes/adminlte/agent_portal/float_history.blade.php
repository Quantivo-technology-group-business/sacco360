@extends('core::layouts.master')
@section('title') Agent Portal - Float History @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-wallet mr-2"></i>Float History</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Float History</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box bg-success">
                <span class="info-box-icon"><i class="fas fa-wallet"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Current Float Balance</span>
                    <span class="info-box-number">{{number_format($agent->float_balance, 2)}}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Float Limit</span>
                    <span class="info-box-number">{{number_format($agent->float_limit, 2)}}</span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-warning">
                <span class="info-box-icon"><i class="fas fa-percentage"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Float Utilization</span>
                    <span class="info-box-number">{{$agent->float_utilization}}%</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-history mr-2"></i>Float Movement History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date/Time</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Float Before</th>
                            <th class="text-right">Float After</th>
                            <th>Movement</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $txn)
                        @php
                            $movement = $txn->float_after - $txn->float_before;
                        @endphp
                        <tr>
                            <td><small>{{$txn->created_at->format('d M Y H:i')}}</small></td>
                            <td><code>{{$txn->reference}}</code></td>
                            <td>{!! $txn->type_badge !!}</td>
                            <td class="text-right">{{number_format($txn->amount, 2)}}</td>
                            <td class="text-right">{{number_format($txn->float_before, 2)}}</td>
                            <td class="text-right"><strong>{{number_format($txn->float_after, 2)}}</strong></td>
                            <td>
                                @if($movement > 0)
                                <span class="text-success"><i class="fas fa-arrow-up"></i> +{{number_format($movement, 2)}}</span>
                                @elseif($movement < 0)
                                <span class="text-danger"><i class="fas fa-arrow-down"></i> {{number_format($movement, 2)}}</span>
                                @else
                                <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No float movements recorded</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">
            {{$transactions->links()}}
        </div>
        @endif
    </div>
</section>

@endsection

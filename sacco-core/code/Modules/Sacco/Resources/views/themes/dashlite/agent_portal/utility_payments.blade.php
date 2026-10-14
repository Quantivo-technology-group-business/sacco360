@extends('core::layouts.master')
@section('title') Agent Portal - Utility Payments @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-bolt mr-2"></i>My Utility Payments</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{route('agent.dashboard')}}">Agent Portal</a></li>
                    <li class="breadcrumb-item active">Utility Payments</li>
                </ol>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="row mb-3">
        <div class="col-12">
            <a href="{{route('agent.utility_payment')}}" class="btn btn-primary">
                <i class="fas fa-bolt mr-1"></i> New Utility Payment
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="thead-dark">
                        <tr>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Provider</th>
                            <th>Account #</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Fee</th>
                            <th class="text-right">Commission</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td><small>{{$payment->created_at->format('d M Y H:i')}}</small></td>
                            <td><code>{{$payment->reference}}</code></td>
                            <td>{{$payment->provider->name ?? 'N/A'}}</td>
                            <td>{{$payment->account_number}}</td>
                            <td class="text-right">{{number_format($payment->amount, 2)}}</td>
                            <td class="text-right">{{number_format($payment->fee, 2)}}</td>
                            <td class="text-right text-success">{{number_format($payment->commission, 2)}}</td>
                            <td>{!! $payment->status_badge !!}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="fas fa-bolt fa-3x mb-2"></i><br>
                                No utility payments yet
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="card-footer">
            {{$payments->links()}}
        </div>
        @endif
    </div>
</section>

@endsection

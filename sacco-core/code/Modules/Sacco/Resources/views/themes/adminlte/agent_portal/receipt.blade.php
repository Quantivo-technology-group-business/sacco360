@extends('core::layouts.master')
@section('title') Transaction Receipt @endsection
@section('content')

<section class="content">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card" id="receiptCard">
                <div class="card-header bg-dark text-white text-center">
                    <h4 class="mb-0">Transaction Receipt</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5><strong>{{config('app.name', 'Loan Manager')}}</strong></h5>
                        <p class="text-muted mb-0">Agent Banking Receipt</p>
                    </div>

                    <hr>

                    <div class="row mb-2">
                        <div class="col-6"><strong>Reference:</strong></div>
                        <div class="col-6 text-right"><code>{{$transaction->reference}}</code></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Date & Time:</strong></div>
                        <div class="col-6 text-right">{{$transaction->created_at->format('d M Y H:i')}}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Transaction Type:</strong></div>
                        <div class="col-6 text-right">{!! $transaction->type_badge !!}</div>
                    </div>

                    <hr>

                    <div class="row mb-2">
                        <div class="col-6"><strong>Agent:</strong></div>
                        <div class="col-6 text-right">{{$agent->agent_name}}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Agent Code:</strong></div>
                        <div class="col-6 text-right">{{$agent->agent_code}}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Location:</strong></div>
                        <div class="col-6 text-right">{{$agent->location ?? 'N/A'}}</div>
                    </div>

                    <hr>

                    @if($transaction->client)
                    <div class="row mb-2">
                        <div class="col-6"><strong>Customer:</strong></div>
                        <div class="col-6 text-right">{{$transaction->client->first_name}} {{$transaction->client->last_name}}</div>
                    </div>
                    @endif

                    @if($transaction->savings)
                    <div class="row mb-2">
                        <div class="col-6"><strong>Account:</strong></div>
                        <div class="col-6 text-right">{{$transaction->savings->account_number ?? 'SAV-'.$transaction->savings_id}}</div>
                    </div>
                    @endif

                    <hr>

                    <div class="row mb-2">
                        <div class="col-6"><h5><strong>Amount:</strong></h5></div>
                        <div class="col-6 text-right"><h5><strong>{{number_format($transaction->amount, 2)}}</strong></h5></div>
                    </div>

                    <hr>

                    <div class="row mb-2">
                        <div class="col-6"><strong>Status:</strong></div>
                        <div class="col-6 text-right">{!! $transaction->status_badge !!}</div>
                    </div>

                    @if($transaction->notes)
                    <div class="row mb-2">
                        <div class="col-6"><strong>Notes:</strong></div>
                        <div class="col-6 text-right"><small>{{$transaction->notes}}</small></div>
                    </div>
                    @endif

                    <hr>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-0"><small>Thank you for using our services</small></p>
                        <p class="text-muted"><small>Keep this receipt for your records</small></p>
                    </div>
                </div>
                <div class="card-footer text-center d-print-none">
                    <button onclick="window.print()" class="btn btn-primary">
                        <i class="fas fa-print mr-1"></i> Print Receipt
                    </button>
                    <a href="{{route('agent.dashboard')}}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('styles')
<style>
@media print {
    body * { visibility: hidden; }
    #receiptCard, #receiptCard * { visibility: visible; }
    #receiptCard { position: absolute; left: 0; top: 0; width: 100%; }
    .d-print-none { display: none !important; }
}
</style>
@endsection

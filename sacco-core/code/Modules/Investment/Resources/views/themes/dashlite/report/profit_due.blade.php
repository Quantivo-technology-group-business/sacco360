@extends('core::layouts.master')
@section('title') Profit Due Report @endsection
@section('content')
<section class="content-header"><div class="container-fluid">
    <div class="row mb-2">
        <div class="col-sm-6"><h1>Profit Due Report</h1></div>
        <div class="col-sm-6"><ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="{{url('investment/report')}}">Investment Reports</a></li>
            <li class="breadcrumb-item active">Profit Due</li>
        </ol></div>
    </div>
</div></section>
<section class="content">
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Accrued But Unpaid Profit</h3>
        <div class="card-tools">
            <span class="badge badge-warning badge-lg p-2">Total Due: {{number_format($totalDue,2)}}</span>
        </div>
    </div>
    <div class="card-body p-0">
        <table class="table table-bordered table-hover table-sm mb-0">
            <thead><tr>
                <th>Account #</th><th>Member</th><th>Plan</th><th class="text-right">Principal</th>
                <th class="text-right">Accrued</th><th class="text-right">Paid</th>
                <th class="text-right font-weight-bold">Due</th><th>Next Posting</th><th></th>
            </tr></thead>
            <tbody>
            @forelse($data as $inv)
            <tr>
                <td><a href="{{url('investment/'.$inv->id.'/show')}}">{{$inv->account_number}}</a></td>
                <td>{{optional($inv->client)->first_name}} {{optional($inv->client)->last_name}}</td>
                <td>{{optional($inv->plan)->name}}</td>
                <td class="text-right">{{number_format($inv->principal_amount,2)}}</td>
                <td class="text-right text-success">{{number_format($inv->total_profit_accrued,2)}}</td>
                <td class="text-right">{{number_format($inv->total_profit_paid,2)}}</td>
                <td class="text-right text-warning font-weight-bold">{{number_format($inv->profit_due_amount,2)}}</td>
                <td>{{$inv->next_profit_posting_date?->format('d M Y') ?? '—'}}</td>
                <td><a href="{{url('investment/'.$inv->id.'/show')}}" class="btn btn-xs btn-warning">Pay</a></td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted py-3">No unpaid profits. All caught up!</td></tr>
            @endforelse
            </tbody>
            @if($data->count())
            <tfoot>
                <tr class="table-warning font-weight-bold">
                    <td colspan="6" class="text-right">Total Profit Due</td>
                    <td class="text-right">{{number_format($totalDue,2)}}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
</section>
@endsection

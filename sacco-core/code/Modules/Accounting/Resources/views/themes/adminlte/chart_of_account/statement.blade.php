@extends('core::layouts.master')
@section('title') Account Statement — {{$account->name}} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Account Statement
                    <a href="#" onclick="window.history.back()" class="btn btn-outline-light bg-white d-none d-sm-inline-flex"><span>Back</span></a>
                </h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('accounting/chart_of_account')}}">Chart of Accounts</a></li>
                    <li class="breadcrumb-item"><a href="{{url('accounting/chart_of_account/'.$account->id.'/show')}}">{{$account->name}}</a></li>
                    <li class="breadcrumb-item active">Statement</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                [{{$account->gl_code}}] {{$account->name}} &mdash; {{ucfirst($account->account_type)}}
                @if(!empty($start_date)) &mdash; <b>{{$start_date}} to {{$end_date}}</b> @endif
            </h3>
            @if(!empty($start_date))
            <div class="card-tools">
                <div class="dropdown">
                    <a href="#" class="btn btn-info btn-sm dropdown-toggle" data-toggle="dropdown">Download</a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <a href="{{url('accounting/chart_of_account/'.$account->id.'/statement?download=1&type=pdf&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">PDF</a>
                        <a href="{{url('accounting/chart_of_account/'.$account->id.'/statement?download=1&type=excel_2007&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">Excel (.xlsx)</a>
                        <a href="{{url('accounting/chart_of_account/'.$account->id.'/statement?download=1&type=csv&start_date='.$start_date.'&end_date='.$end_date.'&branch_id='.$branch_id)}}" class="dropdown-item">CSV</a>
                    </div>
                </div>
            </div>
            @endif
        </div>
        <div class="card-body">
            <form method="get" action="{{Request::url()}}">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>
                            <select class="form-control select2" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{$b->id}}" @if($branch_id==$b->id) selected @endif>{{$b->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Start Date <span class="text-danger">*</span></label>
                            <flat-pickr value="{{$start_date}}" class="form-control" name="start_date" required></flat-pickr>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>End Date <span class="text-danger">*</span></label>
                            <flat-pickr value="{{$end_date}}" class="form-control" name="end_date" required></flat-pickr>
                        </div>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary mr-2">Generate</button>
                        <a href="{{Request::url()}}" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($start_date))
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr style="background:#e9ecef">
                        <th>Date</th>
                        <th>Transaction #</th>
                        <th>Type</th>
                        <th>Reference / Notes</th>
                        <th class="text-right">Debit</th>
                        <th class="text-right">Credit</th>
                        <th class="text-right">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- Opening balance row --}}
                    <tr style="background:#fff9c4;font-weight:bold">
                        <td>{{$start_date}}</td>
                        <td colspan="3"><em>Opening Balance</em></td>
                        <td class="text-right"></td>
                        <td class="text-right"></td>
                        <td class="text-right">{{number_format($openingBalance,2)}}</td>
                    </tr>
                    @forelse($lines as $line)
                    <tr>
                        <td>{{$line->date}}</td>
                        <td>
                            <a href="{{url('accounting/journal_entry/'.$line->transaction_number.'/show')}}">
                                {{$line->transaction_number}}
                            </a>
                        </td>
                        <td>{{$line->manual_entry ? 'Manual' : ucfirst(str_replace('_',' ',$line->transaction_type))}}</td>
                        <td>{{$line->reference}} {{$line->notes}}</td>
                        <td class="text-right">@if(!empty($line->debit)) {{number_format($line->debit,2)}} @endif</td>
                        <td class="text-right">@if(!empty($line->credit)) {{number_format($line->credit,2)}} @endif</td>
                        <td class="text-right @if($line->running_balance < 0) text-danger @endif">
                            {{number_format($line->running_balance,2)}}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">No transactions in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr style="background:#f4f4f4;font-weight:bold">
                        <td colspan="4" class="text-right">Period Totals</td>
                        <td class="text-right">{{number_format($periodDr,2)}}</td>
                        <td class="text-right">{{number_format($periodCr,2)}}</td>
                        <td class="text-right">{{number_format($closingBalance,2)}}</td>
                    </tr>
                    <tr style="background:#d4edda;font-weight:bold">
                        <td colspan="6" class="text-right">Closing Balance ({{$end_date}})</td>
                        <td class="text-right">{{number_format($closingBalance,2)}}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
</section>
@endsection

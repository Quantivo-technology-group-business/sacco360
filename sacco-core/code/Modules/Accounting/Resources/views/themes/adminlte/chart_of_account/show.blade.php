@extends('core::layouts.master')
@section('title')
    {{ $chart_of_account->name }}
@endsection
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>
                        {{ $chart_of_account->name }}
                        <a href="#" onclick="window.history.back()"
                           class="btn btn-outline-light bg-white d-none d-sm-inline-flex">
                            <em class="icon ni ni-arrow-left"></em><span>{{ trans_choice('core::general.back',1) }}</span>
                        </a>
                    </h1>

                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a
                                    href="{{url('dashboard')}}">{{ trans_choice('dashboard::general.dashboard',1) }}</a>
                        </li>
                        <li class="breadcrumb-item"><a
                                    href="{{url('accounting/chart_of_account')}}">{{ trans_choice('accounting::general.chart_of_account',2) }}</a>
                        </li>
                        <li class="breadcrumb-item active">{{ $chart_of_account->name }}</li>
                    </ol>
                </div>
            </div>
        </div><!-- /.container-fluid -->
    
            <div class="col-md-12 mt-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Recent Transactions</h5>
                        <div class="card-tools">
                            <a href="{{ url('accounting/chart_of_account/'.$chart_of_account->id.'/statement') }}" class="btn btn-info btn-sm">
                                View Full Account Statement
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-bordered table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Date</th><th>Transaction #</th><th>Type</th>
                                    <th class="text-right">Debit</th><th class="text-right">Credit</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($recentEntries as $e)
                            <tr>
                                <td>{{$e->date}}</td>
                                <td><a href="{{ url('accounting/journal_entry/'.$e->transaction_number.'/show') }}">{{$e->transaction_number}}</a></td>
                                <td>{{$e->manual_entry ? 'Manual' : ucfirst(str_replace('_',' ',$e->transaction_type))}}</td>
                                <td class="text-right">@if($e->debit) {{number_format($e->debit,2)}} @endif</td>
                                <td class="text-right">@if($e->credit) {{number_format($e->credit,2)}} @endif</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted">No transactions recorded.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

</section>
    <section class="content" id="app">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <td>{{ trans_choice('accounting::general.gl_code',2) }}</td>
                                <td>{{$chart_of_account->gl_code}}</td>
                            </tr>
                            <tr>
                                <td>{{ trans_choice('core::general.account',1) }} {{ trans_choice('core::general.type',1) }}</td>
                                <td>
                                    @if($chart_of_account->account_type=='asset')
                                        {{trans_choice('accounting::general.asset',1)}}
                                    @endif
                                    @if($chart_of_account->account_type=='expense')
                                        {{trans_choice('accounting::general.expense',1)}}
                                    @endif
                                    @if($chart_of_account->account_type=='equity')
                                        {{trans_choice('accounting::general.equity',1)}}
                                    @endif
                                    @if($chart_of_account->account_type=='liability')
                                        {{trans_choice('accounting::general.liability',1)}}
                                    @endif
                                    @if($chart_of_account->account_type=='income')
                                        {{trans_choice('accounting::general.income',1)}}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>{{ trans_choice('accounting::general.manual_entries_allowed',1) }}</td>
                                <td>
                                    @if($chart_of_account->allow_manual==1)
                                        {{trans_choice('core::general.yes',1)}}
                                    @else
                                        {{trans_choice('core::general.no',1)}}
                                    @endif
                                </td>
                            </tr>

                            <tr>
                                <td>{{ trans_choice('core::general.active',1) }} </td>
                                <td>
                                    @if($chart_of_account->active==1)
                                        {{ trans_choice('core::general.yes',1) }}
                                    @else
                                        {{ trans_choice('core::general.no',1) }}
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>{{ trans_choice('core::general.note',2) }} </td>
                                <td>{!! $chart_of_account->notes !!}</td>
                            </tr>
                        </table>

                    </div>
                </div>
                <!-- /.box -->
            </div>
        </div>
    </section>
@endsection
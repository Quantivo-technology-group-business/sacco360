@extends('core::layouts.master')
@section('title') Budget vs Actual @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Budget vs Actual Variance</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('accounting/budget')}}">Budget</a></li>
                    <li class="breadcrumb-item active">Variance</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Budget vs Actual — {{$year}} @if($month) / {{$months[$month]}} @endif</h3>
        </div>
        <div class="card-body">
            <form method="get" action="{{Request::url()}}">
                <div class="row">
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Year</label>
                            <select class="form-control" name="year">
                                @foreach($years as $y) <option value="{{$y}}" @if($year==$y) selected @endif>{{$y}}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Month</label>
                            <select class="form-control" name="month">
                                <option value="">Full Year</option>
                                @foreach($months as $n => $m) <option value="{{$n}}" @if($month==$n) selected @endif>{{$m}}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Branch</label>
                            <select class="form-control" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $b) <option value="{{$b->id}}" @if($branch_id==$b->id) selected @endif>{{$b->name}}</option> @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Generate</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($data->count())
    @php
        $types = ['income'=>'Income','expense'=>'Expense','asset'=>'Asset','liability'=>'Liability','equity'=>'Equity'];
    @endphp
    @foreach($types as $typeKey => $typeLabel)
    @php $rows = $data->where('account_type', $typeKey)->where(fn($r) => $r->budgeted != 0 || $r->actual != 0); @endphp
    @if($rows->count())
    <div class="card">
        <div class="card-header" style="background:#f4f4f4"><h5 class="mb-0"><strong>{{$typeLabel}}</strong></h5></div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm mb-0">
                <thead>
                    <tr style="background:#e9ecef">
                        <th>GL</th><th>Account</th>
                        <th class="text-right">Budget</th>
                        <th class="text-right">Actual</th>
                        <th class="text-right">Variance</th>
                        <th class="text-right">% Achieved</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($rows as $r)
                <tr>
                    <td>{{$r->gl_code}}</td>
                    <td>{{$r->name}}</td>
                    <td class="text-right">{{number_format($r->budgeted,2)}}</td>
                    <td class="text-right">{{number_format($r->actual,2)}}</td>
                    <td class="text-right @if($r->variance<0) text-danger @else text-success @endif">
                        {{number_format($r->variance,2)}}
                    </td>
                    <td class="text-right">
                        @if($r->pct !== null)
                            <div class="progress" style="height:16px;min-width:80px">
                                <div class="progress-bar @if($r->pct<50) bg-danger @elseif($r->pct<80) bg-warning @else bg-success @endif"
                                     style="width:{{min($r->pct,100)}}%">{{$r->pct}}%</div>
                            </div>
                        @else <span class="text-muted">N/A</span> @endif
                    </td>
                </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:#f8f9fa;font-weight:bold">
                        <td colspan="2" class="text-right">Section Total</td>
                        <td class="text-right">{{number_format($rows->sum('budgeted'),2)}}</td>
                        <td class="text-right">{{number_format($rows->sum('actual'),2)}}</td>
                        <td class="text-right @if($rows->sum('variance')<0) text-danger @else text-success @endif">
                            {{number_format($rows->sum('variance'),2)}}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
    @endforeach
    @else
        <div class="alert alert-info">No data to display. Make sure budget entries exist for {{$year}} and there are journal entries in the period {{$start_date}} to {{$end_date}}.</div>
    @endif
</section>
@endsection

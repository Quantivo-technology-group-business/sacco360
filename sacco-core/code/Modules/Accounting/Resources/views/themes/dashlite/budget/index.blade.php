@extends('core::layouts.master')
@section('title') Budget Entries @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Budget Entries</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Budget</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Budget Entries — Year: {{$year}}</h3>
            <div class="card-tools">
                <a href="{{url('accounting/budget/variance')}}" class="btn btn-info btn-sm mr-2">Budget vs Actual</a>
                @can('accounting.budget.create')
                    <a href="{{url('accounting/budget/create')}}" class="btn btn-primary btn-sm">+ Add Budget</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <form method="get" action="{{Request::url()}}" class="mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <select class="form-control" name="year" onchange="this.form.submit()">
                            @foreach($years as $y)
                                <option value="{{$y}}" @if($year==$y) selected @endif>{{$y}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="branch_id" onchange="this.form.submit()">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{$b->id}}" @if($branch_id==$b->id) selected @endif>{{$b->name}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
            <table class="table table-bordered table-hover table-sm">
                <thead>
                    <tr><th>Account</th><th>GL Code</th><th>Month</th><th>Branch</th><th class="text-right">Budget Amount</th><th>Notes</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($budgets as $b)
                <tr>
                    <td>{{optional($b->chart_of_account)->name}}</td>
                    <td>{{optional($b->chart_of_account)->gl_code}}</td>
                    <td>{{$b->month ? date('F', mktime(0,0,0,$b->month,1)) : 'Annual'}}</td>
                    <td>{{optional($b->branch)->name ?? 'All'}}</td>
                    <td class="text-right">{{number_format($b->amount,2)}}</td>
                    <td>{{$b->notes}}</td>
                    <td>
                        @can('accounting.budget.edit')
                            <a href="{{url('accounting/budget/'.$b->id.'/edit')}}" class="btn btn-xs btn-warning">Edit</a>
                        @endcan
                        @can('accounting.budget.destroy')
                            <a href="{{url('accounting/budget/'.$b->id.'/destroy')}}" class="btn btn-xs btn-danger confirm">Delete</a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">No budget entries. <a href="{{url('accounting/budget/create')}}">Add one.</a></td></tr>
                @endforelse
                </tbody>
            </table>
            {{$budgets->links()}}
        </div>
    </div>
</section>
@endsection

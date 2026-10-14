@extends('core::layouts.master')
@section('title') Add Budget Entry @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6"><h1>Add Budget Entry</h1></div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{url('dashboard')}}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{url('accounting/budget')}}">Budget</a></li>
                    <li class="breadcrumb-item active">Add</li>
                </ol>
            </div>
        </div>
    </div>
</section>
<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>
    @endif
    <form method="post" action="{{url('accounting/budget/store')}}">
        {{csrf_field()}}
        <div class="card">
            <div class="card-body">
                <div class="row gy-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Account <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="chart_of_account_id" required>
                                <option value="">— Select Account —</option>
                                @foreach($chart_of_accounts as $acc)
                                    <option value="{{$acc->id}}" @if(old('chart_of_account_id')==$acc->id) selected @endif>
                                        [{{$acc->gl_code}}] {{$acc->name}} ({{ucfirst($acc->account_type)}})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Year <span class="text-danger">*</span></label>
                            <select class="form-control" name="year" required>
                                @foreach($years as $y)
                                    <option value="{{$y}}" @if(old('year',$y==date('Y')?$y:'')!='') @if(old('year')==$y) selected @endif @endif>{{$y}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Month <span class="text-muted">(leave blank for annual)</span></label>
                            <select class="form-control" name="month">
                                <option value="">Annual</option>
                                @foreach($months as $num => $name)
                                    <option value="{{$num}}" @if(old('month')==$num) selected @endif>{{$name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Branch</label>
                            <select class="form-control" name="branch_id">
                                <option value="">All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{$b->id}}">{{$b->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Budget Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="amount" class="form-control" value="{{old('amount')}}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Notes</label>
                            <input type="text" name="notes" class="form-control" value="{{old('notes')}}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary float-right">Save Budget</button>
                <a href="{{url('accounting/budget')}}" class="btn btn-secondary">Cancel</a>
            </div>
        </div>
    </form>
</section>
@endsection

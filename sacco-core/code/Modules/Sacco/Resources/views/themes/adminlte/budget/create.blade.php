@extends('core::layouts.master')
@section('title') New Budget Period @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New Budget Period</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <form method="POST" action="{{ url('sacco/budget/store') }}">@csrf
        <div class="row">
            <div class="col-md-5"><div class="form-group"><label>Name <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', 'Annual Budget '.date('Y')) }}" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Fiscal Year <span class="text-danger">*</span></label>
                <input type="number" name="fiscal_year" class="form-control" value="{{ old('fiscal_year', date('Y')) }}" min="2000" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y').'-01-01') }}" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date', date('Y').'-12-31') }}" required></div></div>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea></div>
        <button type="submit" class="btn btn-primary">Create Budget Period</button>
        <a href="{{ url('sacco/budget') }}" class="btn btn-default">Cancel</a>
    </form>
</div></div></section>
@endsection

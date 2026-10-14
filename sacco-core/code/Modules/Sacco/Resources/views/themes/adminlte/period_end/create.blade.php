@extends('core::layouts.master')
@section('title') New Period @endsection
@section('content')
<section class="content">
    <form method="post" action="{{url('sacco/period_end/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Period Name</label><input name="name" class="form-control" required placeholder="FY 2024"></div>
            <div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" required></div>
            <div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control" required></div>
            <button type="submit" class="btn btn-primary">Create</button>
        </div></div>
    </form>
</section>
@endsection

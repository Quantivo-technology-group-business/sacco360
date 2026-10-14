@extends('core::layouts.master')
@section('title') NPL Provision Rates @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>NPL Provision Rates</h1></div></section>
<section class="content">
    <div class="card">
        <div class="card-header"><h3 class="card-title">Configured Rates</h3></div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>Classification</th>
                    <th>Days Min</th>
                    <th>Days Max</th>
                    <th>Provision %</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @foreach($rates as $rate)
                    <tr>
                        <form method="post" action="{{ url('sacco/regulatory/provision-rates/'.$rate->id.'/update') }}">
                            @csrf
                            <td><input name="classification" class="form-control" value="{{ $rate->classification }}"></td>
                            <td><input name="days_in_arrears_min" class="form-control" value="{{ $rate->days_in_arrears_min }}"></td>
                            <td><input name="days_in_arrears_max" class="form-control" value="{{ $rate->days_in_arrears_max }}"></td>
                            <td><input name="provision_rate" class="form-control" value="{{ $rate->provision_rate }}"></td>
                            <td><button class="btn btn-sm btn-primary">Update</button></td>
                        </form>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Add Rate</h3></div>
        <div class="card-body">
            <form method="post" action="{{ url('sacco/regulatory/provision-rates/store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-3"><input name="classification" class="form-control" placeholder="Classification" required></div>
                    <div class="col-md-2"><input name="days_in_arrears_min" class="form-control" placeholder="Days min" required></div>
                    <div class="col-md-2"><input name="days_in_arrears_max" class="form-control" placeholder="Days max"></div>
                    <div class="col-md-2"><input name="provision_rate" class="form-control" placeholder="Provision %" required></div>
                    <div class="col-md-2"><button class="btn btn-success">Add</button></div>
                </div>
            </form>
        </div>
    </div>
    <a href="{{ url('sacco/regulatory') }}" class="btn btn-default">Back to SASRA Reports</a>
</section>
@endsection

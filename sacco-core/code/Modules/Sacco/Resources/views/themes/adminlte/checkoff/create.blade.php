@extends('core::layouts.master')
@section('title') Upload Check-Off @endsection
@section('content')
<section class="content">
    <form method="post" action="{{url('sacco/checkoff/store')}}" enctype="multipart/form-data">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Employer Name</label><input name="employer_name" class="form-control" required></div>
            <div class="form-group"><label>Period (e.g. 2024-06)</label><input name="period" class="form-control" required></div>
            <div class="form-group">
                <label>CSV/Excel File</label>
                <input type="file" name="file" class="form-control" required accept=".csv,.txt,.xlsx,.xls">
                @include('core::partials.upload_template', ['templateKey' => 'sacco.checkoff'])
            </div>
            <button type="submit" class="btn btn-primary">Upload & Reconcile</button>
        </div></div>
    </form>
    <div class="card mt-3"><div class="card-header">Import from Payroll Module</div><div class="card-body">
        <form method="post" action="{{url('sacco/checkoff/sync-payroll')}}">@csrf
            <div class="row">
                <div class="col-md-3"><input name="payroll_id" class="form-control" placeholder="Payroll Run ID" required></div>
                <div class="col-md-3"><input name="employer_name" class="form-control" placeholder="Employer Name" required></div>
                <div class="col-md-3"><input name="period" class="form-control" placeholder="Period e.g. 2024-06" required></div>
                <div class="col-md-3"><button class="btn btn-secondary">Sync Payroll Deductions</button></div>
            </div>
        </form>
    </div></div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') Register Cheque @endsection
@section('content')
<section class="content">
    <form method="post" action="{{url('sacco/cheque/store')}}" enctype="multipart/form-data">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Cheque Number</label><input name="cheque_number" class="form-control" required></div>
            <div class="form-group"><label>MICR Code</label><input name="micr_code" class="form-control" placeholder="Optional MICR line"></div>
            <div class="form-group"><label>Bank Name</label><input name="bank_name" class="form-control"></div>
            <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date" class="form-control" required></div>
            <div class="form-group">
                <label>Cheque Image</label>
                <input type="file" name="image" class="form-control" accept="image/*">
                @include('core::partials.upload_template', ['templateKey' => 'cheque.image'])
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
        </div></div>
    </form>
</section>
@endsection

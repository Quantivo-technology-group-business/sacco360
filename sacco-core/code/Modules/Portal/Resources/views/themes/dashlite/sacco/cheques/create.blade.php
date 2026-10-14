@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.deposit_cheque') }} @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <form method="post" action="{{ url('portal/sacco/cheques/store') }}" enctype="multipart/form-data">@csrf
        <div class="form-group"><label>Cheque #</label><input type="text" name="cheque_number" class="form-control" required></div>
        <div class="form-group"><label>Bank</label><input type="text" name="bank_name" class="form-control"></div>
        <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        <div class="form-group"><label>Cheque Date</label><input type="date" name="cheque_date" class="form-control" required></div>
        <div class="form-group"><label>MICR</label><input type="text" name="micr_code" class="form-control"></div>
        <div class="form-group"><label>Image</label><input type="file" name="image" class="form-control"></div>
        @include('portal::sacco.partials.transaction_auth', ['purpose' => 'cheque'])
        <button type="submit" class="btn btn-primary">{{ trans('core::general.save') }}</button>
    </form>
</div></div></section>
@endsection

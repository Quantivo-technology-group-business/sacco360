@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.member_transfer') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.transfer_to_member') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        @if($fosa)<p>FOSA balance: <strong>{{ number_format($balance, 2) }}</strong></p>@else<div class="alert alert-warning">{{ trans('sacco::general.no_fosa_account') }}</div>@endif
        <form method="post" action="{{ url('portal/sacco/member-transfer/store') }}">@csrf
            <div class="form-group"><label>{{ trans('sacco::general.recipient_member_number') }}</label><input type="text" name="member_number" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('core::general.amount') }}</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('sacco::general.narration') }}</label><input type="text" name="notes" class="form-control"></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'member_transfer'])
            <button type="submit" class="btn btn-primary">{{ trans('core::general.save') }}</button>
        </form>
    </div></div>
</section>
@endsection

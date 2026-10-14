@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.add_beneficiary') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.add_beneficiary') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <form method="post" action="{{ url('portal/sacco/beneficiaries/store') }}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>{{ trans('sacco::general.nickname') }}</label><input name="nickname" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('sacco::general.name') }}</label><input name="name" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('sacco::general.phone') }}</label><input name="phone" class="form-control" placeholder="07XXXXXXXX or 2547XXXXXXXX" required></div>
            <button class="btn btn-primary">{{ trans('core::general.save') }}</button>
            <a href="{{ url('portal/sacco/beneficiaries') }}" class="btn btn-default">{{ trans('core::general.cancel') }}</a>
        </div></div>
    </form>
</section>
@endsection

@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.pay_utility') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.pay_utility') }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card"><div class="card-body">
        <form method="post" action="{{ url('portal/sacco/utility/store') }}">@csrf
            <div class="form-group"><label>{{ trans('sacco::general.provider') }}</label>
                <select name="provider_id" class="form-control" required>
                    @foreach($providers as $pr)
                        <option value="{{ $pr->id }}">{{ $pr->name }} ({{ $pr->category }})</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>{{ trans('sacco::general.meter_account') }}</label><input type="text" name="account_number" class="form-control" required></div>
            <div class="form-group"><label>{{ trans('core::general.amount') }}</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'utility'])
            <button type="submit" class="btn btn-primary">{{ trans('sacco::general.pay_utility') }}</button>
        </form>
    </div></div>
</section>
@endsection

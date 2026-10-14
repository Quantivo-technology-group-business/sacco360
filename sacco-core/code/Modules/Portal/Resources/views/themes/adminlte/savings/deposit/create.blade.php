@extends('core::layouts.master')
@section('title') {{ trans_choice('savings::general.deposit',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('savings::general.make',1) }} {{ trans_choice('savings::general.deposit',1) }}</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="card mb-3"><div class="card-body">
        <p>{{ $savings->savings_product->name }} (#{{ $savings->id }})</p>
        <p>{{ trans_choice('savings::general.balance',1) }}: {{ number_format($balance, $savings->decimals ?? 2) }}</p>
    </div></div>
    <form method="post" action="{{ url('portal/savings/'.$savings->id.'/deposit/store') }}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group">
                <label>{{ trans_choice('savings::general.amount',1) }}</label>
                <input name="amount" type="number" step="0.01" min="1" class="form-control" value="{{ old('amount') }}" required>
            </div>
            <div class="form-group">
                <label>{{ trans_choice('general.description',1) }}</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
            </div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'savings_deposit'])
            <button class="btn btn-success">{{ trans_choice('core::general.save',1) }}</button>
            <a href="{{ url('portal/savings/'.$savings->id.'/show') }}" class="btn btn-default">{{ trans_choice('core::general.cancel',1) }}</a>
        </div></div>
    </form>
</section>
@endsection

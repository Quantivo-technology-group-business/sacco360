@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.utility_payments') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.utility_payment_history') }}</h1>
    <a href="{{ url('portal/sacco/utility/create') }}" class="btn btn-primary btn-sm float-right">{{ trans('sacco::general.pay_utility') }}</a>
</div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Date</th><th>{{ trans('sacco::general.provider') }}</th><th>Account</th><th>Amount</th><th>Ref</th><th>Channel</th></tr></thead>
            <tbody>
            @forelse($payments as $p)
                <tr>
                    <td>{{ $p->created_at }}</td>
                    <td>{{ optional($p->provider)->name }}</td>
                    <td>{{ $p->account_number }}</td>
                    <td>{{ number_format($p->amount, 2) }}</td>
                    <td>{{ $p->reference }}</td>
                    <td>{{ $p->channel }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">{{ trans('sacco::general.no_utility_payments') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

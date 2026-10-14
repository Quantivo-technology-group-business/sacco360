@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.fixed_deposits') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.fixed_deposits') }}</h1>
    <a href="{{ url('portal/sacco/fixed_deposit/create') }}" class="btn btn-primary btn-sm float-right">{{ trans('sacco::general.new_fixed_deposit') }}</a>
</div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Principal</th><th>Rate</th><th>Tenure</th><th>Maturity</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($deposits as $d)
                <tr><td>{{ number_format($d->principal, 2) }}</td><td>{{ $d->interest_rate }}%</td><td>{{ $d->tenure_months }} mo</td><td>{{ $d->maturity_date }}</td><td>{{ $d->status }}</td></tr>
            @empty
                <tr><td colspan="5" class="text-center">{{ trans('sacco::general.no_fixed_deposits') }}</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $deposits->links() }}
    </div></div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.transfer_history') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.transfer_history') }}</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Type</th><th>Amount</th><th>Date</th><th>Status</th><th>Reference</th></tr></thead>
            <tbody>
            @forelse($transfers as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->transfer_type }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->transfer_date }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->reference }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No transfers yet.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $transfers->links() }}
    </div></div>
</section>
@endsection

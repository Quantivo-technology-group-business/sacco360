@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.standing_order', 2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.standing_order', 2) }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <a href="{{ url('portal/sacco/standing_order/create') }}" class="btn btn-primary mb-2">New Standing Order</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Amount</th><th>Frequency</th><th>Next Run</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($orders as $o)
                <tr>
                    <td>{{ $o->id }}</td>
                    <td>{{ number_format($o->amount, 2) }}</td>
                    <td>{{ $o->frequency }}</td>
                    <td>{{ $o->next_run_date }}</td>
                    <td>{{ $o->status }}</td>
                    <td>
                        @if($o->status === 'active')
                            <a href="{{ url('portal/sacco/standing_order/' . $o->id . '/cancel') }}" class="btn btn-sm btn-warning" onclick="return confirm('Cancel this standing order?')">Cancel</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center">No standing orders.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $orders->links() }}
    </div></div>
</section>
@endsection

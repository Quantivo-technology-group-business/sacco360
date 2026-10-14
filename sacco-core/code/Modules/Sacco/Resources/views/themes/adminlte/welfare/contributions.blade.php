@extends('core::layouts.master')
@section('title') Welfare Contributions @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Welfare Contributions</h1></div>
</section>
<section class="content">
    <a href="{{ url('sacco/welfare') }}" class="btn btn-default mb-3">← Back to Dashboard</a>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr><th>Date</th><th>Member</th><th>Product</th><th>Period</th><th>Amount</th><th>Status</th></tr>
                </thead>
                <tbody>
                @forelse($contributions as $c)
                    <tr>
                        <td>{{ $c->contribution_date }}</td>
                        <td>{{ $c->client->full_name ?? '—' }}</td>
                        <td>{{ $c->product->name ?? '—' }}</td>
                        <td>{{ $c->period }}</td>
                        <td>{{ number_format($c->amount, 2) }}</td>
                        <td><span class="badge badge-{{ $c->status==='posted'?'success':'secondary' }}">{{ $c->status }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">No contributions recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $contributions->links() }}
        </div>
    </div>
</section>
@endsection

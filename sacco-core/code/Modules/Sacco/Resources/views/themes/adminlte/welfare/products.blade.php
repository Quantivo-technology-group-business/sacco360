@extends('core::layouts.master')
@section('title') Welfare Products @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Welfare Products</h1></div>
</section>
<section class="content">
    <a href="{{ url('sacco/welfare/products/create') }}" class="btn btn-primary mb-3">
        <i class="fas fa-plus"></i> Add Welfare Product
    </a>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Name</th><th>Code</th><th>Type</th>
                        <th>Monthly Contribution</th><th>Max Claim</th>
                        <th>Qualifying Months</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    <tr>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->code }}</td>
                        <td>{{ ucfirst($p->type) }}</td>
                        <td>{{ number_format($p->member_contribution, 2) }}</td>
                        <td>{{ $p->max_claim_amount ? number_format($p->max_claim_amount, 2) : '—' }}</td>
                        <td>{{ $p->qualifying_months }}</td>
                        <td>
                            <span class="badge badge-{{ $p->is_active ? 'success' : 'secondary' }}">
                                {{ $p->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ url('sacco/welfare/products/'.$p->id.'/edit') }}" class="btn btn-sm btn-warning">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No welfare products defined.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $products->links() }}
        </div>
    </div>
</section>
@endsection

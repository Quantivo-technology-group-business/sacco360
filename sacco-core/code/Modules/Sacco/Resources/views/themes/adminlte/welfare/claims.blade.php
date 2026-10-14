@extends('core::layouts.master')
@section('title') Welfare Claims @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Welfare Claims</h1></div>
</section>
<section class="content">
    <div class="mb-3">
        @can('sacco.welfare.claims')
            <a href="{{ url('sacco/welfare/claims/create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Claim
            </a>
        @endcan
        <a href="{{ url('sacco/welfare') }}" class="btn btn-default">← Back to Dashboard</a>
        {{-- Status filter --}}
        @foreach(['all','submitted','under_review','approved','rejected','paid'] as $s)
            <a href="{{ url('sacco/welfare/claims?status='.$s) }}"
               class="btn btn-sm {{ $status==$s ? 'btn-dark' : 'btn-outline-secondary' }} ml-1">
                {{ ucfirst(str_replace('_',' ',$s)) }}
            </a>
        @endforeach
    </div>
    <div class="card">
        <div class="card-body">
            <table id="claims-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Ref</th><th>Member</th><th>Product</th><th>Event Date</th>
                        <th>Amount Claimed</th><th>Amount Approved</th><th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse($claims as $c)
                    <tr>
                        <td>{{ $c->claim_reference }}</td>
                        <td>{{ $c->client->full_name ?? '—' }}</td>
                        <td>{{ $c->product->name ?? '—' }}</td>
                        <td>{{ $c->event_date }}</td>
                        <td>{{ number_format($c->amount_claimed, 2) }}</td>
                        <td>{{ $c->amount_approved ? number_format($c->amount_approved,2) : '—' }}</td>
                        <td>
                            @php
                                $badge = ['submitted'=>'secondary','under_review'=>'warning','approved'=>'info',
                                          'rejected'=>'danger','paid'=>'success','cancelled'=>'dark'][$c->status] ?? 'secondary';
                            @endphp
                            <span class="badge badge-{{ $badge }}">{{ ucfirst(str_replace('_',' ',$c->status)) }}</span>
                        </td>
                        <td>
                            <a href="{{ url('sacco/welfare/claims/'.$c->id.'/show') }}" class="btn btn-sm btn-info">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted">No claims found.</td></tr>
                @endforelse
                </tbody>
            </table>
            {{ $claims->links() }}
        </div>
    </div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#claims-table').DataTable({
            paging: false,
            order: [[3, 'desc']],
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

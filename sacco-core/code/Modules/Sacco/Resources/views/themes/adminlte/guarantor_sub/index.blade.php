@extends('core::layouts.master')
@section('title') Guarantor Substitutions @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Guarantor Substitution Requests</h1></div></section>
<section class="content">
    @can('sacco.guarantor_sub.create')
        <a href="{{ url('sacco/guarantor-sub/create') }}" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> New Request</a>
    @endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Loan</th><th>Borrower</th><th>Outgoing Guarantor</th><th>Incoming Guarantor</th><th>Request Date</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($substitutions as $s)
                <tr>
                    <td>#{{ $s->loan_id }}</td>
                    <td>{{ $s->loan->client->full_name ?? '—' }}</td>
                    <td>{{ $s->outgoingGuarantor->full_name ?? '—' }}</td>
                    <td>{{ $s->incomingGuarantor->full_name ?? '—' }}</td>
                    <td>{{ $s->request_date }}</td>
                    <td><span class="badge badge-{{ ['pending'=>'warning','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$s->status]??'secondary' }}">{{ ucfirst($s->status) }}</span></td>
                    <td><a href="{{ url('sacco/guarantor-sub/'.$s->id.'/show') }}" class="btn btn-sm btn-info">View</a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted">No substitution requests.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $substitutions->links() }}
    </div></div>
</section>
@endsection

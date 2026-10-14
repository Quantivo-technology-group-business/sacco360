@extends('core::layouts.master')
@section('title') Budget Management @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Budget Management</h1></div></section>
<section class="content">
    @can('sacco.budget.create')
        <a href="{{ url('sacco/budget/create') }}" class="btn btn-primary mb-3"><i class="fas fa-plus"></i> New Budget Period</a>
    @endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered table-striped">
            <thead><tr><th>Name</th><th>Fiscal Year</th><th>Period</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
            @forelse($periods as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->fiscal_year }}</td>
                    <td>{{ $p->start_date }} to {{ $p->end_date }}</td>
                    <td>
                        @php $b=['draft'=>'secondary','approved'=>'info','active'=>'success','closed'=>'dark'][$p->status]??'secondary'; @endphp
                        <span class="badge badge-{{ $b }}">{{ ucfirst($p->status) }}</span>
                    </td>
                    <td>
                        <a href="{{ url('sacco/budget/'.$p->id.'/show') }}" class="btn btn-sm btn-info">Manage Lines</a>
                        <a href="{{ url('sacco/budget/'.$p->id.'/vs-actual') }}" class="btn btn-sm btn-secondary">Budget vs Actual</a>
                        @if($p->status==='draft')@can('sacco.budget.approve')
                            <a href="{{ url('sacco/budget/'.$p->id.'/approve') }}" class="btn btn-sm btn-success">Approve</a>
                        @endcan@endif
                        @if($p->status==='approved')@can('sacco.budget.approve')
                            <a href="{{ url('sacco/budget/'.$p->id.'/activate') }}" class="btn btn-sm btn-primary">Activate</a>
                        @endcan@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted">No budget periods defined.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $periods->links() }}
    </div></div>
</section>
@endsection

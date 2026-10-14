@extends('core::layouts.master')
@section('title') Groups / Chamas @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Groups &amp; Chamas</h1></div>
</section>
<section class="content">
    @can('sacco.groups.create')
        <a href="{{ url('sacco/group/create') }}" class="btn btn-primary mb-3"><i class="fas fa-plus mr-1"></i> New Group</a>
    @endcan
    <div class="card">
        <div class="card-body p-0">
            <table id="data-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th><th>Code</th><th>Type</th><th>Branch</th>
                        <th>Members</th><th>Monthly Contribution</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ url("sacco/group/get_groups") }}',
            columns: [
                { data: 'name',             name: 'name' },
                { data: 'code',             name: 'code', defaultContent: '—' },
                { data: 'type_label',       name: 'type' },
                { data: 'branch_name',      name: 'branches.name', defaultContent: '—' },
                { data: 'members_count',    name: 'members_count' },
                { data: 'monthly_contribution', name: 'monthly_contribution',
                  render: d => parseFloat(d || 0).toLocaleString('en-KE', {minimumFractionDigits: 2}) },
                { data: 'status_badge',     name: 'status', orderable: false },
                { data: 'action',           name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

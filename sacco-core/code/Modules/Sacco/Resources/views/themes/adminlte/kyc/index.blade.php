@extends('core::layouts.master')
@section('title') Member KYC @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Member KYC / IPRS</h1>
        <a href="{{url('sacco/kyc/search')}}" class="btn btn-primary btn-sm">Search Member</a>
        @can('sacco.kyc.manage')
        <a href="{{url('sacco/kyc/requirements')}}" class="btn btn-default btn-sm">KYC Requirements</a>
        @endcan
    </div>
</section>
<section class="content">
    <div class="row">
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-danger"><i class="fas fa-user-times"></i></span><div class="info-box-content"><span class="info-box-text">Incomplete</span><span class="info-box-number">{{$stats['incomplete']}}</span></div></div></div>
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span><div class="info-box-content"><span class="info-box-text">Pending Review</span><span class="info-box-number">{{$stats['pending_review']}}</span></div></div></div>
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-success"><i class="fas fa-check"></i></span><div class="info-box-content"><span class="info-box-text">Verified</span><span class="info-box-number">{{$stats['verified']}}</span></div></div></div>
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-secondary"><i class="fas fa-ban"></i></span><div class="info-box-content"><span class="info-box-text">Rejected</span><span class="info-box-number">{{$stats['rejected']}}</span></div></div></div>
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-info"><i class="fas fa-calendar-times"></i></span><div class="info-box-content"><span class="info-box-text">Expired</span><span class="info-box-number">{{$stats['expired']}}</span></div></div></div>
        <div class="col-md-2"><div class="info-box"><span class="info-box-icon bg-primary"><i class="fas fa-file"></i></span><div class="info-box-content"><span class="info-box-text">Docs Pending</span><span class="info-box-number">{{$stats['pending_documents']}}</span></div></div></div>
    </div>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Members Awaiting KYC Action</h3></div>
        <div class="card-body p-0">
            <table id="data-table" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Member</th><th>Member No.</th><th>KYC Status</th><th>IPRS</th><th>Action</th>
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
            ajax: '{{ url("sacco/kyc/get_kyc_pending") }}',
            columns: [
                { data: 'name',      name: 'first_name' },
                { data: 'member_no', name: 'member_number', defaultContent: '—' },
                { data: 'kyc_badge', name: 'kyc_status', orderable: false },
                { data: 'iprs',      name: 'iprs_verified_at', orderable: false },
                { data: 'action',    name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'asc']],
            pageLength: 25,
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

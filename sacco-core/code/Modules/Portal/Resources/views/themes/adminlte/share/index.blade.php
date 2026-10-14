@extends('core::layouts.master')
@section('title')
    {{ trans_choice('share::general.share',2) }}
@endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>{{ trans_choice('share::general.share',2) }}</h1>
                </div>
            </div>
        </div>
    </section>
    <section class="content" id="app">
        <div class="card">
            <div class="card-body table-responsive">
                <table class="table table-striped table-hover" id="data-table">
                    <thead>
                    <tr>
                        <th>{{ trans_choice('core::general.id',1) }}</th>
                        <th>{{ trans_choice('core::general.branch',1) }}</th>
                        <th>{{ trans_choice('share::general.total_shares',1) }}</th>
                        <th>{{ trans('sacco::general.share_value') }}</th>
                        <th>{{ trans_choice('share::general.status',1) }}</th>
                        <th>{{ trans_choice('share::general.product',1) }}</th>
                        <th>{{ trans_choice('core::general.action',1) }}</th>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
    </section>
@endsection
@section('scripts')
    <script>
        $('#data-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{!! url('portal/share/get_shares?status='.request('status')) !!}',
            columns: [
                {data: 'id', name: 'shares.id'},
                {data: 'branch', name: 'branches.name'},
                {data: 'total_shares', name: 'shares.total_shares'},
                {data: 'share_value', name: 'share_value'},
                {data: 'status', name: 'shares.status'},
                {data: 'share_product', name: 'share_products.name'},
                {data: 'action', name: 'action', orderable: false, searchable: false}
            ],
            order: [[0, 'desc']]
        });
    </script>
@endsection

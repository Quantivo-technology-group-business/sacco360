@extends('core::layouts.master')
@section('title') Investment Portfolio @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid"><h1>Investment Portfolio</h1></div>
</section>
<section class="content">
    <div class="row mb-3">
        <div class="col-md-3"><div class="info-box bg-primary"><span class="info-box-icon"><i class="fas fa-chart-line"></i></span>
            <div class="info-box-content"><span class="info-box-text">Total Invested</span>
            <span class="info-box-number">{{ number_format($summary['total_principal'],2) }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box bg-info"><span class="info-box-icon"><i class="fas fa-coins"></i></span>
            <div class="info-box-content"><span class="info-box-text">Current Value</span>
            <span class="info-box-number">{{ number_format($summary['total_current_value'],2) }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box {{ $summary['unrealised_gain_loss']>=0?'bg-success':'bg-danger' }}">
            <span class="info-box-icon"><i class="fas fa-exchange-alt"></i></span>
            <div class="info-box-content"><span class="info-box-text">Unrealised Gain/Loss</span>
            <span class="info-box-number">{{ number_format($summary['unrealised_gain_loss'],2) }}</span></div></div></div>
        <div class="col-md-3"><div class="info-box bg-warning"><span class="info-box-icon"><i class="fas fa-calendar-alt"></i></span>
            <div class="info-box-content"><span class="info-box-text">Maturing (30 days)</span>
            <span class="info-box-number">{{ $maturingSoon->count() }}</span></div></div></div>
    </div>

    @can('sacco.investments.create')
        <a href="{{ url('sacco/investment/create') }}" class="btn btn-primary mb-3"><i class="fas fa-plus mr-1"></i> New Investment</a>
    @endcan

    @if($maturingSoon->count())
    <div class="alert alert-warning">
        <strong>Maturing Soon:</strong>
        @foreach($maturingSoon as $m)
            <span class="badge badge-warning mr-1">{{ $m->name }} — {{ $m->maturity_date }}</span>
        @endforeach
    </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <table id="data-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr>
                        <th>Name</th><th>Type</th><th>Issuer</th><th>Principal</th>
                        <th>Current Value</th><th>Rate %</th><th>Maturity</th><th>Status</th><th>Action</th>
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
            ajax: '{{ url("sacco/investment/get_investments") }}',
            columns: [
                { data: 'name',          name: 'name' },
                { data: 'type_label',    name: 'type' },
                { data: 'issuer',        name: 'issuer', defaultContent: '—' },
                { data: 'principal_fmt', name: 'principal_amount', searchable: false },
                { data: 'value_fmt',     name: 'current_value', searchable: false },
                { data: 'rate_fmt',      name: 'interest_rate', searchable: false },
                { data: 'maturity_date', name: 'maturity_date', defaultContent: '—' },
                { data: 'status_badge',  name: 'status', orderable: false },
                { data: 'action',        name: 'action', orderable: false, searchable: false }
            ],
            order: [[0, 'desc']],
            pageLength: 25,
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

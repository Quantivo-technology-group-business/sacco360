@extends('core::layouts.master')
@section('title') Cross-Sell Campaigns @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>Cross-Sell Campaigns</h1>
        <a href="{{url('sacco/cross_sell/create')}}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i>New Campaign</a>
    </div>
</section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{session('success')}}</div>@endif

    <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Campaigns</h3></div>
        <div class="card-body p-0">
            <table id="campaigns-table" class="table table-bordered table-striped table-hover">
                <thead>
                    <tr><th>Name</th><th>Source</th><th>Target</th><th>Min Balance</th><th>Active</th></tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $c)
                    <tr>
                        <td>{{$c->name}}</td>
                        <td>{{ucfirst($c->source_wing)}}</td>
                        <td>{{ucfirst($c->target_wing)}}</td>
                        <td>{{number_format($c->min_balance,2)}}</td>
                        <td>
                            <span class="badge badge-{{$c->active ? 'success' : 'secondary'}}">
                                {{$c->active ? 'Active' : 'Inactive'}}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach($eligible as $e)
    <div class="card mb-3">
        <div class="card-header">
            <h3 class="card-title">{{$e['campaign']->name}}</h3>
            <span class="badge badge-info ml-2">{{$e['eligible_count']}} eligible members</span>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Member</th><th>Member No.</th><th>Message</th></tr></thead>
                <tbody>
                    @foreach($e['members'] as $m)
                    <tr>
                        <td>{{$m['name']}}</td>
                        <td>{{$m['member_number']}}</td>
                        <td>{{$m['message']}}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#campaigns-table').DataTable({
            pageLength: 10,
            order: [[0, 'asc']],
            responsive: true,
            autoWidth: false,
        });
    </script>
@endsection

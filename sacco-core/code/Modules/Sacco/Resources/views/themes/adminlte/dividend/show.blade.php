@extends('core::layouts.master')
@section('title') Dividend #{{$dividend->id}} @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content">
    <div class="card"><div class="card-body">
        <p><strong>{{$dividend->name}}</strong> — {{$dividend->status}}</p>
        <p>Per Share: {{number_format($dividend->dividend_per_share,4)}} | Pool: {{number_format($dividend->total_pool ?? 0,2)}}</p>
        @if($dividend->status==='declared')
            <a href="{{url('sacco/dividend/'.$dividend->id.'/process')}}" class="btn btn-success confirm">Process Payout</a>
        @endif
        <h5 class="mt-3">Allocations</h5>
        <table id="allocations-table" class="table table-sm table-bordered table-hover">
            <thead><tr><th>Client</th><th>Shares</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>@foreach($dividend->allocations as $a)<tr>
                <td>{{$a->client_id}}</td><td>{{$a->shares_held}}</td><td>{{number_format($a->amount,2)}}</td><td>{{$a->status}}</td>
            </tr>@endforeach</tbody>
        </table>
    </div></div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#allocations-table').DataTable({ pageLength: 25, order: [[2,'desc']], responsive: true, autoWidth: false });
    </script>
@endsection

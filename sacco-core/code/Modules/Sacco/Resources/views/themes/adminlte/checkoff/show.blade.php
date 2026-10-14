@extends('core::layouts.master')
@section('title') Check-Off Batch #{{$batch->id}} @endsection
@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
@stop
@section('content')
<section class="content">
    <div class="card"><div class="card-body">
        <p><strong>{{$batch->employer_name}}</strong> — {{$batch->period}} — {{$batch->status}}</p>
        <p>Matched: {{$batch->matched_records}} / {{$batch->total_records}} | Amount: {{number_format($batch->total_amount,2)}}</p>
        @if($batch->status==='reconciled')
            <a href="{{url('sacco/checkoff/'.$batch->id.'/post')}}" class="btn btn-success confirm">Post Payments</a>
        @endif
        <table id="items-table" class="table table-sm table-bordered table-hover mt-3">
            <thead><tr><th>Employee</th><th>Type</th><th>Target</th><th>Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>@foreach($batch->items as $i)<tr>
                <td>{{$i->employee_name}} ({{$i->employee_id}})</td><td>{{$i->deduction_type ?? 'loan'}}</td>
                <td>@if($i->loan_id)Loan #{{$i->loan_id}}@elseif($i->savings_id)BOSA #{{$i->savings_id}}@elseif($i->share_id)Share #{{$i->share_id}}@endif</td>
                <td>{{number_format($i->amount,2)}}</td><td>{{$i->status}}</td>
                <td>@if($i->status==='posted')<a href="{{url('sacco/checkoff/item/'.$i->id.'/refund')}}" class="btn btn-xs btn-warning confirm">Refund</a>@endif</td>
            </tr>@endforeach</tbody>
        </table>
    </div></div>
</section>
@endsection
@section('scripts')
    <script src="{{ asset('assets/plugins/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
    <script>
        $('#items-table').DataTable({ pageLength: 25, order: [[0,'asc']], responsive: true, autoWidth: false });
    </script>
@endsection

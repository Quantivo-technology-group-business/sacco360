@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.checkoff',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.checkoff',1) }}</h1></div></section>
<section class="content">
    @can('sacco.checkoff.create')<a href="{{url('sacco/checkoff/create')}}" class="btn btn-primary mb-2">Upload Remittance</a>@endcan
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Employer</th><th>Period</th><th>Records</th><th>Matched</th><th>Amount</th><th>Status</th><th></th></tr></thead>
            <tbody>@foreach($batches as $b)<tr>
                <td>{{$b->id}}</td><td>{{$b->employer_name}}</td><td>{{$b->period}}</td>
                <td>{{$b->total_records}}</td><td>{{$b->matched_records}}/{{$b->unmatched_records}} unmatched</td>
                <td>{{number_format($b->total_amount,2)}}</td><td>{{$b->status}}</td>
                <td><a href="{{url('sacco/checkoff/'.$b->id.'/show')}}" class="btn btn-sm btn-info">View</a></td>
            </tr>@endforeach</tbody>
        </table>{{$batches->links()}}
    </div></div>
</section>
@endsection

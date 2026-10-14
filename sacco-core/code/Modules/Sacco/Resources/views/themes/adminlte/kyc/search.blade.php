@extends('core::layouts.master')
@section('title') Search Member KYC @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Search Member KYC</h1><a href="{{url('sacco/kyc')}}" class="btn btn-default btn-sm">Back</a></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <form method="get" class="form-inline mb-3">
            <input type="text" name="q" class="form-control mr-2" placeholder="Name or member number" value="{{$q}}">
            <button type="submit" class="btn btn-primary">Search</button>
        </form>
        @if($q)
        <table class="table table-striped">
            <thead><tr><th>Member</th><th>Number</th><th>KYC Status</th><th></th></tr></thead>
            <tbody>
            @forelse($clients as $c)
            <tr>
                <td>{{$c->first_name}} {{$c->last_name}}</td>
                <td>{{$c->member_number ?? $c->external_id}}</td>
                <td>{{ucfirst(str_replace('_',' ',$c->kyc_status ?? 'incomplete'))}}</td>
                <td><a href="{{url('sacco/kyc/'.$c->id.'/show')}}" class="btn btn-xs btn-primary">Open KYC</a></td>
            </tr>
            @empty
            <tr><td colspan="4">No members found.</td></tr>
            @endforelse
            </tbody>
        </table>
        @endif
    </div></div>
</section>
@endsection

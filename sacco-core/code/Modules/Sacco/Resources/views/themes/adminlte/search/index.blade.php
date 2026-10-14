@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.global_search') }} @endsection
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <h1>{{ trans('sacco::general.global_search') }}</h1>
    </div>
</section>
<section class="content">
    <div class="card">
        <div class="card-body">
            <form method="get" action="{{url('sacco/search')}}" class="form-inline mb-3">
                <input type="text" name="q" class="form-control mr-2" value="{{$query}}" placeholder="Member number, ID, phone, account number, name...">
                <button class="btn btn-primary" type="submit">{{ trans_choice('core::general.search',1) }}</button>
            </form>
            @if($query)
            <table class="table table-bordered">
                <thead><tr><th>Type</th><th>Label</th><th>Detail</th><th>Alerts</th><th></th></tr></thead>
                <tbody>
                @forelse($results as $r)
                    <tr class="{{!empty($r['has_earmarks'])?'table-warning':''}}">
                        <td>{{ucfirst($r['type'])}}</td>
                        <td>{{$r['label']}}</td>
                        <td>{{$r['detail']}}</td>
                        <td>
                            @if(!empty($r['earmarks']))
                                @foreach($r['earmarks'] as $e)
                                    <span class="badge badge-{{$e['severity']==='critical'?'danger':'warning'}}" title="{{$e['message']}}">{{$e['title']}}</span>
                                @endforeach
                            @else
                                —
                            @endif
                        </td>
                        <td><a href="{{$r['url']}}" class="btn btn-sm btn-info">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5">No results found.</td></tr>
                @endforelse
                </tbody>
            </table>
            @endif
        </div>
    </div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.earmark', 2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.earmark', 2) }}</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Title</th><th>Type</th><th>Message</th><th>Severity</th></tr></thead>
            <tbody>
            @forelse($earmarks as $e)
                <tr><td>{{ $e['title'] ?? '' }}</td><td>{{ $e['restriction_type'] ?? '' }}</td><td>{{ $e['message'] ?? '' }}</td><td>{{ $e['severity'] ?? '' }}</td></tr>
            @empty
                <tr><td colspan="4" class="text-center">{{ trans('sacco::general.no_earmarks') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

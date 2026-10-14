@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.beneficiaries') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.beneficiaries') }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="mb-3"><a href="{{ url('portal/sacco/beneficiaries/create') }}" class="btn btn-primary">{{ trans('sacco::general.add_beneficiary') }}</a></div>
    <div class="card"><div class="card-body">
        <table class="table table-striped">
            <thead><tr><th>{{ trans('sacco::general.nickname') }}</th><th>{{ trans('sacco::general.name') }}</th><th>{{ trans('sacco::general.phone') }}</th><th></th></tr></thead>
            <tbody>
            @forelse($beneficiaries as $b)
                <tr>
                    <td>{{ $b->nickname }}</td>
                    <td>{{ $b->name }}</td>
                    <td>{{ $b->phone }}</td>
                    <td>
                        <form method="post" action="{{ url('portal/sacco/beneficiaries/'.$b->id.'/destroy') }}" onsubmit="return confirm('Remove this beneficiary?');">
                            @csrf
                            <button class="btn btn-sm btn-danger">{{ trans('core::general.delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4">{{ trans('sacco::general.no_beneficiaries') }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

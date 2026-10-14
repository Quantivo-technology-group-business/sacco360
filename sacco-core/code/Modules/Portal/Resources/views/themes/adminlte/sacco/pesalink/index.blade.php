@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.pesalink') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.pesalink') }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if($simulation)<div class="alert alert-warning">{{ trans('sacco::general.pesalink_simulation_notice') }}</div>@endif
    <a href="{{ url('portal/sacco/pesalink/create') }}" class="btn btn-primary mb-2">Send Pesalink Transfer</a>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>ID</th><th>Beneficiary</th><th>Bank</th><th>Amount</th><th>Reference</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            @forelse($transactions as $t)
                <tr>
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->beneficiary_name }} ({{ $t->beneficiary_account }})</td>
                    <td>{{ $t->beneficiary_bank }}</td>
                    <td>{{ number_format($t->amount, 2) }}</td>
                    <td>{{ $t->reference }}</td>
                    <td>{{ $t->status }}</td>
                    <td>{{ $t->created_at }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No Pesalink transactions.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $transactions->links() }}
    </div></div>
</section>
@endsection

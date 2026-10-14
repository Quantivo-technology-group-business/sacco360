@extends('core::layouts.master')
@section('title') Statement @endsection
@section('content')
<section class="content"><div class="card"><div class="card-body">
    <h4>Savings #{{ $data['savings']->account_number ?? $data['savings']->id }} ({{ $start_date }} — {{ $end_date }})</h4>
    <p>Opening balance: {{ number_format($data['opening_balance'], 2) }}</p>
    <table class="table table-bordered">
        <thead><tr><th>Date</th><th>Description</th><th>Debit</th><th>Credit</th></tr></thead>
        <tbody>
        @foreach($data['transactions'] as $t)
            <tr><td>{{ $t->submitted_on }}</td><td>{{ $t->name }}</td><td>{{ number_format($t->debit, 2) }}</td><td>{{ number_format($t->credit, 2) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    <a href="{{ url('portal/sacco/statements') }}" class="btn btn-default">{{ trans('core::general.back') }}</a>
</div></div></section>
@endsection

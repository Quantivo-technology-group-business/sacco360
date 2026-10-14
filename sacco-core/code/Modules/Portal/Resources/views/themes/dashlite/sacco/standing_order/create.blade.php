@extends('core::layouts.master')
@section('title') New Standing Order @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>New Standing Order</h1></div></section>
<section class="content">
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <form method="post" action="{{ url('portal/sacco/standing_order/store') }}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group">
                <label>From Savings Account</label>
                <select name="source_savings_id" class="form-control" required>
                    @foreach($savings as $s)
                        <option value="{{ $s->id }}">#{{ $s->account_number ?? $s->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>To Loan (optional)</label>
                <select name="destination_loan_id" class="form-control">
                    <option value="">-- Select loan --</option>
                    @foreach($loans as $l)
                        <option value="{{ $l->id }}">Loan #{{ $l->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>To Savings Account (optional)</label>
                <select name="destination_savings_id" class="form-control">
                    <option value="">-- Select savings --</option>
                    @foreach($savings as $s)
                        <option value="{{ $s->id }}">#{{ $s->account_number ?? $s->id }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>Amount</label><input name="amount" type="number" step="0.01" class="form-control" required></div>
            <div class="form-group">
                <label>Frequency</label>
                <select name="frequency" class="form-control" required>
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly" selected>Monthly</option>
                </select>
            </div>
            <div class="form-group"><label>Start Date</label><input name="start_date" type="date" class="form-control" value="{{ date('Y-m-d') }}" required></div>
            <div class="form-group"><label>End Date (optional)</label><input name="end_date" type="date" class="form-control"></div>
            <div class="form-group"><label>Description</label><textarea name="description" class="form-control"></textarea></div>
            @include('portal::sacco.partials.transaction_auth', ['purpose' => 'standing_order'])
            <button class="btn btn-primary">Create Standing Order</button>
        </div></div>
    </form>
</section>
@endsection

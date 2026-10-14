@extends('core::layouts.master')
@section('title') Send Statement On-Demand @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Send Member Statement On-Demand</h1></div></section>
<section class="content"><div class="card"><div class="card-body">
    <form method="POST" action="{{ url('sacco/estatement/send-demand') }}">@csrf
        <div class="row">
            <div class="col-md-4"><div class="form-group"><label>Member <span class="text-danger">*</span></label>
                <select name="client_id" class="form-control select2" required>
                    <option value="">— Select Member —</option>
                    @foreach($clients as $c)
                        <option value="{{ $c->id }}">{{ $c->full_name }} ({{ $c->member_number ?? $c->id }})</option>
                    @endforeach
                </select></div></div>
            <div class="col-md-2"><div class="form-group"><label>Statement Type</label>
                <select name="statement_type" class="form-control">
                    <option value="full">Full Statement</option>
                    <option value="savings">Savings Only</option>
                    <option value="loans">Loans Only</option>
                    <option value="shares">Shares Only</option>
                </select></div></div>
            <div class="col-md-2"><div class="form-group"><label>From Date <span class="text-danger">*</span></label>
                <input type="date" name="from_date" class="form-control" value="{{ date('Y-m-01') }}" required></div></div>
            <div class="col-md-2"><div class="form-group"><label>To Date <span class="text-danger">*</span></label>
                <input type="date" name="to_date" class="form-control" value="{{ date('Y-m-d') }}" required></div></div>
        </div>
        <button type="submit" class="btn btn-primary">Send Statement</button>
        <a href="{{ url('sacco/estatement') }}" class="btn btn-default">Cancel</a>
    </form>
</div></div></section>
@endsection

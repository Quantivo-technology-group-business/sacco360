@extends('core::layouts.master')
@section('title') Create {{ trans_choice('sacco::general.earmark',1) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Create Member Earmark</h1></div></section>
<section class="content">
    <form method="post" action="{{url('sacco/earmark/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group">
                <label>Member ID / {{ trans('sacco::general.member_number') }}</label>
                @if($client)
                    <input type="hidden" name="client_id" value="{{$client->id}}">
                    <input type="text" class="form-control" readonly value="{{$client->first_name}} {{$client->last_name}} ({{$member360['member_number'] ?? $client->external_id ?? $client->id}})">
                @else
                    <input type="number" name="client_id" class="form-control" required placeholder="Client ID from member profile URL">
                    <small class="text-muted">Open a member profile and use the ID from the URL, or add <code>?client_id=</code> to this page.</small>
                @endif
            </div>
            <div class="form-group"><label>Title</label><input name="title" class="form-control" required placeholder="e.g. Loan in arrears - restricted withdrawals"></div>
            <div class="form-group"><label>Message (shown to tellers)</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
            <div class="row">
                <div class="col-md-4 form-group">
                    <label>Severity</label>
                    <select name="severity" class="form-control">
                        <option value="info">Info</option>
                        <option value="warning" selected>Warning</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>{{ trans('sacco::general.restriction_type') }}</label>
                    <select name="restriction_type" class="form-control">
                        <option value="alert">Alert Only</option>
                        <option value="block_withdrawal">Block Withdrawals</option>
                        <option value="block_deposit">Block Deposits</option>
                        <option value="block_all_transactions">Block All Transactions</option>
                        <option value="block_loans">Block New Loans</option>
                    </select>
                </div>
                <div class="col-md-4 form-group">
                    <label>Expires At (optional)</label>
                    <input type="date" name="expires_at" class="form-control">
                </div>
            </div>
            <div class="form-check"><input type="checkbox" name="show_at_teller" value="1" checked class="form-check-input" id="show_at_teller"><label class="form-check-label" for="show_at_teller">Show pop-up alert at teller desk</label></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">Save Earmark</button></div></div>
    </form>
</section>
@endsection

@extends('core::layouts.master')
@section('title') Create Exit Settlement @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>Create Exit Settlement</h1></div></section>
<section class="content">
    <form method="post" action="{{url('sacco/exit/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Client ID</label><input name="client_id" class="form-control" value="{{request('client_id')}}" required></div>
            @if($calculation)
            <div class="alert alert-info">
                BOSA: {{number_format($calculation['bosa_balance'],2)}} |
                Shares: {{number_format($calculation['share_value'],2)}} |
                Loans: {{number_format($calculation['loan_balance'],2)}} |
                Net Payout: {{number_format($calculation['net_payout'],2)}}
            </div>
            @endif
            <div class="form-group"><label>Exit Reason</label>
                <select name="exit_reason" class="form-control" required>
                    @foreach(['resignation','retirement','death','expulsion','other'] as $r)
                    <option value="{{$r}}">{{ucfirst($r)}}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group"><label>Exit Date</label><input type="date" name="exit_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
            <div class="form-group"><label>Payout Account / Nominee</label><input name="payout_account" class="form-control" placeholder="Bank account or M-Pesa"></div>
            <div class="form-group"><label>Nominee Name</label><input name="nominee_name" class="form-control" value="{{$client->nominee_name ?? ''}}"></div>
            <div class="form-group"><label>Notes</label><textarea name="notes" class="form-control"></textarea></div>
            <button class="btn btn-primary">Create Draft</button>
        </div></div>
    </form>
</section>
@endsection

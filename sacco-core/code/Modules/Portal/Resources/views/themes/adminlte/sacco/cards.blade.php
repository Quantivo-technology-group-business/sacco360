@extends('core::layouts.master')
@section('title') {{ trans_choice('sacco::general.debit_card', 2) }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans_choice('sacco::general.debit_card', 2) }}</h1></div></section>
<section class="content">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    <div class="alert alert-info">{{ trans('sacco::general.card_reactivation_note') }}</div>
    <div class="card"><div class="card-body">
        <table class="table table-bordered">
            <thead><tr><th>Card</th><th>FOSA</th><th>Issued</th><th>Expires</th><th>Daily Limit</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse($cards as $card)
                <tr>
                    <td>**** **** **** {{ substr($card->card_number, -4) }}</td>
                    <td>#{{ $card->savings_id }}</td>
                    <td>{{ $card->issue_date }}</td>
                    <td>{{ $card->expiry_date }}</td>
                    <td>{{ number_format($card->daily_limit ?? 0, 2) }}</td>
                    <td>{{ $card->status }}</td>
                    <td>
                        @if($card->status !== 'blocked')
                        <form method="post" action="{{ url('portal/sacco/cards/'.$card->id.'/block') }}" class="d-inline">@csrf
                            <input type="password" name="transaction_pin" placeholder="PIN" maxlength="6" class="form-control form-control-sm d-inline-block" style="width:80px" required>
                            <button type="submit" class="btn btn-xs btn-danger">{{ trans('sacco::general.block_card') }}</button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center">No debit cards linked to your account.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
</section>
@endsection

@extends('core::layouts.master')
@section('title') {{ trans('sacco::general.account_statements') }} @endsection
@section('content')
<section class="content-header"><div class="container-fluid"><h1>{{ trans('sacco::general.account_statements') }}</h1></div></section>
<section class="content">
    <div class="card"><div class="card-body">
        <form method="post" action="{{ url('portal/sacco/statements/generate') }}">@csrf
            <div class="row">
                <div class="col-md-3"><label>{{ trans('core::general.type') }}</label>
                    <select name="account_type" class="form-control" required>
                        <option value="savings">Savings</option>
                        <option value="loan">Loan</option>
                    </select>
                </div>
                <div class="col-md-3"><label>{{ trans('sacco::general.account') }}</label>
                    <select name="account_id" class="form-control" required>
                        @foreach($savings as $s)<option value="{{ $s->id }}">Savings #{{ $s->account_number ?? $s->id }}</option>@endforeach
                        @foreach($loans as $l)<option value="{{ $l->id }}">Loan #{{ $l->id }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2"><label>{{ trans('sacco::general.start_date') }}</label><input type="date" name="start_date" class="form-control" required></div>
                <div class="col-md-2"><label>{{ trans('sacco::general.end_date') }}</label><input type="date" name="end_date" class="form-control" required></div>
                <div class="col-md-2"><label>&nbsp;</label><br>
                    <button type="submit" name="format" value="view" class="btn btn-primary">{{ trans('sacco::general.generate_statement') }}</button>
                    <button type="submit" name="format" value="pdf" class="btn btn-outline-secondary">PDF</button>
                </div>
            </div>
        </form>
    </div></div>
</section>
@endsection

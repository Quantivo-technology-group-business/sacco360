@extends('core::layouts.master')
@section('title') New Standing Order @endsection
@section('content')
<section class="content">
    <form method="post" action="{{url('sacco/standing_order/store')}}">@csrf
        <div class="card"><div class="card-body">
            <div class="form-group"><label>Client</label>
                <select name="client_id" id="client_id" class="form-control" required>@foreach($clients as $c)<option value="{{$c->id}}">{{$c->first_name}} {{$c->last_name}}</option>@endforeach</select>
            </div>
            <div class="form-group"><label>Source Savings</label><select name="source_savings_id" id="source_savings_id" class="form-control" required></select></div>
            <div class="form-group"><label>Destination Loan (optional)</label><select name="destination_loan_id" id="destination_loan_id" class="form-control"></select></div>
            <div class="form-group"><label>Destination Savings (optional)</label><select name="destination_savings_id" id="destination_savings_id" class="form-control"></select></div>
            <div class="form-group"><label>Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
            <div class="form-group"><label>Frequency</label>
                <select name="frequency" class="form-control"><option value="monthly">Monthly</option><option value="weekly">Weekly</option><option value="daily">Daily</option></select>
            </div>
            <div class="form-group"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="{{date('Y-m-d')}}" required></div>
            <div class="form-group"><label>End Date</label><input type="date" name="end_date" class="form-control"></div>
            <button type="submit" class="btn btn-primary">Save</button>
        </div></div>
    </form>
</section>
@endsection
@section('scripts')
<script>
$('#client_id').change(function(){
    $.get('{{url("sacco/standing_order/accounts")}}',{client_id:$(this).val()},function(d){
        $('#source_savings_id,#destination_savings_id').empty();
        $('#destination_loan_id').empty();
        d.savings.forEach(function(s){$('#source_savings_id,#destination_savings_id').append('<option value="'+s.id+'">Savings #'+s.id+'</option>');});
        d.loans.forEach(function(l){$('#destination_loan_id').append('<option value="'+l.id+'">Loan #'+l.id+'</option>');});
    });
}).trigger('change');
</script>
@endsection

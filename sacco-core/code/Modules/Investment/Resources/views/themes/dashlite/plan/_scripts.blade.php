@section('scripts')
<script>
var existingTiers = @json(isset($plan) && $plan ? $plan->tiers->map(fn($t) => ['label'=>$t->label,'min_amount'=>$t->min_amount,'max_amount'=>$t->max_amount,'return_rate'=>$t->return_rate]) : []);
var app = new Vue({
    el: '#planApp',
    data: {
        rateType: "{{old('return_rate_type', isset($plan) ? $plan->return_rate_type : 'fixed')}}",
        tiers: existingTiers.length ? existingTiers : [{label:'',min_amount:0,max_amount:'',return_rate:''}],
    },
    methods: {
        addTier() { this.tiers.push({label:'',min_amount:0,max_amount:'',return_rate:''}); }
    }
});
</script>
@endsection

@extends('core::layouts.master')
@section('title') Contribute — {{ $group->name }} @endsection
@section('content')

<section class="content-header">
    <div class="container-fluid">
        <h1>
            <i class="fas fa-hand-holding-usd mr-2"></i>Make Contribution
        </h1>
        <small class="text-muted">{{ $group->name }} &nbsp;·&nbsp; Your role: <strong>{{ ucfirst($membership->role) }}</strong></small>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-7">

                @if($alreadyPaid)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-1"></i>
                    You have already recorded a contribution for <strong>{{ date('F Y', strtotime($currentPeriod . '-01')) }}</strong>.
                    You can still contribute for a different period below.
                </div>
                @endif

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Contribution Details</h3>
                    </div>
                    <div class="card-body">

                        {{-- Group Summary --}}
                        <div class="alert alert-light border mb-4">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-muted small">Group</div>
                                    <strong>{{ $group->name }}</strong>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Monthly Amount</div>
                                    <strong class="text-success">KES {{ number_format($group->monthly_contribution, 2) }}</strong>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Current Period</div>
                                    <strong>{{ date('M Y', strtotime($currentPeriod . '-01')) }}</strong>
                                </div>
                            </div>
                        </div>

                        <form method="POST" action="{{ url('portal/sacco/chama/' . $group->id . '/contribute') }}" id="contributeForm">
                            @csrf

                            <div class="form-group">
                                <label>Period <span class="text-danger">*</span></label>
                                <input type="month" name="period" class="form-control"
                                       value="{{ old('period', $currentPeriod) }}" required>
                                <small class="text-muted">Select the month you are contributing for.</small>
                            </div>

                            <div class="form-group">
                                <label>Amount (KES) <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control"
                                       value="{{ old('amount', $group->monthly_contribution) }}"
                                       step="0.01" min="1" required>
                            </div>

                            <div class="form-group">
                                <label>Payment Method <span class="text-danger">*</span></label>
                                <div class="row" id="paymentMethods">

                                    {{-- M-Pesa --}}
                                    @if($mpesaEnabled)
                                    <div class="col-6 col-md-4 mb-2">
                                        <label class="payment-option w-100">
                                            <input type="radio" name="payment_method" value="mpesa"
                                                   class="d-none payment-radio" {{ old('payment_method') === 'mpesa' ? 'checked' : '' }}>
                                            <div class="card border text-center py-3 payment-card h-100">
                                                <div class="card-body p-2">
                                                    <i class="fas fa-mobile-alt fa-2x text-success"></i>
                                                    <div class="mt-2 font-weight-bold small">M-Pesa</div>
                                                    <div class="text-muted" style="font-size:11px">STK Push</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    @endif

                                    {{-- From Savings (FOSA) --}}
                                    <div class="col-6 col-md-4 mb-2">
                                        <label class="payment-option w-100">
                                            <input type="radio" name="payment_method" value="savings"
                                                   class="d-none payment-radio"
                                                   {{ old('payment_method') === 'savings' ? 'checked' : '' }}
                                                   @if($fosaBalance <= 0) disabled @endif>
                                            <div class="card border text-center py-3 payment-card h-100 {{ $fosaBalance <= 0 ? 'bg-light text-muted' : '' }}">
                                                <div class="card-body p-2">
                                                    <i class="fas fa-wallet fa-2x {{ $fosaBalance > 0 ? 'text-primary' : 'text-muted' }}"></i>
                                                    <div class="mt-2 font-weight-bold small">FOSA Savings</div>
                                                    <div class="text-muted" style="font-size:11px">
                                                        Bal: KES {{ number_format($fosaBalance, 2) }}
                                                    </div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    {{-- Cash --}}
                                    <div class="col-6 col-md-4 mb-2">
                                        <label class="payment-option w-100">
                                            <input type="radio" name="payment_method" value="cash"
                                                   class="d-none payment-radio"
                                                   {{ old('payment_method', 'cash') === 'cash' ? 'checked' : '' }}>
                                            <div class="card border text-center py-3 payment-card h-100">
                                                <div class="card-body p-2">
                                                    <i class="fas fa-money-bill-wave fa-2x text-warning"></i>
                                                    <div class="mt-2 font-weight-bold small">Cash</div>
                                                    <div class="text-muted" style="font-size:11px">Admin confirms</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    {{-- Bank --}}
                                    <div class="col-6 col-md-4 mb-2">
                                        <label class="payment-option w-100">
                                            <input type="radio" name="payment_method" value="bank"
                                                   class="d-none payment-radio"
                                                   {{ old('payment_method') === 'bank' ? 'checked' : '' }}>
                                            <div class="card border text-center py-3 payment-card h-100">
                                                <div class="card-body p-2">
                                                    <i class="fas fa-university fa-2x text-info"></i>
                                                    <div class="mt-2 font-weight-bold small">Bank Transfer</div>
                                                    <div class="text-muted" style="font-size:11px">Admin confirms</div>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                </div>
                            </div>

                            {{-- M-Pesa phone field (shown when mpesa selected) --}}
                            <div class="form-group" id="mpesaPhoneGroup" style="display:none">
                                <label>M-Pesa Phone Number</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    </div>
                                    <input type="text" name="phone_number" class="form-control"
                                           placeholder="e.g. 0712345678"
                                           value="{{ old('phone_number', $client->mobile ?? $client->phone ?? '') }}">
                                </div>
                                <small class="text-muted">An STK push will be sent to this number.</small>
                            </div>

                            {{-- Payment reference (shown for bank/cash/other) --}}
                            <div class="form-group" id="referenceGroup" style="display:none">
                                <label>Payment Reference</label>
                                <input type="text" name="payment_reference" class="form-control"
                                       placeholder="e.g. Cheque no., bank slip ref, receipt no."
                                       value="{{ old('payment_reference') }}">
                            </div>

                            <div class="form-group">
                                <label>Notes <small class="text-muted">(optional)</small></label>
                                <textarea name="notes" class="form-control" rows="2"
                                          placeholder="Any additional information...">{{ old('notes') }}</textarea>
                            </div>

                            <button type="submit" class="btn btn-success btn-block btn-lg" id="submitBtn">
                                <i class="fas fa-paper-plane mr-1"></i> Submit Contribution
                            </button>
                        </form>

                    </div>
                </div>

                <a href="{{ url('portal/sacco/chama/' . $group->id . '/show') }}" class="btn btn-default btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Group
                </a>

            </div>
        </div>
    </div>
</section>

@endsection

@section('scripts')
<script>
$(function () {
    // Payment method card selection
    function updatePaymentUI() {
        var selected = $('input.payment-radio:checked').val();

        // Highlight selected card
        $('.payment-card').removeClass('border-primary bg-light-primary');
        $('input.payment-radio:checked').closest('.payment-option').find('.payment-card')
            .addClass('border-primary');

        // Toggle conditional fields
        $('#mpesaPhoneGroup').toggle(selected === 'mpesa');
        $('#referenceGroup').toggle(selected === 'bank' || selected === 'cash' || selected === 'other');

        // Update submit button label
        var labels = {
            mpesa:   ' Send M-Pesa STK Push',
            savings: ' Pay from FOSA Savings',
            cash:    ' Record Cash Contribution',
            bank:    ' Record Bank Contribution',
            other:   ' Record Contribution',
        };
        $('#submitBtn').html('<i class="fas fa-paper-plane mr-1"></i>' + (labels[selected] || ' Submit Contribution'));
    }

    $('input.payment-radio').on('change', updatePaymentUI);
    updatePaymentUI(); // run on page load

    // Pre-select cash if nothing selected
    if (!$('input.payment-radio:checked').length) {
        $('input.payment-radio[value="cash"]').prop('checked', true);
        updatePaymentUI();
    }
});
</script>
<style>
.payment-card { cursor: pointer; transition: border-color 0.15s, box-shadow 0.15s; }
.payment-card:hover { border-color: #007bff !important; box-shadow: 0 0 0 2px rgba(0,123,255,0.15); }
.border-primary { box-shadow: 0 0 0 2px rgba(0,123,255,0.3); }
</style>
@endsection

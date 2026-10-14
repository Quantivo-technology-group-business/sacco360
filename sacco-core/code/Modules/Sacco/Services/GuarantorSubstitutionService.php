<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Modules\Sacco\Entities\SaccoGuarantorSubstitution;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanGuarantor;

class GuarantorSubstitutionService
{
    /**
     * Submit a guarantor substitution request.
     */
    public function submitRequest(array $data): SaccoGuarantorSubstitution
    {
        // Validate the outgoing guarantor is actually on this loan
        $loan = Loan::findOrFail($data['loan_id']);

        $isGuarantor = LoanGuarantor::where('loan_id', $loan->id)
            ->where('client_id', $data['outgoing_guarantor_client_id'])
            ->exists();

        if (!$isGuarantor) {
            throw new \Exception('The outgoing guarantor is not a guarantor on this loan.');
        }

        // Validate incoming guarantor is not already guaranteeing this loan
        $alreadyGuarantees = LoanGuarantor::where('loan_id', $loan->id)
            ->where('client_id', $data['incoming_guarantor_client_id'])
            ->exists();

        if ($alreadyGuarantees) {
            throw new \Exception('The incoming guarantor is already a guarantor on this loan.');
        }

        // Validate incoming != borrower
        if ($data['incoming_guarantor_client_id'] == $loan->client_id) {
            throw new \Exception('The borrower cannot be a guarantor on their own loan.');
        }

        $substitution = SaccoGuarantorSubstitution::create(array_merge($data, [
            'request_date'                 => Carbon::today(),
            'status'                       => 'pending',
            'outgoing_guarantor_consented' => 0,
            'incoming_guarantor_consented' => 0,
        ]));

        if (function_exists('sacco_compliance_log')) {
            sacco_compliance_log(
                'guarantor_substitution_requested',
                'sacco',
                'guarantor_substitution',
                $substitution->id,
                "Guarantor substitution requested for loan #{$loan->id}"
            );
        }

        return $substitution;
    }

    /**
     * Record consent from the outgoing guarantor.
     */
    public function recordOutgoingConsent(SaccoGuarantorSubstitution $sub): void
    {
        $sub->outgoing_guarantor_consented = 1;
        $sub->outgoing_consent_at = Carbon::now();
        $sub->save();
    }

    /**
     * Record consent from the incoming guarantor.
     */
    public function recordIncomingConsent(SaccoGuarantorSubstitution $sub): void
    {
        $sub->incoming_guarantor_consented = 1;
        $sub->incoming_consent_at = Carbon::now();
        $sub->save();
    }

    /**
     * Approve the substitution and update the loan guarantors.
     */
    public function approve(SaccoGuarantorSubstitution $sub, int $approvedById): void
    {
        if (!$sub->isReadyForApproval()) {
            throw new \Exception('Both guarantors must consent before approval.');
        }

        // Remove the outgoing guarantor from loan_guarantors
        LoanGuarantor::where('loan_id', $sub->loan_id)
            ->where('client_id', $sub->outgoing_guarantor_client_id)
            ->delete();

        // Add the incoming guarantor
        LoanGuarantor::create([
            'loan_id'           => $sub->loan_id,
            'client_id'         => $sub->incoming_guarantor_client_id,
            'guarantee_amount'  => $sub->guaranteed_amount,
        ]);

        $sub->status          = 'approved';
        $sub->approved_by_id  = $approvedById;
        $sub->decision_date   = Carbon::today();
        $sub->save();
    }

    /**
     * Reject the substitution.
     */
    public function reject(SaccoGuarantorSubstitution $sub, string $reason, int $approvedById): void
    {
        $sub->status           = 'rejected';
        $sub->rejection_reason = $reason;
        $sub->approved_by_id   = $approvedById;
        $sub->decision_date    = Carbon::today();
        $sub->save();
    }
}

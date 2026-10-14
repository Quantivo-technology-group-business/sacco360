<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Sacco\Entities\SaccoWelfareClaim;
use Modules\Sacco\Entities\SaccoWelfareContribution;
use Modules\Sacco\Entities\SaccoWelfareProduct;
use Modules\Client\Entities\Client;

class WelfareService
{
    /**
     * Check whether a member is eligible to claim a given welfare product.
     */
    public function checkEligibility(Client $client, SaccoWelfareProduct $product): array
    {
        $contributionMonths = SaccoWelfareContribution::where('client_id', $client->id)
            ->where('sacco_welfare_product_id', $product->id)
            ->where('status', 'posted')
            ->distinct('period')
            ->count('period');

        if ($contributionMonths < $product->qualifying_months) {
            return [
                'eligible' => false,
                'reason'   => "Requires {$product->qualifying_months} months of contributions. You have {$contributionMonths}.",
            ];
        }

        // Check waiting period from first contribution
        $firstContribution = SaccoWelfareContribution::where('client_id', $client->id)
            ->where('sacco_welfare_product_id', $product->id)
            ->where('status', 'posted')
            ->orderBy('contribution_date')
            ->first();

        if ($firstContribution && $product->waiting_period_days > 0) {
            $daysSinceFirst = Carbon::parse($firstContribution->contribution_date)->diffInDays(now());
            if ($daysSinceFirst < $product->waiting_period_days) {
                $remaining = $product->waiting_period_days - $daysSinceFirst;
                return [
                    'eligible' => false,
                    'reason'   => "Waiting period not elapsed. {$remaining} days remaining.",
                ];
            }
        }

        // Check for existing open claim on same product
        $openClaim = SaccoWelfareClaim::where('client_id', $client->id)
            ->where('sacco_welfare_product_id', $product->id)
            ->whereIn('status', ['submitted', 'under_review'])
            ->exists();

        if ($openClaim) {
            return [
                'eligible' => false,
                'reason'   => 'You already have an open claim for this product.',
            ];
        }

        return ['eligible' => true, 'reason' => null];
    }

    /**
     * Submit a new welfare claim.
     */
    public function submitClaim(array $data): SaccoWelfareClaim
    {
        $claim = new SaccoWelfareClaim($data);
        $claim->claim_reference = 'WEL-' . strtoupper(Str::random(8));
        $claim->status = 'submitted';
        $claim->save();

        if (function_exists('sacco_compliance_log')) {
            sacco_compliance_log(
                'welfare_claim_submitted',
                'sacco',
                'welfare_claim',
                $claim->id,
                "Welfare claim {$claim->claim_reference} submitted"
            );
        }

        return $claim;
    }

    /**
     * Approve a welfare claim.
     */
    public function approveClaim(SaccoWelfareClaim $claim, float $approvedAmount, int $approvedById): void
    {
        $claim->status = 'approved';
        $claim->amount_approved = $approvedAmount;
        $claim->approved_by_id = $approvedById;
        $claim->save();
    }

    /**
     * Reject a welfare claim.
     */
    public function rejectClaim(SaccoWelfareClaim $claim, string $reason, int $reviewedById): void
    {
        $claim->status = 'rejected';
        $claim->rejection_reason = $reason;
        $claim->reviewed_by_id = $reviewedById;
        $claim->save();
    }

    /**
     * Mark a welfare claim as paid.
     */
    public function markPaid(SaccoWelfareClaim $claim, string $paymentReference): void
    {
        $claim->status = 'paid';
        $claim->payment_date = Carbon::today();
        $claim->payment_reference = $paymentReference;
        $claim->save();
    }

    /**
     * Get the total welfare fund balance for a product
     * (total contributions minus total payouts).
     */
    public function getFundBalance(SaccoWelfareProduct $product): float
    {
        $totalIn = SaccoWelfareContribution::where('sacco_welfare_product_id', $product->id)
            ->where('status', 'posted')
            ->sum('amount');

        $totalOut = SaccoWelfareClaim::where('sacco_welfare_product_id', $product->id)
            ->where('status', 'paid')
            ->sum('amount_approved');

        return (float) $totalIn - (float) $totalOut;
    }
}

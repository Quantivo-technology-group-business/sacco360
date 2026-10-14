<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareProduct;

class ShareRegulatoryService
{
    public function getCooperativeShareValue(): float
    {
        return (float)DB::table('shares')
            ->join('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->where('shares.status', 'active')
            ->selectRaw('COALESCE(SUM(shares.total_shares * COALESCE(shares.nominal_price, share_products.nominal_price, 0)), 0) as value')
            ->value('value');
    }

    public function getMemberShareValue(int $clientId, ?int $excludeShareId = null): float
    {
        $query = DB::table('shares')
            ->join('share_products', 'share_products.id', '=', 'shares.share_product_id')
            ->where('shares.client_id', $clientId)
            ->where('shares.status', 'active');

        if ($excludeShareId) {
            $query->where('shares.id', '!=', $excludeShareId);
        }

        return (float)$query
            ->selectRaw('COALESCE(SUM(shares.total_shares * COALESCE(shares.nominal_price, share_products.nominal_price, 0)), 0) as value')
            ->value('value');
    }

    public function resolveMaxPercent(?ShareProduct $product = null): float
    {
        if ($product && $product->max_share_percent) {
            return (float)$product->max_share_percent;
        }

        return (float)config('sacco.share_cap_limit_percent', 20);
    }

    public function validateAdditionalPurchase(
        int $clientId,
        float $additionalValue,
        ?ShareProduct $product = null,
        ?int $excludeShareId = null
    ): array {
        if ($additionalValue <= 0) {
            return ['passed' => true, 'violations' => [], 'member_percent' => 0, 'max_percent' => $this->resolveMaxPercent($product)];
        }

        $maxPercent = $this->resolveMaxPercent($product);
        $coopTotal = $this->getCooperativeShareValue();
        $memberValue = $this->getMemberShareValue($clientId, $excludeShareId);
        $newMemberValue = $memberValue + $additionalValue;
        $newCoopTotal = $coopTotal + $additionalValue;
        $memberPercent = $newCoopTotal > 0 ? ($newMemberValue / $newCoopTotal) * 100 : 0;

        $violations = [];
        if ($memberPercent > $maxPercent) {
            $violations[] = sprintf(
                'Share purchase would give member %.2f%% of total share capital (SASRA limit: %.2f%%)',
                $memberPercent,
                $maxPercent
            );
        }

        return [
            'passed' => empty($violations),
            'violations' => $violations,
            'member_percent' => round($memberPercent, 4),
            'max_percent' => $maxPercent,
            'member_value_after' => $newMemberValue,
            'coop_value_after' => $newCoopTotal,
        ];
    }

    public function validateShareAccount(Share $share, float $additionalValue = 0): array
    {
        $product = $share->share_product ?? ShareProduct::find($share->share_product_id);
        $nominal = $share->nominal_price ?? ($product->nominal_price ?? 0);

        if ($additionalValue <= 0 && $share->status !== 'active') {
            $additionalValue = ($share->total_shares ?? 0) * $nominal;
        }

        return $this->validateAdditionalPurchase(
            (int)$share->client_id,
            $additionalValue,
            $product,
            $share->status === 'active' ? null : (int)$share->id
        );
    }

    public function validatePaymentDeadline(Share $share): array
    {
        $product = $share->share_product ?? ShareProduct::find($share->share_product_id);
        if (!$product || ($product->share_category ?? '') !== 'compulsory') {
            return ['passed' => true, 'violations' => []];
        }

        $deadlineMonths = (int)($product->payment_deadline_months ?? 0);
        if ($deadlineMonths <= 0) {
            return ['passed' => true, 'violations' => []];
        }

        $startDate = $share->activated_on_date ?: $share->application_date;
        if (!$startDate) {
            return ['passed' => true, 'violations' => []];
        }

        $deadline = date('Y-m-d', strtotime($startDate . " +{$deadlineMonths} months"));
        if ($deadline >= date('Y-m-d')) {
            return ['passed' => true, 'violations' => [], 'deadline' => $deadline];
        }

        $nominal = $share->nominal_price ?? ($product->nominal_price ?? 0);
        $targetValue = ($share->total_shares ?? 0) * $nominal;
        $paidValue = (float)DB::table('share_transactions')
            ->where('share_id', $share->id)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as paid')
            ->value('paid');

        if ($targetValue > 0 && $paidValue >= $targetValue) {
            return ['passed' => true, 'violations' => [], 'deadline' => $deadline];
        }

        return [
            'passed' => false,
            'violations' => [
                sprintf(
                    'Compulsory share payment deadline (%s) passed. Paid %.2f of %.2f required.',
                    $deadline,
                    $paidValue,
                    $targetValue
                ),
            ],
            'deadline' => $deadline,
        ];
    }
}

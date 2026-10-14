<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Modules\Sacco\Entities\SaccoInvestment;
use Modules\Sacco\Entities\SaccoInvestmentValuation;

class InvestmentService
{
    /**
     * Create a new investment record.
     */
    public function create(array $data): SaccoInvestment
    {
        $investment = SaccoInvestment::create($data);

        // Post initial valuation equal to principal
        SaccoInvestmentValuation::create([
            'sacco_investment_id' => $investment->id,
            'valuation_date'      => $investment->investment_date,
            'market_value'        => $investment->principal_amount,
            'accrued_interest'    => 0,
            'created_by_id'       => $data['created_by_id'] ?? null,
            'notes'               => 'Initial valuation at investment date.',
        ]);

        return $investment;
    }

    /**
     * Add a new valuation snapshot.
     */
    public function addValuation(SaccoInvestment $investment, array $data): SaccoInvestmentValuation
    {
        $valuation = SaccoInvestmentValuation::create(array_merge($data, [
            'sacco_investment_id' => $investment->id,
        ]));

        // Update current_value on the investment
        $investment->current_value = $valuation->market_value + $valuation->accrued_interest;
        $investment->save();

        return $valuation;
    }

    /**
     * Mark an investment as matured and record final return.
     */
    public function markMatured(SaccoInvestment $investment, float $actualReturn): void
    {
        $investment->status        = 'matured';
        $investment->actual_return = $actualReturn;
        $investment->save();
    }

    /**
     * Portfolio summary: total invested, total current value, unrealised gain/loss.
     */
    public function getPortfolioSummary(): array
    {
        $investments = SaccoInvestment::whereIn('status', ['active'])->get();

        $totalPrincipal = $investments->sum('principal_amount');
        $totalCurrent   = $investments->sum(function ($inv) {
            return $inv->current_value ?? $inv->principal_amount;
        });

        return [
            'total_principal'      => $totalPrincipal,
            'total_current_value'  => $totalCurrent,
            'unrealised_gain_loss' => $totalCurrent - $totalPrincipal,
            'count_active'         => $investments->count(),
            'by_type'              => $investments->groupBy('type')->map(function ($group) {
                return [
                    'count'     => $group->count(),
                    'principal' => $group->sum('principal_amount'),
                    'current'   => $group->sum(fn($i) => $i->current_value ?? $i->principal_amount),
                ];
            }),
        ];
    }

    /**
     * Get investments maturing within the next N days.
     */
    public function getUpcomingMaturities(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoInvestment::where('status', 'active')
            ->whereNotNull('maturity_date')
            ->whereBetween('maturity_date', [Carbon::today(), Carbon::today()->addDays($days)])
            ->orderBy('maturity_date')
            ->get();
    }
}

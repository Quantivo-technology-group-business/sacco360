<?php
namespace Modules\Investment\Services;

use Carbon\Carbon;
use Modules\Investment\Entities\InvestmentPlan;
use Modules\Investment\Entities\MemberInvestment;

class ProfitCalculatorService
{
    /**
     * Calculate profit for a given principal, rate, method, and period (in days).
     */
    public function calculate(
        float  $principal,
        float  $annualRatePercent,
        string $method,            // simple | compound
        string $compoundFrequency, // daily|monthly|quarterly|annually
        int    $days
    ): float {
        $rate = $annualRatePercent / 100;

        if ($method === 'simple') {
            return $principal * $rate * ($days / 365);
        }

        // Compound
        $n = match($compoundFrequency) {
            'daily'     => 365,
            'monthly'   => 12,
            'quarterly' => 4,
            default     => 1,   // annually
        };

        $t = $days / 365;
        return $principal * (pow(1 + $rate / $n, $n * $t) - 1);
    }

    /**
     * Calculate projected schedule of profit postings from start to maturity.
     * Returns array of ['date' => Carbon, 'profit' => float, 'balance' => float]
     */
    public function projectedSchedule(MemberInvestment $inv): array
    {
        $plan      = $inv->plan;
        $principal = (float)$inv->principal_amount;
        $rate      = (float)$inv->effective_rate;
        $method    = $plan->interest_method;
        $freq      = $plan->profit_posting_frequency;
        $start     = Carbon::parse($inv->start_date);
        $end       = $inv->maturity_date ? Carbon::parse($inv->maturity_date) : $start->copy()->addMonths(12);

        $schedule  = [];
        $current   = $start->copy();
        $balance   = $principal;
        $totalProfit = 0;

        $step = match($freq) {
            'monthly'     => 1,
            'quarterly'   => 3,
            'semi_annual' => 6,
            'annual'      => 12,
            default       => null, // on_maturity
        };

        if ($step === null) {
            // on_maturity: single entry
            $days   = $start->diffInDays($end);
            $profit = $this->calculate($principal, $rate, $method, $plan->compounding_frequency, $days);
            $schedule[] = [
                'period'  => 1,
                'date'    => $end->format('Y-m-d'),
                'days'    => $days,
                'profit'  => round($profit, 4),
                'balance' => round($principal + $profit, 4),
            ];
            return $schedule;
        }

        $period = 1;
        while ($current->lt($end)) {
            $next   = $current->copy()->addMonths($step);
            if ($next->gt($end)) $next = $end->copy();
            $days   = $current->diffInDays($next);
            if ($days <= 0) break;

            $profit = $this->calculate($balance, $rate, $method, $plan->compounding_frequency, $days);
            $totalProfit += $profit;

            if ($method === 'compound') {
                $balance += $profit; // reinvest for next period
            }

            $schedule[] = [
                'period'  => $period++,
                'date'    => $next->format('Y-m-d'),
                'days'    => $days,
                'profit'  => round($profit, 4),
                'balance' => round($balance, 4),
            ];
            $current = $next;
            if ($current->gte($end)) break;
        }

        return $schedule;
    }

    /**
     * Calculate and post due profit to a member investment.
     * Returns the profit amount posted, or 0 if nothing due.
     */
    public function postDueProfit(MemberInvestment $inv): float
    {
        if (!in_array($inv->status, ['active'])) return 0;

        $today    = Carbon::today();
        $nextPost = $inv->next_profit_posting_date ? Carbon::parse($inv->next_profit_posting_date) : null;

        if ($nextPost && $nextPost->gt($today)) return 0;

        $lastPost = $inv->last_profit_posting_date
            ? Carbon::parse($inv->last_profit_posting_date)
            : Carbon::parse($inv->start_date);

        $days = $lastPost->diffInDays($today);
        if ($days <= 0) return 0;

        $plan   = $inv->plan;
        $profit = $this->calculate(
            (float)$inv->principal_amount,
            (float)$inv->effective_rate,
            $plan->interest_method,
            $plan->compounding_frequency,
            $days
        );

        return round($profit, 4);
    }

    /**
     * Calculate maturity payout (principal + all accrued profits).
     */
    public function maturityPayout(MemberInvestment $inv): array
    {
        $plan     = $inv->plan;
        $start    = Carbon::parse($inv->start_date);
        $end      = $inv->maturity_date ? Carbon::parse($inv->maturity_date) : Carbon::today();
        $days     = $start->diffInDays($end);

        $totalProfit = $this->calculate(
            (float)$inv->principal_amount,
            (float)$inv->effective_rate,
            $plan->interest_method,
            $plan->compounding_frequency,
            $days
        );

        $alreadyPaid = (float)$inv->total_profit_paid;
        $remainingProfit = max(0, $totalProfit - $alreadyPaid);

        return [
            'principal'        => (float)$inv->principal_amount,
            'total_profit'     => round($totalProfit, 4),
            'already_paid'     => round($alreadyPaid, 4),
            'remaining_profit' => round($remainingProfit, 4),
            'payout_total'     => round((float)$inv->principal_amount + $remainingProfit, 4),
        ];
    }

    /**
     * Early withdrawal payout (principal minus penalty).
     */
    public function earlyWithdrawalPayout(MemberInvestment $inv): array
    {
        $plan      = $inv->plan;
        $principal = (float)$inv->principal_amount;
        $penalty   = $principal * ((float)$plan->early_withdrawal_penalty_rate / 100);
        return [
            'principal'  => $principal,
            'penalty'    => round($penalty, 4),
            'net_payout' => round($principal - $penalty, 4),
        ];
    }
}

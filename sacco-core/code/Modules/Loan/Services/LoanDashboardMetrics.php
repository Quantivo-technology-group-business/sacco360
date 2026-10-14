<?php

namespace Modules\Loan\Services;

use Illuminate\Support\Carbon;
use Modules\Loan\Entities\Loan;

class LoanDashboardMetrics
{
    public static function activeLoansWithSchedules()
    {
        return Loan::with('repayment_schedules', 'loan_product', 'client', 'branch')
            ->where('status', 'active')
            ->get();
    }

    public static function calculateLoanOutstanding($loan)
    {
        $schedules = $loan->repayment_schedules;
        $principal = $schedules->sum('principal') - $schedules->sum('principal_waived_derived')
            - $schedules->sum('principal_repaid_derived') - $schedules->sum('principal_written_off_derived');
        $interest = $schedules->sum('interest') - $schedules->sum('interest_waived_derived')
            - $schedules->sum('interest_repaid_derived') - $schedules->sum('interest_written_off_derived');
        $fees = $schedules->sum('fees') - $schedules->sum('fees_waived_derived')
            - $schedules->sum('fees_repaid_derived') - $schedules->sum('fees_written_off_derived');
        $penalties = $schedules->sum('penalties') - $schedules->sum('penalties_waived_derived')
            - $schedules->sum('penalties_repaid_derived') - $schedules->sum('penalties_written_off_derived');

        return max(0, $principal + $interest + $fees + $penalties);
    }

    public static function daysInArrears($loan)
    {
        $overdue = $loan->repayment_schedules
            ->where('due_date', '<', Carbon::today()->format('Y-m-d'))
            ->where('total_due', '>', 0)
            ->sortBy('due_date')
            ->first();

        if (empty($overdue)) {
            return 0;
        }

        return Carbon::parse($overdue->due_date)->diffInDays(Carbon::today());
    }

    public static function portfolioAtRisk($loans = null)
    {
        $loans = $loans ?: self::activeLoansWithSchedules();
        $totalOutstanding = 0;
        $atRisk = [1 => 0, 7 => 0, 30 => 0, 90 => 0];

        foreach ($loans as $loan) {
            $outstanding = self::calculateLoanOutstanding($loan);
            $totalOutstanding += $outstanding;
            $days = self::daysInArrears($loan);

            foreach ([1, 7, 30, 90] as $threshold) {
                if ($days >= $threshold) {
                    $atRisk[$threshold] += $outstanding;
                }
            }
        }

        $percentages = [];
        foreach ([1, 7, 30, 90] as $threshold) {
            $percentages[$threshold] = $totalOutstanding > 0
                ? round(($atRisk[$threshold] / $totalOutstanding) * 100, 2)
                : 0;
        }

        return [
            'total_outstanding' => $totalOutstanding,
            'at_risk_amounts' => $atRisk,
            'par_percentages' => $percentages,
        ];
    }

    public static function arrearsAging($loans = null)
    {
        $loans = $loans ?: self::activeLoansWithSchedules();
        $buckets = [
            'current' => 0,
            '1_30' => 0,
            '31_60' => 0,
            '61_90' => 0,
            '90_plus' => 0,
        ];

        foreach ($loans as $loan) {
            $days = self::daysInArrears($loan);
            $arrearsAmount = self::calculateArrearsAmount($loan);

            if ($days === 0) {
                continue;
            }
            if ($days <= 30) {
                $buckets['1_30'] += $arrearsAmount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $arrearsAmount;
            } elseif ($days <= 90) {
                $buckets['61_90'] += $arrearsAmount;
            } else {
                $buckets['90_plus'] += $arrearsAmount;
            }
        }

        return $buckets;
    }

    public static function calculateArrearsAmount($loan)
    {
        $total = 0;
        $arrearsLastSchedule = $loan->repayment_schedules
            ->sortByDesc('due_date')
            ->where('due_date', '<', date('Y-m-d'))
            ->where('total_due', '>', 0)
            ->first();

        if (empty($arrearsLastSchedule)) {
            return 0;
        }

        $overdueSchedules = $loan->repayment_schedules
            ->where('due_date', '<=', $arrearsLastSchedule->due_date);

        $total += $overdueSchedules->sum('principal') - $overdueSchedules->sum('principal_written_off_derived')
            - $overdueSchedules->sum('principal_repaid_derived');
        $total += $overdueSchedules->sum('interest') - $overdueSchedules->sum('interest_written_off_derived')
            - $overdueSchedules->sum('interest_repaid_derived') - $overdueSchedules->sum('interest_waived_derived');
        $total += $overdueSchedules->sum('fees') - $overdueSchedules->sum('fees_written_off_derived')
            - $overdueSchedules->sum('fees_repaid_derived') - $overdueSchedules->sum('fees_waived_derived');
        $total += $overdueSchedules->sum('penalties') - $overdueSchedules->sum('penalties_written_off_derived')
            - $overdueSchedules->sum('penalties_repaid_derived') - $overdueSchedules->sum('penalties_waived_derived');

        return max(0, $total);
    }
}

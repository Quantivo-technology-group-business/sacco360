<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoBudgetPeriod;
use Modules\Sacco\Entities\SaccoBudgetLine;

class BudgetService
{
    /**
     * Get budget vs actual for a given period.
     * Compares budget lines against actual journal entries.
     */
    public function getBudgetVsActual(SaccoBudgetPeriod $period): array
    {
        $lines = SaccoBudgetLine::with('account')
            ->where('sacco_budget_period_id', $period->id)
            ->get();

        $results = [];

        foreach ($lines as $line) {
            // Sum actual debits/credits from journal entries within period date range
            $actual = DB::table('journal_entries')
                ->where('chart_of_account_id', $line->chart_of_account_id)
                ->whereBetween('entry_date', [$period->start_date, $period->end_date])
                ->sum('amount');

            $variance = (float) $actual - (float) $line->budget_amount;
            $pct      = $line->budget_amount > 0
                ? round(($actual / $line->budget_amount) * 100, 1)
                : null;

            $results[] = [
                'account'        => $line->account,
                'budget'         => (float) $line->budget_amount,
                'actual'         => (float) $actual,
                'variance'       => $variance,
                'variance_pct'   => $pct,
                'q1_budget'      => (float) $line->q1_amount,
                'q2_budget'      => (float) $line->q2_amount,
                'q3_budget'      => (float) $line->q3_amount,
                'q4_budget'      => (float) $line->q4_amount,
            ];
        }

        return $results;
    }

    /**
     * Approve a budget period.
     */
    public function approve(SaccoBudgetPeriod $period, int $approvedById): void
    {
        $period->status       = 'approved';
        $period->approved_by_id = $approvedById;
        $period->approved_date  = Carbon::today();
        $period->save();
    }

    /**
     * Activate a budget period (set it as current).
     */
    public function activate(SaccoBudgetPeriod $period): void
    {
        // Close any currently active period for same fiscal year
        SaccoBudgetPeriod::where('fiscal_year', $period->fiscal_year)
            ->where('status', 'active')
            ->where('id', '!=', $period->id)
            ->update(['status' => 'closed']);

        $period->status = 'active';
        $period->save();
    }

    /**
     * Get current active budget period.
     */
    public function getActivePeriod(): ?SaccoBudgetPeriod
    {
        return SaccoBudgetPeriod::where('status', 'active')
            ->where('start_date', '<=', Carbon::today())
            ->where('end_date', '>=', Carbon::today())
            ->first();
    }

    /**
     * Copy budget lines from one period to another (for year rollover).
     */
    public function copyFromPeriod(SaccoBudgetPeriod $source, SaccoBudgetPeriod $target, int $createdById): int
    {
        $copied = 0;
        foreach ($source->lines as $line) {
            SaccoBudgetLine::firstOrCreate(
                [
                    'sacco_budget_period_id' => $target->id,
                    'chart_of_account_id'    => $line->chart_of_account_id,
                ],
                [
                    'budget_amount'  => $line->budget_amount,
                    'q1_amount'      => $line->q1_amount,
                    'q2_amount'      => $line->q2_amount,
                    'q3_amount'      => $line->q3_amount,
                    'q4_amount'      => $line->q4_amount,
                    'created_by_id'  => $createdById,
                    'notes'          => "Copied from {$source->name}",
                ]
            );
            $copied++;
        }

        return $copied;
    }
}

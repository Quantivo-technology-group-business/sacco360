<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoFinancialPeriod;

class PeriodEndService
{
    public function closePeriod(SaccoFinancialPeriod $period): SaccoFinancialPeriod
    {
        if ($period->status === 'closed') {
            throw new \RuntimeException('Period is already closed');
        }

        DB::transaction(function () use ($period) {
            $period->status = 'closing';
            $period->save();

            \Artisan::call('savings:process_interest');
            \Artisan::call('sacco:classify-loans', ['--date' => $period->end_date]);
            \Artisan::call('sacco:process-loan-accruals', ['--date' => $period->end_date]);

            $period->status = 'closed';
            $period->closed_by_id = Auth::id();
            $period->closed_at = now();
            $period->save();
        });

        return $period;
    }

    public function isPeriodLocked(string $date): bool
    {
        return SaccoFinancialPeriod::where('status', 'closed')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->exists();
    }

    public function createPeriod(string $name, string $startDate, string $endDate): SaccoFinancialPeriod
    {
        return SaccoFinancialPeriod::create([
            'name' => $name,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'open',
        ]);
    }
}

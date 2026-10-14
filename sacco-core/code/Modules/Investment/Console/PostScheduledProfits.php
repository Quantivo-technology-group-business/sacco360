<?php
namespace Modules\Investment\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Modules\Investment\Entities\InvestmentTransaction;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Services\InvestmentGLService;
use Modules\Investment\Services\ProfitCalculatorService;

/**
 * Runs daily (via scheduler) to post profit on all active investments
 * whose next_profit_posting_date is today or overdue.
 *
 * Register in InvestmentServiceProvider:
 *   $schedule->command('investment:post-profits')->daily();
 */
class PostScheduledProfits extends Command
{
    protected $signature   = 'investment:post-profits {--dry-run : Show what would be posted without saving}';
    protected $description = 'Post scheduled profit accruals for all active member investments';

    public function handle(ProfitCalculatorService $calc, InvestmentGLService $gl): int
    {
        $today   = Carbon::today();
        $dryRun  = $this->option('dry-run');
        $count   = 0;
        $total   = 0;

        $due = MemberInvestment::with('plan')
            ->where('status', 'active')
            ->where(function ($q) use ($today) {
                $q->whereNull('next_profit_posting_date')
                  ->orWhere('next_profit_posting_date', '<=', $today->format('Y-m-d'));
            })
            ->get();

        $this->info("Found {$due->count()} investment(s) due for profit posting.");

        foreach ($due as $inv) {
            $profit = $calc->postDueProfit($inv);
            if ($profit <= 0) continue;

            $this->line("  [{$inv->account_number}] {$inv->plan->name} — profit: {$profit}");

            if (!$dryRun) {
                $date   = $today->format('Y-m-d');
                $txnRef = uniqid('INV-AUTO-');

                InvestmentTransaction::create([
                    'member_investment_id' => $inv->id,
                    'created_by_id'        => null, // system
                    'transaction_type'     => 'profit_accrual',
                    'amount'               => $profit,
                    'date'                 => $date,
                    'reference'            => $txnRef,
                    'notes'                => 'Scheduled profit accrual',
                    'transaction_number'   => $txnRef,
                ]);

                $gl->postProfitAccrual($inv, $date, $profit);

                $plan = $inv->plan;
                $nextPost = match($plan->profit_posting_frequency) {
                    'monthly'     => Carbon::parse($date)->addMonth(),
                    'quarterly'   => Carbon::parse($date)->addMonths(3),
                    'semi_annual' => Carbon::parse($date)->addMonths(6),
                    'annual'      => Carbon::parse($date)->addYear(),
                    default       => $inv->maturity_date ? Carbon::parse($inv->maturity_date) : null,
                };

                $inv->increment('total_profit_accrued', $profit);
                $inv->update([
                    'last_profit_posting_date' => $date,
                    'next_profit_posting_date' => $nextPost?->format('Y-m-d'),
                ]);

                // Auto-mature if past maturity date
                if ($inv->maturity_date && Carbon::parse($inv->maturity_date)->lte($today)) {
                    $this->line("  → Investment {$inv->account_number} has matured.");
                }
            }

            $count++;
            $total += $profit;
        }

        $this->info($dryRun
            ? "DRY RUN — would post profit on {$count} investment(s), total: {$total}"
            : "Posted profit on {$count} investment(s). Total accrued: {$total}"
        );

        return 0;
    }
}

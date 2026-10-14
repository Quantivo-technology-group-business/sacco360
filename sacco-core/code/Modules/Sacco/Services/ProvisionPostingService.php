<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Sacco\Entities\SaccoProvisionRun;

class ProvisionPostingService
{
    public function calculateProvisionAdjustment(string $periodEnd): array
    {
        $caels = new CaelsClassificationService();
        $portfolio = $caels->classifyPortfolio($periodEnd);
        $requiredProvision = $portfolio['total_provision'];

        $allowanceAccountId = $this->getGlAccountId(1215);
        $previousAllowance = 0;
        if ($allowanceAccountId) {
            $previousAllowance = abs((float) DB::table('journal_entries')
                ->where('chart_of_account_id', $allowanceAccountId)
                ->where('date', '<=', $periodEnd)
                ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
                ->value('total'));
        }

        return [
            'total_provision' => $requiredProvision,
            'previous_allowance' => $previousAllowance,
            'adjustment_amount' => max(0, $requiredProvision - $previousAllowance),
            'portfolio' => $portfolio,
        ];
    }

    public function createProvisionRun(string $periodEnd): SaccoProvisionRun
    {
        $calc = $this->calculateProvisionAdjustment($periodEnd);

        return SaccoProvisionRun::create([
            'created_by_id' => Auth::id(),
            'period_end' => $periodEnd,
            'total_provision' => $calc['total_provision'],
            'previous_allowance' => $calc['previous_allowance'],
            'adjustment_amount' => $calc['adjustment_amount'],
            'status' => 'pending',
        ]);
    }

    public function postProvision(SaccoProvisionRun $run): bool
    {
        if ($run->status === 'posted') {
            return false;
        }

        $amount = $run->adjustment_amount;
        if ($amount <= 0) {
            $run->status = 'posted';
            $run->save();
            return true;
        }

        $expenseId = $this->getGlAccountId(5300);
        $allowanceId = $this->getGlAccountId(1215);

        if (!$expenseId || !$allowanceId) {
            throw new \RuntimeException('Provision GL accounts (5300, 1215) not configured');
        }

        $txnNumber = 'PROV-' . $run->id;
        $date = $run->period_end;
        $dateParts = explode('-', $date);
        $userId = Auth::id() ?? $run->created_by_id ?? 1;
        $branchId = DB::table('branches')->value('id') ?? 1;
        $currencyId = DB::table('currencies')->value('id') ?? 1;

        $debit = new JournalEntry();
        $debit->created_by_id = $userId;
        $debit->transaction_number = $txnNumber;
        $debit->branch_id = $branchId;
        $debit->currency_id = $currencyId;
        $debit->chart_of_account_id = $expenseId;
        $debit->transaction_type = 'loan_provision';
        $debit->date = $date;
        $debit->month = $dateParts[1];
        $debit->year = $dateParts[0];
        $debit->debit = $amount;
        $debit->reference = $run->id;
        $debit->save();

        $credit = new JournalEntry();
        $credit->created_by_id = $userId;
        $credit->transaction_number = $txnNumber;
        $credit->branch_id = $branchId;
        $credit->currency_id = $currencyId;
        $credit->chart_of_account_id = $allowanceId;
        $credit->transaction_type = 'loan_provision';
        $credit->date = $date;
        $credit->month = $dateParts[1];
        $credit->year = $dateParts[0];
        $credit->credit = $amount;
        $credit->reference = $run->id;
        $credit->save();

        $run->journal_reference = $txnNumber;
        $run->status = 'posted';
        $run->save();

        return true;
    }

    protected function getGlAccountId(int $glCode): ?int
    {
        $id = DB::table('chart_of_accounts')->where('gl_code', $glCode)->value('id');
        if ($id) {
            return $id;
        }

        if ($glCode === 1215 && Schema::hasTable('chart_of_accounts')) {
            return DB::table('chart_of_accounts')->insertGetId([
                'parent_id' => DB::table('chart_of_accounts')->where('gl_code', 1200)->value('id'),
                'name' => 'Allowance for Loan Losses',
                'gl_code' => 1215,
                'account_type' => 'asset',
                'sasra_category' => 'provision_allowance',
                'allow_manual' => 0,
                'active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return null;
    }
}

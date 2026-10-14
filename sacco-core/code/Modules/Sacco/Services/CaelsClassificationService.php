<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\LoanClassification;
use Modules\Sacco\Entities\SaccoProvisionRate;

class CaelsClassificationService
{
    protected static array $suspendClasses = ['Substandard', 'Doubtful', 'Loss'];

    public function classifyAllLoans(?string $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $rates = SaccoProvisionRate::orderBy('days_in_arrears_min')->get();
        $count = 0;

        $loans = DB::table('loans')
            ->whereIn('status', ['active', 'disbursed'])
            ->get();

        foreach ($loans as $loan) {
            $arrears = $this->getLoanArrears($loan->id, $asOfDate);
            $rate = $this->getRateForDays($rates, $arrears['days']);
            $provision = $arrears['outstanding'] * ($rate->provision_rate / 100);

            $previous = $loan->classification_status ?? 'Performing';
            $this->persistClassification($loan->id, $rate->classification, $arrears, $rate->provision_rate, $provision, $previous);
            $this->updateLoanRecord($loan->id, $rate->classification, $asOfDate);
            $count++;
        }

        return $count;
    }

    public function classifyPortfolio(string $asOfDate): array
    {
        $rates = SaccoProvisionRate::orderBy('days_in_arrears_min')->get();
        $classifications = [];
        $totalOutstanding = 0;
        $totalProvision = 0;
        $nplOutstanding = 0;
        $byClass = [];

        $loans = DB::table('loans')
            ->whereIn('status', ['active', 'disbursed'])
            ->get();

        foreach ($loans as $loan) {
            $arrears = $this->getLoanArrears($loan->id, $asOfDate);
            $outstanding = $this->getLoanOutstanding($loan->id, $asOfDate);
            $rate = $this->getRateForDays($rates, $arrears['days']);
            $provision = $outstanding * ($rate->provision_rate / 100);

            $totalOutstanding += $outstanding;
            $totalProvision += $provision;
            if ($arrears['days'] > 30) {
                $nplOutstanding += $outstanding;
            }

            $byClass[$rate->classification] = ($byClass[$rate->classification] ?? 0) + $outstanding;

            $classifications[] = [
                'loan_id' => $loan->id,
                'client_id' => $loan->client_id,
                'sector_code' => $loan->sector_code ?? 'unclassified',
                'days_overdue' => $arrears['days'],
                'classification' => $rate->classification,
                'outstanding' => $outstanding,
                'provision_rate' => $rate->provision_rate,
                'provision_amount' => $provision,
            ];
        }

        $totalPortfolio = (float) DB::table('loan_transactions')
            ->where('submitted_on', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');

        if ($totalPortfolio <= 0) {
            $totalPortfolio = $totalOutstanding;
        }

        return [
            'classifications' => $classifications,
            'by_classification' => $byClass,
            'total_outstanding' => $totalOutstanding,
            'total_provision' => $totalProvision,
            'npl_ratio' => $totalPortfolio > 0 ? round(($nplOutstanding / $totalPortfolio) * 100, 2) : 0,
            'provision_coverage' => $totalOutstanding > 0 ? round(($totalProvision / $totalOutstanding) * 100, 2) : 0,
            'as_of' => $asOfDate,
        ];
    }

    public function overrideClassification(int $loanId, string $classification, int $userId): void
    {
        $loan = DB::table('loans')->where('id', $loanId)->first();
        if (!$loan) {
            throw new \RuntimeException('Loan not found');
        }

        $rate = SaccoProvisionRate::where('classification', $classification)->first();
        $arrears = $this->getLoanArrears($loanId, date('Y-m-d'));
        $outstanding = $this->getLoanOutstanding($loanId, date('Y-m-d'));
        $provisionRate = $rate ? $rate->provision_rate : 100;
        $provision = $outstanding * ($provisionRate / 100);

        $this->persistClassification($loanId, $classification, $arrears, $provisionRate, $provision, $loan->classification_status ?? 'Performing', 'manual', $userId);
        $this->updateLoanRecord($loanId, $classification, date('Y-m-d'));
    }

    public function shouldSuspendInterest(string $classification): bool
    {
        return in_array($classification, self::$suspendClasses, true);
    }

    protected function getLoanArrears(int $loanId, string $asOfDate): array
    {
        $row = DB::table('loan_repayment_schedules')
            ->where('loan_id', $loanId)
            ->where('due_date', '<', $asOfDate)
            ->whereRaw('(principal + interest + fees + penalties - principal_repaid_derived - interest_repaid_derived - fees_repaid_derived - penalties_repaid_derived) > 0')
            ->selectRaw('MAX(DATEDIFF(?, due_date)) as days, SUM(principal + interest + fees + penalties - principal_repaid_derived - interest_repaid_derived - fees_repaid_derived - penalties_repaid_derived) as outstanding', [$asOfDate])
            ->first();

        return [
            'days' => (int) ($row->days ?? 0),
            'outstanding' => (float) ($row->outstanding ?? 0),
        ];
    }

    protected function getLoanOutstanding(int $loanId, string $asOfDate): float
    {
        return (float) DB::table('loan_transactions')
            ->where('loan_id', $loanId)
            ->where('submitted_on', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')
            ->value('total');
    }

    protected function persistClassification(int $loanId, string $classification, array $arrears, float $provisionRate, float $provision, ?string $previous, string $by = 'system', ?int $userId = null): void
    {
        if (!Schema::hasTable('loan_classifications')) {
            return;
        }

        LoanClassification::create([
            'loan_id' => $loanId,
            'classification' => $classification,
            'days_in_arrears' => $arrears['days'],
            'outstanding' => $arrears['outstanding'],
            'provision_rate' => $provisionRate,
            'provision_amount' => $provision,
            'previous_classification' => $previous,
            'classified_by' => $by,
            'user_id' => $userId,
            'classified_at' => date('Y-m-d'),
        ]);
    }

    protected function updateLoanRecord(int $loanId, string $classification, string $asOfDate): void
    {
        if (!Schema::hasColumn('loans', 'classification_status')) {
            return;
        }

        DB::table('loans')->where('id', $loanId)->update([
            'classification_status' => $classification,
            'classification_date' => $asOfDate,
            'interest_suspended' => $this->shouldSuspendInterest($classification) ? 1 : 0,
        ]);
    }

    protected function getRateForDays($rates, int $days)
    {
        foreach ($rates as $rate) {
            $max = $rate->days_in_arrears_max ?? PHP_INT_MAX;
            if ($days >= $rate->days_in_arrears_min && $days <= $max) {
                return $rate;
            }
        }

        return $rates->last() ?? (object) ['classification' => 'Loss', 'provision_rate' => 100];
    }
}

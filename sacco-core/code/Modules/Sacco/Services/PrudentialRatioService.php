<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrudentialRatioService
{
    public function calculateAll(string $asOfDate): array
    {
        $coreCapital = $this->getCoreCapital($asOfDate);
        $totalAssets = $this->getTotalAssets($asOfDate);
        $totalDeposits = $this->getTotalDeposits($asOfDate);
        $institutionalCapital = $this->getInstitutionalCapital($asOfDate);
        $liquidAssets = $this->getLiquidAssets($asOfDate);
        $nonEarningAssets = $this->getNonEarningAssets($asOfDate);
        $externalBorrowing = $this->getExternalBorrowing($asOfDate);

        $carAssets = $totalAssets > 0 ? ($coreCapital / $totalAssets) * 100 : 0;
        $carDeposits = $totalDeposits > 0 ? ($coreCapital / $totalDeposits) * 100 : 0;
        $instCapitalRatio = $totalAssets > 0 ? ($institutionalCapital / $totalAssets) * 100 : 0;
        $liquidityRatio = $totalDeposits > 0 ? ($liquidAssets / $totalDeposits) * 100 : 0;
        $nonEarningRatio = $totalAssets > 0 ? ($nonEarningAssets / $totalAssets) * 100 : 0;
        $borrowingRatio = $totalAssets > 0 ? ($externalBorrowing / $totalAssets) * 100 : 0;

        $ratios = [
            'core_capital_absolute' => $this->buildRatio(
                'Core Capital (Absolute)',
                $coreCapital,
                config('sacco.core_capital_min', 10000000),
                'min',
                'KES'
            ),
            'core_capital_to_assets' => $this->buildRatio(
                'Core Capital / Total Assets',
                round($carAssets, 2),
                config('sacco.car_assets_min', 10),
                'min',
                '%'
            ),
            'core_capital_to_deposits' => $this->buildRatio(
                'Core Capital / Total Deposits',
                round($carDeposits, 2),
                config('sacco.car_deposits_min', 8),
                'min',
                '%'
            ),
            'institutional_capital_to_assets' => $this->buildRatio(
                'Institutional Capital / Total Assets',
                round($instCapitalRatio, 2),
                config('sacco.inst_capital_min', 8),
                'min',
                '%'
            ),
            'liquidity_ratio' => $this->buildRatio(
                'Liquidity Ratio',
                round($liquidityRatio, 2),
                config('sacco.liquidity_ratio_minimum', 15),
                'min',
                '%'
            ),
            'non_earning_assets' => $this->buildRatio(
                'Non-Earning Assets / Total Assets',
                round($nonEarningRatio, 2),
                config('sacco.non_earning_max', 10),
                'max',
                '%'
            ),
            'external_borrowing' => $this->buildRatio(
                'External Borrowing / Total Assets',
                round($borrowingRatio, 2),
                config('sacco.external_borrowing_max', 25),
                'max',
                '%'
            ),
        ];

        $memberExposures = $this->getMemberExposureBreaches($asOfDate, $coreCapital);
        $ratios['single_member_exposure'] = [
            'label' => 'Single Member Exposure / Core Capital',
            'breaches' => $memberExposures,
            'threshold' => config('sacco.single_member_exposure_max', 10),
            'status' => empty($memberExposures) ? 'green' : 'red',
            'compliant' => empty($memberExposures),
        ];

        $ratios['summary'] = [
            'core_capital' => $coreCapital,
            'institutional_capital' => $institutionalCapital,
            'total_assets' => $totalAssets,
            'total_deposits' => $totalDeposits,
            'liquid_assets' => $liquidAssets,
            'non_earning_assets' => $nonEarningAssets,
            'external_borrowing' => $externalBorrowing,
            'as_of' => $asOfDate,
            'all_compliant' => collect($ratios)->every(fn ($r) => ($r['compliant'] ?? true)),
        ];

        return $ratios;
    }

    public function getTrafficLightStatus(float $value, float $threshold, string $direction = 'min'): string
    {
        if ($direction === 'min') {
            if ($value >= $threshold * 1.1) {
                return 'green';
            }
            if ($value >= $threshold) {
                return 'amber';
            }
            return 'red';
        }

        if ($value <= $threshold * 0.9) {
            return 'green';
        }
        if ($value <= $threshold) {
            return 'amber';
        }
        return 'red';
    }

    protected function buildRatio(string $label, float $value, float $threshold, string $direction, string $unit): array
    {
        $compliant = $direction === 'min' ? $value >= $threshold : $value <= $threshold;

        return [
            'label' => $label,
            'value' => $value,
            'threshold' => $threshold,
            'unit' => $unit,
            'direction' => $direction,
            'status' => $this->getTrafficLightStatus($value, $threshold, $direction),
            'compliant' => $compliant,
        ];
    }

    public function getCoreCapital(string $asOfDate): float
    {
        $shareCapital = $this->getAccountBalanceByGlCodes([2210, 2220], 'liability', $asOfDate);
        $reserves = $this->getAccountBalanceByGlCodes([3100, 3200], 'equity', $asOfDate);
        $intangibles = $this->getAccountBalanceByCategory('intangible', $asOfDate);

        return max(0, $shareCapital + $reserves - $intangibles);
    }

    public function getInstitutionalCapital(string $asOfDate): float
    {
        $totalEquity = $this->getAccountBalanceByType('equity', $asOfDate);
        $memberShares = $this->getAccountBalanceByGlCodes([2210, 2220], 'liability', $asOfDate);

        return max(0, $totalEquity - $memberShares);
    }

    public function getTotalAssets(string $asOfDate): float
    {
        return $this->getAccountBalanceByType('asset', $asOfDate);
    }

    public function getTotalDeposits(string $asOfDate): float
    {
        $fromGl = $this->getAccountBalanceByGlCodes([2110, 2120], 'liability', $asOfDate);
        if ($fromGl > 0) {
            return $fromGl;
        }

        return (float) DB::table('savings_transactions')
            ->where('savings_transactions.submitted_on', '<=', $asOfDate)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total');
    }

    public function getLiquidAssets(string $asOfDate): float
    {
        $fromCategory = $this->getAccountBalanceByCategory('liquid_asset', $asOfDate);
        if ($fromCategory > 0) {
            return $fromCategory;
        }

        return (float) DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->where(function ($q) {
                $q->whereIn('chart_of_accounts.gl_code', [1110, 1120])
                    ->orWhere('chart_of_accounts.name', 'like', '%cash%')
                    ->orWhere('chart_of_accounts.name', 'like', '%bank%')
                    ->orWhere('chart_of_accounts.sasra_category', 'liquid_asset');
            })
            ->selectRaw('COALESCE(SUM(journal_entries.debit) - SUM(journal_entries.credit), 0) as total')
            ->value('total');
    }

    public function getNonEarningAssets(string $asOfDate): float
    {
        return $this->getAccountBalanceByCategory('non_earning', $asOfDate)
            + $this->getAccountBalanceByCategory('land_building', $asOfDate);
    }

    public function getExternalBorrowing(string $asOfDate): float
    {
        if (Schema::hasTable('sacco_borrowings')) {
            $fromTable = (float) DB::table('sacco_borrowings')
                ->where('status', 'active')
                ->where('disbursement_date', '<=', $asOfDate)
                ->sum('outstanding');
            if ($fromTable > 0) {
                return $fromTable;
            }
        }

        return $this->getAccountBalanceByCategory('external_borrowing', $asOfDate);
    }

    protected function getMemberExposureBreaches(string $asOfDate, float $coreCapital): array
    {
        if ($coreCapital <= 0) {
            return [];
        }

        $threshold = config('sacco.single_member_exposure_max', 10);
        $breaches = [];

        $exposures = DB::table('loans')
            ->join('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->join('clients', 'clients.id', '=', 'loans.client_id')
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->where('loan_transactions.submitted_on', '<=', $asOfDate)
            ->selectRaw("clients.id, CONCAT(clients.first_name,' ',clients.last_name) as name, COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as exposure")
            ->groupBy('clients.id', 'clients.first_name', 'clients.last_name')
            ->having('exposure', '>', 0)
            ->get();

        foreach ($exposures as $row) {
            $ratio = ($row->exposure / $coreCapital) * 100;
            if ($ratio > $threshold) {
                $breaches[] = [
                    'client_id' => $row->id,
                    'name' => $row->name,
                    'exposure' => $row->exposure,
                    'ratio' => round($ratio, 2),
                ];
            }
        }

        return $breaches;
    }

    protected function getAccountBalanceByType(string $type, string $asOfDate): float
    {
        return (float) DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->where('chart_of_accounts.account_type', $type)
            ->selectRaw($type === 'asset'
                ? 'COALESCE(SUM(journal_entries.debit) - SUM(journal_entries.credit), 0) as total'
                : 'COALESCE(SUM(journal_entries.credit) - SUM(journal_entries.debit), 0) as total')
            ->value('total');
    }

    protected function getAccountBalanceByGlCodes(array $glCodes, string $type, string $asOfDate): float
    {
        return (float) DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->whereIn('chart_of_accounts.gl_code', $glCodes)
            ->selectRaw($type === 'asset'
                ? 'COALESCE(SUM(journal_entries.debit) - SUM(journal_entries.credit), 0) as total'
                : 'COALESCE(SUM(journal_entries.credit) - SUM(journal_entries.debit), 0) as total')
            ->value('total');
    }

    protected function getAccountBalanceByCategory(string $category, string $asOfDate): float
    {
        if (!Schema::hasColumn('chart_of_accounts', 'sasra_category')) {
            return 0;
        }

        return (float) DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->where('chart_of_accounts.sasra_category', $category)
            ->selectRaw('COALESCE(SUM(journal_entries.debit) - SUM(journal_entries.credit), 0) as total')
            ->value('total');
    }
}

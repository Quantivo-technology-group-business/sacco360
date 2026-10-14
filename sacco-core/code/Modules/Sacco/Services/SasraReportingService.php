<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;

class SasraReportingService
{
    protected PrudentialRatioService $prudential;
    protected CaelsClassificationService $caels;

    public function __construct()
    {
        $this->prudential = new PrudentialRatioService();
        $this->caels = new CaelsClassificationService();
    }

    public function generateAll(string $asOfDate): array
    {
        return [
            'form_1_006' => $this->form1StatementOfFinancialPosition($asOfDate),
            'form_2_007' => $this->form2StatementOfComprehensiveIncome($asOfDate),
            'form_3_001' => $this->form3CapitalAdequacy($asOfDate),
            'form_4_002' => $this->form4Liquidity($asOfDate),
            'form_5_004' => $this->form5AssetQuality($asOfDate),
            'form_6' => $this->form6Membership($asOfDate),
            'form_7_003' => $this->form7DepositReturn($asOfDate),
            'form_8' => $this->form8InsiderLending($asOfDate),
            'form_5_investment' => $this->form5InvestmentReturn($asOfDate),
            'form_8_disclosures' => $this->form8OtherDisclosures($asOfDate),
            'form_4b' => $this->form4bSectorialLending($asOfDate),
            'prudential_ratios' => $this->prudentialRatios($asOfDate),
            'form_1' => $this->form1StatementOfFinancialPosition($asOfDate),
            'form_2' => $this->form2StatementOfComprehensiveIncome($asOfDate),
            'form_3' => $this->form3CapitalAdequacy($asOfDate),
            'form_4' => $this->form4Liquidity($asOfDate),
            'form_5' => $this->form5AssetQuality($asOfDate),
            'form_7' => $this->form7DepositReturn($asOfDate),
        ];
    }

    public function form3CapitalAdequacy(string $asOfDate): array
    {
        $ratios = $this->prudential->calculateAll($asOfDate);
        $summary = $ratios['summary'];

        return [
            'core_capital' => $summary['core_capital'],
            'institutional_capital' => $summary['institutional_capital'],
            'total_assets' => $summary['total_assets'],
            'total_deposits' => $summary['total_deposits'],
            'capital_adequacy_ratio' => $ratios['core_capital_to_assets']['value'],
            'capital_to_deposits_ratio' => $ratios['core_capital_to_deposits']['value'],
            'core_capital_absolute' => $ratios['core_capital_absolute']['value'],
            'compliant' => $ratios['core_capital_to_assets']['compliant']
                && $ratios['core_capital_to_deposits']['compliant']
                && $ratios['core_capital_absolute']['compliant'],
            'status' => $ratios['core_capital_to_assets']['status'],
            'as_of' => $asOfDate,
        ];
    }

    public function form4Liquidity(string $asOfDate): array
    {
        $ratios = $this->prudential->calculateAll($asOfDate);
        $summary = $ratios['summary'];

        return [
            'liquid_assets' => $summary['liquid_assets'],
            'total_deposits' => $summary['total_deposits'],
            'liquidity_ratio' => $ratios['liquidity_ratio']['value'],
            'minimum_required' => $ratios['liquidity_ratio']['threshold'],
            'compliant' => $ratios['liquidity_ratio']['compliant'],
            'status' => $ratios['liquidity_ratio']['status'],
            'as_of' => $asOfDate,
        ];
    }

    public function form5AssetQuality(string $asOfDate): array
    {
        return $this->caels->classifyPortfolio($asOfDate);
    }

    public function form1StatementOfFinancialPosition(string $asOfDate): array
    {
        $lines = $this->getMappedLines('form_1_006', $asOfDate);
        if (!empty($lines)) {
            return ['lines' => $lines, 'as_of' => $asOfDate];
        }

        $assets = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->where('chart_of_accounts.account_type', 'asset')
            ->selectRaw('chart_of_accounts.name, chart_of_accounts.gl_code, SUM(journal_entries.debit) - SUM(journal_entries.credit) as balance')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.gl_code')
            ->get();

        $liabilities = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->whereIn('chart_of_accounts.account_type', ['liability', 'equity'])
            ->selectRaw('chart_of_accounts.name, chart_of_accounts.gl_code, SUM(journal_entries.credit) - SUM(journal_entries.debit) as balance')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.gl_code')
            ->get();

        return ['assets' => $assets, 'liabilities' => $liabilities, 'as_of' => $asOfDate];
    }

    public function form2StatementOfComprehensiveIncome(string $asOfDate): array
    {
        $yearStart = date('Y-01-01', strtotime($asOfDate));
        $lines = $this->getMappedLines('form_2_007', $asOfDate, $yearStart);

        $income = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->whereBetween('journal_entries.date', [$yearStart, $asOfDate])
            ->where('chart_of_accounts.account_type', 'income')
            ->selectRaw('SUM(journal_entries.credit) - SUM(journal_entries.debit) as total')
            ->value('total') ?? 0;

        $expenses = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->whereBetween('journal_entries.date', [$yearStart, $asOfDate])
            ->where('chart_of_accounts.account_type', 'expense')
            ->selectRaw('SUM(journal_entries.debit) - SUM(journal_entries.credit) as total')
            ->value('total') ?? 0;

        return [
            'lines' => $lines,
            'total_income' => (float) $income,
            'total_expenses' => (float) $expenses,
            'net_income' => (float) $income - (float) $expenses,
            'period' => $yearStart . ' to ' . $asOfDate,
        ];
    }

    public function form6Membership(string $asOfDate): array
    {
        return [
            'total_members' => DB::table('clients')->where('status', 'active')->count(),
            'new_members_ytd' => DB::table('clients')
                ->where('status', 'active')
                ->where('created_date', '>=', date('Y-01-01', strtotime($asOfDate)))
                ->count(),
            'dormant_members' => DB::table('clients')->where('member_status_detail', 'dormant')->count(),
            'defaulting_members' => DB::table('clients')->where('member_status_detail', 'defaulting')->count(),
        ];
    }

    public function form7DepositReturn(string $asOfDate): array
    {
        $byProduct = DB::table('savings')
            ->join('savings_products', 'savings_products.id', '=', 'savings.savings_product_id')
            ->leftJoin('savings_transactions', 'savings_transactions.savings_id', '=', 'savings.id')
            ->where('savings.status', 'active')
            ->where('savings_transactions.submitted_on', '<=', $asOfDate)
            ->selectRaw('savings_products.name as product, savings_products.savings_category, COALESCE(SUM(savings_transactions.credit)-SUM(savings_transactions.debit),0) as balance')
            ->groupBy('savings_products.id', 'savings_products.name', 'savings_products.savings_category')
            ->get();

        $top = DB::table('savings')
            ->leftJoin('savings_transactions', 'savings_transactions.savings_id', '=', 'savings.id')
            ->join('clients', 'clients.id', '=', 'savings.client_id')
            ->where('savings.status', 'active')
            ->where(function ($q) use ($asOfDate) {
                $q->whereNull('savings_transactions.submitted_on')
                    ->orWhere('savings_transactions.submitted_on', '<=', $asOfDate);
            })
            ->selectRaw("clients.id, CONCAT(clients.first_name,' ',clients.last_name) as name, COALESCE(SUM(savings_transactions.credit)-SUM(savings_transactions.debit),0) as balance")
            ->groupBy('clients.id', 'clients.first_name', 'clients.last_name')
            ->orderByDesc('balance')
            ->limit(10)
            ->get();

        $prudential = $this->prudential->calculateAll($asOfDate);

        $bosaTotal = $byProduct->where('savings_category', 'compulsory')->sum('balance');
        $fosaTotal = $byProduct->where('savings_category', 'voluntary')->sum('balance');

        return [
            'by_product' => $byProduct,
            'top_depositors' => $top,
            'total_deposits' => $prudential['summary']['total_deposits'],
            'bosa_total' => $bosaTotal,
            'fosa_total' => $fosaTotal,
            'non_withdrawable_deposits' => $bosaTotal,
            'withdrawable_deposits' => $fosaTotal,
            'as_of' => $asOfDate,
        ];
    }

    public function form8InsiderLending(string $asOfDate): array
    {
        // Insider lending: loans where the borrower (client) is also a staff member (user).
        // Join loans → clients to get borrower details, then inner-join users on matching email
        // to identify only clients who have a system user account (staff).
        $staffLoans = DB::table('loans')
            ->join('clients', 'clients.id', '=', 'loans.client_id')
            ->join('users', 'users.email', '=', 'clients.email')
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->selectRaw('loans.id, loans.client_id, loans.account_number, loans.principal,
                         loans.total_outstanding_derived,
                         clients.first_name, clients.last_name, clients.member_number,
                         users.id as user_id')
            ->get();

        return [
            'staff_loans' => $staffLoans,
            'as_of'       => $asOfDate,
        ];
    }

    public function form5InvestmentReturn(string $asOfDate): array
    {
        $investments = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->where('journal_entries.date', '<=', $asOfDate)
            ->where(function ($q) {
                $q->where('chart_of_accounts.sasra_category', 'investment')
                    ->orWhere('chart_of_accounts.gl_code', 4300)
                    ->orWhere('chart_of_accounts.name', 'like', '%investment%');
            })
            ->selectRaw('chart_of_accounts.name, chart_of_accounts.gl_code, SUM(journal_entries.debit) - SUM(journal_entries.credit) as balance')
            ->groupBy('chart_of_accounts.id', 'chart_of_accounts.name', 'chart_of_accounts.gl_code')
            ->get();

        $income = DB::table('journal_entries')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'journal_entries.chart_of_account_id')
            ->whereBetween('journal_entries.date', [date('Y-01-01', strtotime($asOfDate)), $asOfDate])
            ->where('chart_of_accounts.gl_code', 4300)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as total')
            ->value('total');

        return [
            'investments' => $investments,
            'investment_income_ytd' => (float) $income,
            'as_of' => $asOfDate,
        ];
    }

    public function form8OtherDisclosures(string $asOfDate): array
    {
        $templates = DB::table('sacco_report_templates')
            ->where('form_type', 'disclosure')
            ->where('active', 1)
            ->get();

        return [
            'templates' => $templates,
            'disclosures' => [],
            'as_of' => $asOfDate,
        ];
    }

    public function form4bSectorialLending(string $asOfDate): array
    {
        $sectors = DB::table('loans')
            ->join('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->where('loan_transactions.submitted_on', '<=', $asOfDate)
            ->selectRaw("COALESCE(loans.sector_code, 'unclassified') as sector, COUNT(DISTINCT loans.id) as loan_count, COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as outstanding")
            ->groupBy('loans.sector_code')
            ->get();

        return ['sectors' => $sectors, 'as_of' => $asOfDate];
    }

    public function prudentialRatios(string $asOfDate): array
    {
        return $this->prudential->calculateAll($asOfDate);
    }

    protected function getMappedLines(string $form, string $asOfDate, ?string $startDate = null): array
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('sacco_coa_line_mappings')) {
            return [];
        }

        $mappings = DB::table('sacco_coa_line_mappings')
            ->join('chart_of_accounts', 'chart_of_accounts.id', '=', 'sacco_coa_line_mappings.chart_of_account_id')
            ->where('sacco_coa_line_mappings.sasra_form', $form)
            ->get();

        $lines = [];
        foreach ($mappings as $map) {
            $query = DB::table('journal_entries')
                ->where('chart_of_account_id', $map->chart_of_account_id);

            if ($startDate) {
                $query->whereBetween('date', [$startDate, $asOfDate]);
            } else {
                $query->where('date', '<=', $asOfDate);
            }

            $balance = $query->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as total')->value('total');

            $lines[] = [
                'line_code' => $map->line_code,
                'description' => $map->line_description,
                'balance' => (float) $balance,
            ];
        }

        return $lines;
    }
}

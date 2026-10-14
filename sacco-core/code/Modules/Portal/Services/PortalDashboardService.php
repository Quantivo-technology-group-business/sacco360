<?php

namespace Modules\Portal\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanRepaymentSchedule;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Services\LoanDashboardMetrics;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Share\Entities\Share;
use Modules\Investment\Entities\MemberInvestment;
use Modules\Investment\Entities\InvestmentPlan;

class PortalDashboardService
{
    public function getDashboardData(int $clientId): array
    {
        $client = Client::with('branch')->find($clientId);
        $member360 = $this->getMember360Summary($clientId);

        $loans = Loan::with('loan_product', 'repayment_schedules')
            ->where('client_id', $clientId)
            ->orderByDesc('created_at')
            ->get();

        $activeLoans = $loans->where('status', 'active');
        $loanStats = $this->buildLoanStats($loans, $activeLoans);
        $savingsStats = $this->buildSavingsStats($clientId);
        $shareStats = $this->buildShareStats($clientId);

        return [
            'client' => $client,
            'member360' => $member360,
            'loan_stats' => $loanStats,
            'savings_stats' => $savingsStats,
            'share_stats' => $shareStats,
            'upcoming_repayments' => $this->getUpcomingRepayments($clientId),
            'overdue_repayments' => $this->getOverdueRepayments($clientId),
            'active_loans' => $activeLoans->take(5),
            'recent_loan_transactions' => $this->getRecentLoanTransactions($clientId),
            'recent_savings_transactions' => $this->getRecentSavingsTransactions($clientId),
            'repayment_chart' => $this->getRepaymentChartData($clientId),
            'savings_chart' => $this->getSavingsChartData($clientId),
            'loan_status_counts' => $this->getLoanStatusCounts($clientId),
            'net_position'      => $member360['net_position'] ?? $this->calculateNetPosition($loanStats, $savingsStats, $shareStats),
            'investment_stats'  => $this->buildInvestmentStats($clientId),
            'available_plans'   => InvestmentPlan::where('active', 1)->where('open_to_members', 1)->orderByDesc('return_rate')->get(),
            'total_assets' => $member360['total_assets'] ?? ($savingsStats['total_balance'] + $shareStats['share_value']),
        ];
    }

    protected function getMember360Summary(int $clientId): ?array
    {
        if (!function_exists('sacco_member_summary')) {
            return null;
        }

        return sacco_member_summary($clientId);
    }

    protected function buildLoanStats($loans, $activeLoans): array
    {
        $totalOutstanding = 0;
        $totalArrears = 0;
        $totalRepaid = LoanTransaction::where('reversed', 0)
            ->whereIn('loan_transaction_type_id', [2, 5, 8])
            ->whereIn('loan_id', $loans->pluck('id'))
            ->sum('amount');

        foreach ($activeLoans as $loan) {
            $totalOutstanding += LoanDashboardMetrics::calculateLoanOutstanding($loan);
            $totalArrears += LoanDashboardMetrics::calculateArrearsAmount($loan);
        }

        return [
            'total_loans' => $loans->count(),
            'active_count' => $activeLoans->count(),
            'pending_count' => $loans->whereIn('status', ['pending', 'submitted', 'approved'])->count(),
            'closed_count' => $loans->where('status', 'closed')->count(),
            'disbursed_amount' => $activeLoans->sum('principal'),
            'total_outstanding' => $totalOutstanding,
            'total_arrears' => $totalArrears,
            'total_repaid' => $totalRepaid,
        ];
    }

    protected function buildSavingsStats(int $clientId): array
    {
        $accounts = Savings::where('client_id', $clientId)->get();

        return [
            'total_accounts' => $accounts->count(),
            'active_accounts' => $accounts->where('status', 'active')->count(),
            'total_balance' => $accounts->sum('balance_derived'),
            'total_deposits' => $accounts->sum('total_deposits_derived'),
            'total_withdrawals' => $accounts->sum('total_withdrawals_derived'),
            'total_interest' => $accounts->sum('total_interest_posted_derived'),
        ];
    }

    protected function buildShareStats(int $clientId): array
    {
        if (!class_exists(Share::class)) {
            return ['total_accounts' => 0, 'total_shares' => 0, 'share_value' => 0];
        }

        $shares = Share::with('share_product')->where('client_id', $clientId)->get();
        $shareValue = 0;
        foreach ($shares as $share) {
            $price = $share->share_product->nominal_price ?? $share->nominal_price ?? 0;
            $shareValue += ($share->total_shares ?? 0) * $price;
        }

        return [
            'total_accounts' => $shares->count(),
            'total_shares' => $shares->sum('total_shares'),
            'share_value' => $shareValue,
        ];
    }

    protected function calculateNetPosition(array $loanStats, array $savingsStats, array $shareStats): float
    {
        return $savingsStats['total_balance'] + $shareStats['share_value'] - $loanStats['total_outstanding'];
    }

    protected function getUpcomingRepayments(int $clientId, int $limit = 5)
    {
        return LoanRepaymentSchedule::join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loans.client_id', $clientId)
            ->where('loans.status', 'active')
            ->where('loan_repayment_schedules.due_date', '>=', Carbon::today()->format('Y-m-d'))
            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->selectRaw('loan_repayment_schedules.*, loans.id as loan_id, loan_products.name as loan_product')
            ->orderBy('loan_repayment_schedules.due_date')
            ->limit($limit)
            ->get();
    }

    protected function getOverdueRepayments(int $clientId, int $limit = 5)
    {
        return LoanRepaymentSchedule::join('loans', 'loans.id', '=', 'loan_repayment_schedules.loan_id')
            ->join('loan_products', 'loan_products.id', '=', 'loans.loan_product_id')
            ->where('loans.client_id', $clientId)
            ->where('loans.status', 'active')
            ->where('loan_repayment_schedules.due_date', '<', Carbon::today()->format('Y-m-d'))
            ->where('loan_repayment_schedules.total_due', '>', 0)
            ->selectRaw('loan_repayment_schedules.*, loans.id as loan_id, loan_products.name as loan_product')
            ->orderBy('loan_repayment_schedules.due_date')
            ->limit($limit)
            ->get();
    }

    protected function getRecentLoanTransactions(int $clientId, int $limit = 8)
    {
        return LoanTransaction::join('loans', 'loans.id', '=', 'loan_transactions.loan_id')
            ->where('loans.client_id', $clientId)
            ->where('loan_transactions.reversed', 0)
            ->selectRaw('loan_transactions.*, loans.id as loan_id')
            ->orderByDesc('loan_transactions.submitted_on')
            ->limit($limit)
            ->get();
    }

    protected function getRecentSavingsTransactions(int $clientId, int $limit = 8)
    {
        return SavingsTransaction::join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
            ->where('savings.client_id', $clientId)
            ->where('savings_transactions.reversed', 0)
            ->selectRaw('savings_transactions.*, savings.id as savings_id')
            ->orderByDesc('savings_transactions.submitted_on')
            ->limit($limit)
            ->get();
    }

    protected function getRepaymentChartData(int $clientId): array
    {
        $labels = [];
        $amounts = [];
        $startDate = Carbon::today()->subMonthsNoOverflow(5)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $startDate->copy()->startOfMonth();
            $monthEnd = $startDate->copy()->endOfMonth();
            $labels[] = $startDate->format('M Y');

            $amounts[] = round(LoanTransaction::join('loans', 'loans.id', '=', 'loan_transactions.loan_id')
                ->where('loans.client_id', $clientId)
                ->where('loan_transactions.reversed', 0)
                ->whereIn('loan_transaction_type_id', [2, 5, 8])
                ->whereBetween('loan_transactions.submitted_on', [$monthStart, $monthEnd])
                ->sum('loan_transactions.amount'), 2);

            $startDate->addMonthNoOverflow();
        }

        return compact('labels', 'amounts');
    }

    protected function getSavingsChartData(int $clientId): array
    {
        $labels = [];
        $deposits = [];
        $withdrawals = [];
        $startDate = Carbon::today()->subMonthsNoOverflow(5)->startOfMonth();

        for ($i = 0; $i < 6; $i++) {
            $monthStart = $startDate->copy()->startOfMonth();
            $monthEnd = $startDate->copy()->endOfMonth();
            $labels[] = $startDate->format('M Y');

            $deposits[] = round(SavingsTransaction::join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
                ->where('savings.client_id', $clientId)
                ->where('savings_transactions.reversed', 0)
                ->whereBetween('savings_transactions.submitted_on', [$monthStart, $monthEnd])
                ->sum('savings_transactions.credit'), 2);

            $withdrawals[] = round(SavingsTransaction::join('savings', 'savings.id', '=', 'savings_transactions.savings_id')
                ->where('savings.client_id', $clientId)
                ->where('savings_transactions.reversed', 0)
                ->whereBetween('savings_transactions.submitted_on', [$monthStart, $monthEnd])
                ->sum('savings_transactions.debit'), 2);

            $startDate->addMonthNoOverflow();
        }

        return compact('labels', 'deposits', 'withdrawals');
    }

    protected function getLoanStatusCounts(int $clientId): array
    {
        return Loan::where('client_id', $clientId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    protected function buildInvestmentStats(int $clientId): array
    {
        $investments = MemberInvestment::with('plan')
            ->where('client_id', $clientId)
            ->whereIn('status', ['active', 'matured', 'pending'])
            ->get();

        $active    = $investments->where('status', 'active');
        $profitDue = $active->sum(fn($i) => max(0, (float)$i->total_profit_accrued - (float)$i->total_profit_paid));

        return [
            'count'                => $active->count(),
            'total_invested'       => $investments->sum('principal_amount'),
            'total_profit_accrued' => $investments->sum('total_profit_accrued'),
            'total_profit_paid'    => $investments->sum('total_profit_paid'),
            'profit_due'           => $profitDue,
            'current_value'        => $active->sum(fn($i) => (float)$i->principal_amount + (float)$i->total_profit_accrued),
            'investments'          => $investments,
            'pending_count'        => $investments->where('status','pending')->count(),
            'maturing_soon'        => $active->filter(fn($i) =>
                $i->maturity_date && $i->maturity_date->diffInDays(now()) <= 30
            )->count(),
        ];
    }

}

<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaccoModelDataTableSeeder extends Seeder
{
    protected const SEED_MARKER = 'SACCO-MODEL-SEED';

    public function run()
    {
        if (DB::table('clients')->where('external_id', self::SEED_MARKER)->exists()) {
            return;
        }

        $branchId = DB::table('branches')->value('id') ?? 1;
        $currencyId = DB::table('currencies')->value('id') ?? 1;
        $userId = DB::table('users')->value('id') ?? 1;
        $fundId = DB::table('funds')->value('id') ?? DB::table('funds')->insertGetId(['name' => 'SACCO Operating Fund']);
        $strategyId = DB::table('loan_transaction_processing_strategies')->value('id') ?? 1;
        $loanPurposeId = DB::table('loan_purposes')->value('id') ?? 1;

        $coa = $this->coaIds();
        if (empty($coa)) {
            return;
        }

        $now = now();
        $today = date('Y-m-d');
        $yearStart = date('Y-01-01');

        $products = $this->seedProducts($coa, $currencyId, $userId, $fundId, $strategyId, $now);
        $members = $this->seedMembers($branchId, $userId, $today, $now);
        $accounts = $this->seedAccounts($members, $products, $branchId, $currencyId, $userId, $fundId, $strategyId, $loanPurposeId, $today, $now);
        $this->seedTransactions($accounts, $members, $branchId, $currencyId, $userId, $today, $now);
        $this->seedJournalEntries($coa, $branchId, $currencyId, $userId, $yearStart, $today, $now);
        $this->seedSaccoRecords($accounts, $members, $products, $branchId, $userId, $today, $now);
    }

    protected function coaIds(): array
    {
        $codes = [1110, 1120, 1210, 2110, 2120, 2210, 3100, 4100, 4200, 5100, 5200];
        $map = [];
        foreach ($codes as $code) {
            $id = DB::table('chart_of_accounts')->where('gl_code', $code)->value('id');
            if ($id) {
                $map[$code] = $id;
            }
        }
        return $map;
    }

    protected function seedProducts(array $coa, int $currencyId, int $userId, int $fundId, int $strategyId, $now): array
    {
        $loanCoa = [
            'fund_source_chart_of_account_id' => $coa[1120],
            'loan_portfolio_chart_of_account_id' => $coa[1210],
            'interest_receivable_chart_of_account_id' => $coa[1210],
            'income_from_interest_chart_of_account_id' => $coa[4100],
            'income_from_fees_chart_of_account_id' => $coa[4200],
        ];

        $bosaProductId = DB::table('savings_products')->insertGetId(array_merge([
            'created_by_id' => $userId,
            'currency_id' => $currencyId,
            'savings_reference_chart_of_account_id' => $coa[2110],
            'savings_control_chart_of_account_id' => $coa[1120],
            'interest_on_savings_chart_of_account_id' => $coa[5100],
            'income_from_fees_chart_of_account_id' => $coa[4200],
            'name' => 'BOSA Ordinary Savings',
            'short_name' => 'BOSA',
            'description' => 'Back Office Savings Account (BOSA)',
            'decimals' => 2,
            'savings_category' => 'compulsory',
            'account_product_type' => 'savings',
            'auto_create' => 0,
            'minimum_interest_rate' => 3,
            'default_interest_rate' => 5,
            'maximum_interest_rate' => 8,
            'interest_rate_type' => 'year',
            'compounding_period' => 'monthly',
            'interest_posting_period_type' => 'monthly',
            'interest_calculation_type' => 'daily_balance',
            'automatic_opening_balance' => 0,
            'minimum_balance_for_interest_calculation' => 100,
            'lockin_period' => 0,
            'lockin_type' => 'days',
            'minimum_balance' => 0,
            'allow_overdraft' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->savingsProductDefaults()));

        $fosaProductId = DB::table('savings_products')->insertGetId(array_merge([
            'created_by_id' => $userId,
            'currency_id' => $currencyId,
            'savings_reference_chart_of_account_id' => $coa[2120],
            'savings_control_chart_of_account_id' => $coa[1120],
            'interest_on_savings_chart_of_account_id' => $coa[5100],
            'income_from_fees_chart_of_account_id' => $coa[4200],
            'name' => 'FOSA Current Account',
            'short_name' => 'FOSA',
            'description' => 'Front Office Service Account (FOSA)',
            'decimals' => 2,
            'savings_category' => 'voluntary',
            'account_product_type' => 'current',
            'auto_create' => 0,
            'minimum_interest_rate' => 0,
            'default_interest_rate' => 2,
            'maximum_interest_rate' => 4,
            'interest_rate_type' => 'year',
            'compounding_period' => 'monthly',
            'interest_posting_period_type' => 'monthly',
            'interest_calculation_type' => 'daily_balance',
            'automatic_opening_balance' => 0,
            'minimum_balance_for_interest_calculation' => 0,
            'lockin_period' => 0,
            'lockin_type' => 'days',
            'minimum_balance' => 0,
            'allow_overdraft' => 0,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->savingsProductDefaults()));

        $shareProductId = DB::table('share_products')->insertGetId([
            'created_by_id' => $userId,
            'currency_id' => $currencyId,
            'share_reference_chart_of_account_id' => $coa[2210],
            'share_suspense_control_chart_of_account_id' => $coa[1120],
            'equity_chart_of_account_id' => $coa[2210],
            'income_from_fees_chart_of_account_id' => $coa[4200],
            'name' => 'Compulsory Member Shares',
            'short_name' => 'SHARES',
            'description' => 'Compulsory SACCO share capital',
            'decimals' => 0,
            'share_category' => 'compulsory',
            'total_shares' => 100000,
            'shares_to_be_issued' => 50000,
            'nominal_price' => 100,
            'capital_value' => 10000000,
            'minimum_shares_per_client' => 10,
            'default_shares_per_client' => 20,
            'maximum_shares_per_client' => 500,
            'minimum_active_period' => 0,
            'minimum_active_period_type' => 'days',
            'lockin_period' => 0,
            'lockin_type' => 'days',
            'allow_dividends_for_inactive_clients' => 0,
            'accounting_rule' => 'cash',
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $loanProductId = DB::table('loan_products')->insertGetId(array_merge([
            'created_by_id' => $userId,
            'currency_id' => $currencyId,
            'loan_transaction_processing_strategy_id' => $strategyId,
            'fund_id' => $fundId,
            'name' => 'Development Loan',
            'short_name' => 'DEV',
            'description' => 'SACCO development loan product',
            'decimals' => 2,
            'instalment_multiple_of' => 1,
            'minimum_principal' => 10000,
            'default_principal' => 100000,
            'maximum_principal' => 2000000,
            'minimum_loan_term' => 3,
            'default_loan_term' => 12,
            'maximum_loan_term' => 60,
            'repayment_frequency' => 1,
            'repayment_frequency_type' => 'months',
            'minimum_interest_rate' => 10,
            'default_interest_rate' => 12,
            'maximum_interest_rate' => 18,
            'interest_rate_type' => 'year',
            'loan_multiplier' => 3,
            'active' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $loanCoa, $this->loanProductDefaults()));

        return [
            'bosa' => $bosaProductId,
            'fosa' => $fosaProductId,
            'share' => $shareProductId,
            'loan' => $loanProductId,
        ];
    }

    protected function savingsProductDefaults(): array
    {
        return [
            'overdraft_portfolio_chart_of_account_id' => null,
            'write_off_savings_chart_of_account_id' => null,
            'income_from_penalties_chart_of_account_id' => null,
            'income_from_interest_overdraft_chart_of_account_id' => null,
            'days_in_year' => '365',
            'days_in_month' => '30',
            'accounting_rule' => 'cash',
        ];
    }

    protected function loanProductDefaults(): array
    {
        return [
            'loan_disbursement_channel_id' => null,
            'penalties_receivable_chart_of_account_id' => null,
            'fees_receivable_chart_of_account_id' => null,
            'fees_chart_of_account_id' => null,
            'overpayments_chart_of_account_id' => null,
            'suspended_income_chart_of_account_id' => null,
            'income_from_penalties_chart_of_account_id' => null,
            'income_from_recovery_chart_of_account_id' => null,
            'losses_written_off_chart_of_account_id' => null,
            'interest_written_off_chart_of_account_id' => null,
            'enable_balloon_payments' => 0,
            'allow_schedule_adjustments' => 0,
            'grace_on_principal_paid' => 0,
            'grace_on_interest_paid' => 0,
            'grace_on_interest_charged' => 0,
            'allow_custom_grace_period' => 0,
            'allow_topup' => 0,
            'interest_methodology' => 'flat',
            'interest_recalculation' => 0,
            'amortization_method' => 'equal_installments',
            'interest_calculation_period_type' => 'same',
            'days_in_year' => 'actual',
            'days_in_month' => 'actual',
            'include_in_loan_cycle' => 0,
            'lock_guarantee_funds' => 0,
            'auto_allocate_overpayments' => 0,
            'allow_additional_charges' => 0,
            'auto_disburse' => 0,
            'require_linked_savings_account' => 0,
            'accounting_rule' => 'cash',
            'npa_overdue_days' => 0,
            'npa_suspend_accrued_income' => 0,
        ];
    }

    protected function seedMembers(int $branchId, int $userId, string $today, $now): array
    {
        $members = [
            ['first_name' => 'James', 'last_name' => 'Kamau', 'mobile' => '0712000001', 'account' => 'MEM-001'],
            ['first_name' => 'Mary', 'last_name' => 'Wanjiku', 'mobile' => '0712000002', 'account' => 'MEM-002'],
            ['first_name' => 'Peter', 'last_name' => 'Ochieng', 'mobile' => '0712000003', 'account' => 'MEM-003'],
            ['first_name' => 'Grace', 'last_name' => 'Achieng', 'mobile' => '0712000004', 'account' => 'MEM-004'],
            ['first_name' => 'David', 'last_name' => 'Mutua', 'mobile' => '0712000005', 'account' => 'MEM-005'],
        ];

        $ids = [];
        foreach ($members as $i => $member) {
            $ids[] = DB::table('clients')->insertGetId([
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'gender' => $i % 2 === 0 ? 'male' : 'female',
                'status' => 'active',
                'kyc_verified' => 1,
                'member_status_detail' => 'active',
                'mobile' => $member['mobile'],
                'email' => strtolower($member['first_name']) . '@sacco-demo.local',
                'account_number' => $member['account'],
                'external_id' => $i === 0 ? self::SEED_MARKER : 'SACCO-MODEL-' . ($i + 1),
                'dob' => '1985-0' . (($i % 9) + 1) . '-15',
                'address' => 'Nairobi, Kenya',
                'created_date' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $ids;
    }

    protected function seedAccounts(array $memberIds, array $products, int $branchId, int $currencyId, int $userId, int $fundId, int $strategyId, int $loanPurposeId, string $today, $now): array
    {
        $accounts = ['bosa' => [], 'fosa' => [], 'shares' => [], 'loans' => []];
        $savingsDefaults = [
            'interest_rate' => 5,
            'interest_rate_type' => 'year',
            'compounding_period' => 'monthly',
            'interest_posting_period_type' => 'monthly',
            'interest_calculation_type' => 'daily_balance',
            'lockin_period' => 0,
            'lockin_type' => 'days',
            'allow_overdraft' => 0,
        ];

        foreach ($memberIds as $i => $clientId) {
            $bosaId = DB::table('savings')->insertGetId(array_merge($savingsDefaults, [
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'savings_product_id' => $products['bosa'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'BOSA-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'balance_derived' => 0,
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            $accounts['bosa'][$clientId] = $bosaId;

            $fosaId = DB::table('savings')->insertGetId(array_merge($savingsDefaults, [
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'savings_product_id' => $products['fosa'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'FOSA-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'interest_rate' => 2,
                'balance_derived' => 0,
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]));
            $accounts['fosa'][$clientId] = $fosaId;

            $shareId = DB::table('shares')->insertGetId([
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'share_product_id' => $products['share'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'SHR-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'total_shares' => 20 + ($i * 5),
                'nominal_price' => 100,
                'lockin_period' => 0,
                'lockin_type' => 'days',
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $accounts['shares'][$clientId] = $shareId;
        }

        $loanAmounts = [150000, 200000, 80000];
        foreach (array_slice($memberIds, 0, 3) as $i => $clientId) {
            $principal = $loanAmounts[$i];
            $loanId = DB::table('loans')->insertGetId([
                'created_by_id' => $userId,
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'loan_product_id' => $products['loan'],
                'loan_transaction_processing_strategy_id' => $strategyId,
                'fund_id' => $fundId,
                'loan_purpose_id' => $loanPurposeId,
                'loan_officer_id' => $userId,
                'linked_savings_id' => $accounts['bosa'][$clientId],
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'disbursed_on_date' => $today,
                'expected_disbursement_date' => $today,
                'expected_first_payment_date' => date('Y-m-d', strtotime('+1 month')),
                'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
                'expected_maturity_date' => date('Y-m-d', strtotime('+12 months')),
                'account_number' => 'LN-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'principal' => $principal,
                'approved_amount' => $principal,
                'applied_amount' => $principal,
                'interest_rate' => 12,
                'loan_term' => 12,
                'repayment_frequency' => 1,
                'repayment_frequency_type' => 'months',
                'interest_rate_type' => 'year',
                'interest_methodology' => 'flat',
                'amortization_method' => 'equal_installments',
                'status' => 'active',
                'principal_disbursed_derived' => $principal,
                'principal_outstanding_derived' => $principal,
                'interest_outstanding_derived' => $principal * 0.12,
                'total_outstanding_derived' => $principal * 1.12,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $accounts['loans'][$clientId] = ['id' => $loanId, 'principal' => $principal];
        }

        return $accounts;
    }

    protected function seedTransactions(array $accounts, array $memberIds, int $branchId, int $currencyId, int $userId, string $today, $now): void
    {
        $depositTypeId = DB::table('savings_transaction_types')->where('name', 'Deposit')->value('id') ?? 1;
        $withdrawalTypeId = DB::table('savings_transaction_types')->where('name', 'Withdrawal')->value('id') ?? 2;
        $disbursementTypeId = DB::table('loan_transaction_types')->where('name', 'Disbursement')->value('id') ?? 1;
        $repaymentTypeId = DB::table('loan_transaction_types')->where('name', 'Repayment')->value('id') ?? 2;
        $sharePurchaseTypeId = DB::table('share_transaction_types')->where('name', 'Purchase')->value('id') ?? 1;

        $bosaDeposits = [50000, 75000, 120000, 45000, 90000];
        $fosaDeposits = [15000, 22000, 8000, 30000, 12000];
        $shareAmounts = [2000, 2500, 2000, 3000, 2500];

        foreach ($memberIds as $i => $clientId) {
            $bosaAmount = $bosaDeposits[$i];
            $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_deposit', $now);
            DB::table('savings_transactions')->insert([
                'savings_id' => $accounts['bosa'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Initial BOSA deposit',
                'amount' => $bosaAmount,
                'credit' => $bosaAmount,
                'balance' => $bosaAmount,
                'savings_transaction_type_id' => $depositTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('savings')->where('id', $accounts['bosa'][$clientId])->update([
                'balance_derived' => $bosaAmount,
                'total_deposits_derived' => $bosaAmount,
            ]);

            $fosaAmount = $fosaDeposits[$i];
            $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_deposit', $now);
            DB::table('savings_transactions')->insert([
                'savings_id' => $accounts['fosa'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Initial FOSA deposit',
                'amount' => $fosaAmount,
                'credit' => $fosaAmount,
                'balance' => $fosaAmount,
                'savings_transaction_type_id' => $depositTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('savings')->where('id', $accounts['fosa'][$clientId])->update([
                'balance_derived' => $fosaAmount,
                'total_deposits_derived' => $fosaAmount,
            ]);

            $shareAmount = $shareAmounts[$i];
            $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_share', $now);
            DB::table('share_transactions')->insert([
                'share_id' => $accounts['shares'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Share purchase',
                'amount' => $shareAmount,
                'credit' => $shareAmount,
                'balance' => $shareAmount,
                'share_transaction_type_id' => $sharePurchaseTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        foreach ($accounts['loans'] as $clientId => $loan) {
            $principal = $loan['principal'];
            $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_disbursement', $now);
            DB::table('loan_transactions')->insert([
                'loan_id' => $loan['id'],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Loan disbursement',
                'amount' => $principal,
                'debit' => $principal,
                'loan_transaction_type_id' => $disbursementTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $repayment = round($principal * 0.1, 2);
            $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_repayment', $now);
            DB::table('loan_transactions')->insert([
                'loan_id' => $loan['id'],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Loan repayment',
                'amount' => $repayment,
                'credit' => $repayment,
                'principal_repaid_derived' => $repayment,
                'loan_transaction_type_id' => $repaymentTypeId,
                'submitted_on' => date('Y-m-d', strtotime('-15 days')),
                'created_on' => date('Y-m-d', strtotime('-15 days')),
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('loans')->where('id', $loan['id'])->update([
                'principal_repaid_derived' => $repayment,
                'principal_outstanding_derived' => $principal - $repayment,
            ]);
        }

        $bosaId = $accounts['bosa'][$memberIds[0]];
        $fosaId = $accounts['fosa'][$memberIds[0]];
        $transferAmount = 5000;
        $paymentDetailId = $this->createPaymentDetail($userId, 'sacco_seed_transfer', $now);
        DB::table('savings_transactions')->insert([
            'savings_id' => $bosaId,
            'created_by_id' => $userId,
            'branch_id' => $branchId,
            'payment_detail_id' => $paymentDetailId,
            'name' => 'Transfer to FOSA',
            'amount' => $transferAmount,
            'debit' => $transferAmount,
            'balance' => $bosaDeposits[0] - $transferAmount,
            'savings_transaction_type_id' => $withdrawalTypeId,
            'submitted_on' => $today,
            'created_on' => $today,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('savings_transactions')->insert([
            'savings_id' => $fosaId,
            'created_by_id' => $userId,
            'branch_id' => $branchId,
            'payment_detail_id' => $paymentDetailId,
            'name' => 'Transfer from BOSA',
            'amount' => $transferAmount,
            'credit' => $transferAmount,
            'balance' => $fosaDeposits[0] + $transferAmount,
            'savings_transaction_type_id' => $depositTypeId,
            'submitted_on' => $today,
            'created_on' => $today,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('savings')->where('id', $bosaId)->update(['balance_derived' => $bosaDeposits[0] - $transferAmount]);
        DB::table('savings')->where('id', $fosaId)->update(['balance_derived' => $fosaDeposits[0] + $transferAmount]);
    }

    protected function seedJournalEntries(array $coa, int $branchId, int $currencyId, int $userId, string $yearStart, string $today, $now): void
    {
        $entries = [
            ['date' => $yearStart, 'amount' => 2500000, 'debit' => 1110, 'credit' => 3100, 'name' => 'Opening cash balance'],
            ['date' => date('Y-m-01', strtotime('-3 months')), 'amount' => 850000, 'debit' => 1210, 'credit' => 1120, 'name' => 'Loan disbursements - Q1'],
            ['date' => date('Y-m-15', strtotime('-2 months')), 'amount' => 420000, 'debit' => 1120, 'credit' => 2110, 'name' => 'BOSA member deposits'],
            ['date' => date('Y-m-10', strtotime('-1 month')), 'amount' => 180000, 'debit' => 1120, 'credit' => 2120, 'name' => 'FOSA member deposits'],
            ['date' => date('Y-m-05', strtotime('-1 month')), 'amount' => 95000, 'debit' => 1120, 'credit' => 2210, 'name' => 'Share capital contributions'],
            ['date' => date('Y-m-20', strtotime('-1 month')), 'amount' => 45000, 'debit' => 1120, 'credit' => 4100, 'name' => 'Loan interest income'],
            ['date' => date('Y-m-25', strtotime('-1 month')), 'amount' => 12000, 'debit' => 4200, 'credit' => 1120, 'name' => 'Processing fee income'],
            ['date' => date('Y-m-28', strtotime('-1 month')), 'amount' => 28000, 'debit' => 5100, 'credit' => 1120, 'name' => 'Interest paid on deposits'],
            ['date' => $today, 'amount' => 65000, 'debit' => 5200, 'credit' => 1120, 'name' => 'Monthly operating expenses'],
        ];

        foreach ($entries as $i => $entry) {
            $txnNumber = 'SACCO-JE-' . ($i + 1);
            $this->postJournalEntry(
                $txnNumber,
                $entry['date'],
                $coa[$entry['debit']],
                $coa[$entry['credit']],
                $entry['amount'],
                $entry['name'],
                $branchId,
                $currencyId,
                $userId,
                $now
            );
        }
    }

    protected function seedSaccoRecords(array $accounts, array $memberIds, array $products, int $branchId, int $userId, string $today, $now): void
    {
        $clientId = $memberIds[0];
        $bosaId = $accounts['bosa'][$clientId];
        $loanId = $accounts['loans'][$clientId]['id'] ?? null;

        DB::table('sacco_financial_periods')->insert([
            'name' => date('Y') . ' Financial Year',
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($loanId) {
            DB::table('sacco_transfers')->insert([
                'created_by_id' => $userId,
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'transfer_type' => 'savings_to_loan',
                'source_id' => $bosaId,
                'destination_id' => $loanId,
                'amount' => 10000,
                'transfer_date' => date('Y-m-d', strtotime('-10 days')),
                'notes' => 'BOSA to loan repayment transfer',
                'reference' => 'TRF-SEED-001',
                'status' => 'completed',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('sacco_standing_orders')->insert([
                'created_by_id' => $userId,
                'client_id' => $clientId,
                'source_savings_id' => $bosaId,
                'destination_loan_id' => $loanId,
                'amount' => 5000,
                'frequency' => 'monthly',
                'start_date' => $today,
                'next_run_date' => date('Y-m-d', strtotime('+1 month')),
                'status' => 'active',
                'description' => 'Monthly loan repayment from BOSA',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $dividendId = DB::table('sacco_dividends')->insertGetId([
            'created_by_id' => $userId,
            'share_product_id' => $products['share'],
            'name' => date('Y', strtotime('-1 year')) . ' AGM Dividend',
            'dividend_per_share' => 5,
            'total_pool' => 250000,
            'declaration_date' => date('Y-m-d', strtotime('-6 months')),
            'payment_date' => date('Y-m-d', strtotime('-5 months')),
            'payout_method' => 'savings',
            'status' => 'processed',
            'agm_resolution' => 'AGM Resolution 2025/01 - 5 per share dividend',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        foreach (array_slice($memberIds, 0, 3) as $i => $memberId) {
            DB::table('sacco_dividend_allocations')->insert([
                'sacco_dividend_id' => $dividendId,
                'share_id' => $accounts['shares'][$memberId],
                'client_id' => $memberId,
                'shares_held' => 20 + ($i * 5),
                'amount' => (20 + ($i * 5)) * 5,
                'status' => 'paid',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function createPaymentDetail(int $userId, string $type, $now): int
    {
        return DB::table('payment_details')->insertGetId([
            'created_by_id' => $userId,
            'payment_type_id' => 1,
            'transaction_type' => $type,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function postJournalEntry(string $txnNumber, string $date, int $debitAccountId, int $creditAccountId, float $amount, string $name, int $branchId, int $currencyId, int $userId, $now): void
    {
        $parts = explode('-', $date);
        $year = $parts[0];
        $month = $parts[1];

        DB::table('journal_entries')->insert([
            [
                'created_by_id' => $userId,
                'transaction_number' => $txnNumber,
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'chart_of_account_id' => $debitAccountId,
                'transaction_type' => 'sacco_seed',
                'name' => $name,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'debit' => $amount,
                'active' => 1,
                'manual_entry' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'created_by_id' => $userId,
                'transaction_number' => $txnNumber,
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'chart_of_account_id' => $creditAccountId,
                'transaction_type' => 'sacco_seed',
                'name' => $name,
                'date' => $date,
                'month' => $month,
                'year' => $year,
                'credit' => $amount,
                'active' => 1,
                'manual_entry' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}

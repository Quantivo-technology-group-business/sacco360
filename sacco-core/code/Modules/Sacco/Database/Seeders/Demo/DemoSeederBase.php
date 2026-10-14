<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class DemoSeederBase extends Seeder
{
    public const SEED_PREFIX = 'DEMO';
    public const SEED_MARKER = 'DEMO-SEED-MASTER';
    public const TXN_TYPE = 'demo_seed';

    protected static array $context = [];

    public static function resetContext(): void
    {
        self::$context = [
            'memberCount' => 250,
            'force' => false,
            'only' => [],
            'branchIds' => [],
            'staffUserIds' => [],
            'loanOfficerIds' => [],
            'tellerIds' => [],
            'productIds' => [],
            'memberIds' => [],
            'memberMeta' => [],
            'accounts' => ['bosa' => [], 'fosa' => [], 'shares' => [], 'loans' => []],
            'currencyId' => null,
            'adminUserId' => null,
            'coa' => [],
            'now' => null,
            'today' => null,
        ];
    }

    public static function setContext(array $values): void
    {
        self::$context = array_merge(self::$context, $values);
    }

    public static function context(string $key, $default = null)
    {
        return self::$context[$key] ?? $default;
    }

    public static function setContextValue(string $key, $value): void
    {
        self::$context[$key] = $value;
    }

    public static function shouldRun(string $seederKey): bool
    {
        $only = self::context('only', []);
        if (empty($only)) {
            return true;
        }
        return in_array($seederKey, $only, true);
    }

    public static function alreadySeeded(): bool
    {
        return DB::table('clients')->where('external_id', self::SEED_MARKER)->exists();
    }

    public static function wipeDemoData(): void
    {
        DB::disableQueryLog();

        $demoClientIds = DB::table('clients')
            ->where('external_id', 'like', self::SEED_PREFIX . '-%')
            ->pluck('id')
            ->all();

        $demoLoanIds = [];
        if (!empty($demoClientIds)) {
            $demoLoanIds = DB::table('loans')
                ->whereIn('client_id', $demoClientIds)
                ->orWhere('external_id', 'like', self::SEED_PREFIX . '-LOAN-%')
                ->pluck('id')
                ->all();
        }

        $demoSavingsIds = !empty($demoClientIds)
            ? DB::table('savings')->whereIn('client_id', $demoClientIds)->pluck('id')->all()
            : [];

        $demoShareIds = !empty($demoClientIds)
            ? DB::table('shares')->whereIn('client_id', $demoClientIds)->pluck('id')->all()
            : [];

        $demoUserIds = DB::table('users')
            ->where('email', 'like', '%@demo.ulm.local')
            ->pluck('id')
            ->all();

        $demoBranchIds = DB::table('branches')
            ->whereIn('name', ['Nairobi HQ', 'Mombasa Branch', 'Kisumu Branch', 'Nakuru Branch'])
            ->pluck('id')
            ->all();

        $tablesByClient = [
            'sacco_dividend_allocations',
            'sacco_kyc_documents',
            'sacco_kyc_verifications',
            'sacco_beneficiaries',
            'sacco_member_earmarks',
            'sacco_exit_settlements',
            'sacco_aml_alerts',
            'sacco_standing_orders',
            'sacco_transfers',
            'sacco_cheques',
            'sacco_atm_transactions',
            'sacco_mpesa_c2b_transactions',
            'sacco_mpesa_b2c_transactions',
            'sacco_pesalink_transactions',
            'sacco_debit_cards',
            'sacco_agent_transactions',
            'client_identification',
            'client_next_of_kin',
            'client_users',
            'wallets',
        ];

        foreach ($tablesByClient as $table) {
            if (Schema::hasTable($table) && !empty($demoClientIds)) {
                DB::table($table)->whereIn('client_id', $demoClientIds)->delete();
            }
        }

        if (Schema::hasTable('share_transfer_requests') && !empty($demoShareIds)
            && Schema::hasColumn('share_transfer_requests', 'seller_share_id')) {
            DB::table('share_transfer_requests')->where(function ($query) use ($demoShareIds) {
                $query->whereIn('seller_share_id', $demoShareIds)
                    ->orWhereIn('buyer_share_id', $demoShareIds);
            })->delete();
        }
        if (Schema::hasTable('share_listings') && !empty($demoShareIds)) {
            DB::table('share_listings')->whereIn('share_id', $demoShareIds)->delete();
        }
        if (Schema::hasTable('sacco_fixed_deposits') && !empty($demoSavingsIds)) {
            DB::table('sacco_fixed_deposits')->whereIn('savings_id', $demoSavingsIds)->delete();
        }
        if (Schema::hasTable('sacco_checkoff_items') && !empty($demoLoanIds)) {
            DB::table('sacco_checkoff_items')->whereIn('loan_id', $demoLoanIds)->delete();
        }
        if (Schema::hasTable('sacco_checkoff_batches')) {
            DB::table('sacco_checkoff_batches')->where('employer_name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('sacco_dividends')) {
            DB::table('sacco_dividends')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('sacco_financial_periods')) {
            DB::table('sacco_financial_periods')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }

        if (!empty($demoLoanIds)) {
            if (Schema::hasTable('loan_applications')) {
                DB::table('loan_applications')->where(function ($query) use ($demoClientIds, $demoLoanIds) {
                    $query->whereIn('client_id', $demoClientIds)
                        ->orWhereIn('loan_id', $demoLoanIds);
                })->where('notes', 'like', self::SEED_PREFIX . '%')->delete();
            }
            if (Schema::hasTable('loan_repayment_schedules')) {
                DB::table('loan_repayment_schedules')->whereIn('loan_id', $demoLoanIds)->delete();
            }
            if (Schema::hasTable('loan_transactions')) {
                DB::table('loan_transactions')->whereIn('loan_id', $demoLoanIds)->delete();
            }
            DB::table('loans')->whereIn('id', $demoLoanIds)->delete();
        }

        if (!empty($demoSavingsIds)) {
            DB::table('savings_transactions')->whereIn('savings_id', $demoSavingsIds)->delete();
            DB::table('savings')->whereIn('id', $demoSavingsIds)->delete();
        }

        if (!empty($demoShareIds)) {
            DB::table('share_transactions')->whereIn('share_id', $demoShareIds)->delete();
            DB::table('shares')->whereIn('id', $demoShareIds)->delete();
        }

        if (Schema::hasTable('wallet_transactions') && !empty($demoClientIds)) {
            $walletIds = DB::table('wallets')->whereIn('client_id', $demoClientIds)->pluck('id')->all();
            if (!empty($walletIds)) {
                DB::table('wallet_transactions')->whereIn('wallet_id', $walletIds)->delete();
            }
        }

        if (Schema::hasTable('communication_campaign_logs')) {
            DB::table('communication_campaign_logs')->where('campaign_name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('communication_campaigns')) {
            DB::table('communication_campaigns')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }

        if (Schema::hasTable('payroll_payments')) {
            DB::table('payroll_payments')->where('notes', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('payroll')) {
            DB::table('payroll')->where('description', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('payroll_runs')) {
            DB::table('payroll_runs')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('payroll_employees')) {
            DB::table('payroll_employees')->where('employee_number', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('payroll_template_items')) {
            $templateIds = DB::table('payroll_templates')->where('name', 'like', self::SEED_PREFIX . '%')->pluck('id')->all();
            if (!empty($templateIds)) {
                DB::table('payroll_template_items')->whereIn('payroll_template_id', $templateIds)->delete();
            }
        }
        if (Schema::hasTable('payroll_templates')) {
            DB::table('payroll_templates')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('payroll_items')) {
            DB::table('payroll_items')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }

        if (Schema::hasTable('asset_depreciation')) {
            $assetIds = DB::table('assets')->where('name', 'like', self::SEED_PREFIX . '%')->pluck('id')->all();
            if (!empty($assetIds)) {
                DB::table('asset_depreciation')->whereIn('asset_id', $assetIds)->delete();
            }
        }
        if (Schema::hasTable('assets')) {
            DB::table('assets')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('asset_types')) {
            DB::table('asset_types')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }

        if (Schema::hasTable('expenses')) {
            DB::table('expenses')->where('description', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('expense_types')) {
            DB::table('expense_types')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('income')) {
            DB::table('income')->where('description', 'like', self::SEED_PREFIX . '%')->delete();
        }
        if (Schema::hasTable('income_types')) {
            DB::table('income_types')->where('name', 'like', self::SEED_PREFIX . '%')->delete();
        }

        if (Schema::hasTable('journal_entries')) {
            DB::table('journal_entries')->where('transaction_type', self::TXN_TYPE)->delete();
        }

        if (Schema::hasTable('sacco_teller_sessions') && !empty($demoUserIds)) {
            DB::table('sacco_teller_sessions')->whereIn('user_id', $demoUserIds)->delete();
        }
        if (Schema::hasTable('sacco_tellers') && !empty($demoUserIds)) {
            DB::table('sacco_tellers')->whereIn('user_id', $demoUserIds)->delete();
        }

        if (!empty($demoClientIds)) {
            DB::table('clients')->whereIn('id', $demoClientIds)->delete();
        }

        if (!empty($demoUserIds)) {
            DB::table('model_has_roles')->where('model_type', 'Modules\\User\\Entities\\User')->whereIn('model_id', $demoUserIds)->delete();
            if (Schema::hasTable('branch_users')) {
                DB::table('branch_users')->whereIn('user_id', $demoUserIds)->delete();
            }
            DB::table('users')->whereIn('id', $demoUserIds)->delete();
        }

        if (!empty($demoBranchIds)) {
            DB::table('branch_users')->whereIn('branch_id', $demoBranchIds)->delete();
            DB::table('branches')->whereIn('id', $demoBranchIds)->delete();
        }

        DB::table('payment_details')->where('transaction_type', 'like', 'demo_seed%')->delete();
    }

    protected function initRuntime(): void
    {
        DB::disableQueryLog();
        if (!self::context('now')) {
            self::setContextValue('now', now());
        }
        if (!self::context('today')) {
            self::setContextValue('today', date('Y-m-d'));
        }
        if (!self::context('adminUserId')) {
            self::setContextValue('adminUserId', DB::table('users')->value('id') ?? 1);
        }
        if (!self::context('currencyId')) {
            self::setContextValue('currencyId', DB::table('currencies')->value('id') ?? 1);
        }
        if (empty(self::context('coa'))) {
            self::setContextValue('coa', $this->coaIds());
        }
    }

    protected function faker()
    {
        return FakerFactory::create('en_US');
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

    protected function resolveProducts(): array
    {
        $existing = [
            'bosa' => DB::table('savings_products')->where('short_name', 'BOSA')->value('id'),
            'fosa' => DB::table('savings_products')->where('short_name', 'FOSA')->value('id'),
            'share' => DB::table('share_products')->where('short_name', 'SHARES')->value('id'),
            'loan' => DB::table('loan_products')->where('short_name', 'DEV')->value('id'),
        ];

        if ($existing['bosa'] && $existing['fosa'] && $existing['share'] && $existing['loan']) {
            return $existing;
        }

        $coa = self::context('coa') ?: $this->coaIds();
        if (empty($coa)) {
            return $existing;
        }

        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $now = self::context('now');
        $fundId = DB::table('funds')->value('id') ?? DB::table('funds')->insertGetId(['name' => 'SACCO Operating Fund']);
        $strategyId = DB::table('loan_transaction_processing_strategies')->value('id') ?? 1;

        if (!$existing['bosa']) {
            $existing['bosa'] = DB::table('savings_products')->insertGetId($this->bosaProductRow($coa, $currencyId, $userId, $now));
        }
        if (!$existing['fosa']) {
            $existing['fosa'] = DB::table('savings_products')->insertGetId($this->fosaProductRow($coa, $currencyId, $userId, $now));
        }
        if (!$existing['share']) {
            $existing['share'] = DB::table('share_products')->insertGetId($this->shareProductRow($coa, $currencyId, $userId, $now));
        }
        if (!$existing['loan']) {
            $existing['loan'] = DB::table('loan_products')->insertGetId($this->loanProductRow($coa, $currencyId, $userId, $fundId, $strategyId, $now));
        }

        return $existing;
    }

    protected function bosaProductRow(array $coa, int $currencyId, int $userId, $now): array
    {
        return array_merge([
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
        ], $this->savingsProductDefaults());
    }

    protected function fosaProductRow(array $coa, int $currencyId, int $userId, $now): array
    {
        return array_merge([
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
        ], $this->savingsProductDefaults());
    }

    protected function shareProductRow(array $coa, int $currencyId, int $userId, $now): array
    {
        return [
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
        ];
    }

    protected function loanProductRow(array $coa, int $currencyId, int $userId, int $fundId, int $strategyId, $now): array
    {
        return array_merge([
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
            'fund_source_chart_of_account_id' => $coa[1120],
            'loan_portfolio_chart_of_account_id' => $coa[1210],
            'interest_receivable_chart_of_account_id' => $coa[1210],
            'income_from_interest_chart_of_account_id' => $coa[4100],
            'income_from_fees_chart_of_account_id' => $coa[4200],
            'created_at' => $now,
            'updated_at' => $now,
        ], $this->loanProductDefaults());
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
                'transaction_type' => self::TXN_TYPE,
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
                'transaction_type' => self::TXN_TYPE,
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

    protected function createStaffUser(string $firstName, string $lastName, string $role, int $branchId): int
    {
        $email = strtolower($firstName . '.' . $lastName) . '@demo.ulm.local';
        $existing = DB::table('users')->where('email', $email)->value('id');
        if ($existing) {
            return $existing;
        }

        $userId = DB::table('users')->insertGetId([
            'branch_id' => $branchId,
            'name' => $firstName . ' ' . $lastName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'username' => Str::slug($firstName . $lastName) . rand(10, 99),
            'email' => $email,
            'password' => Hash::make('demo123'),
            'api_token' => Str::random(60),
            'phone' => '07' . rand(10000000, 99999999),
            'email_verified_at' => self::context('now'),
            'created_at' => self::context('now'),
            'updated_at' => self::context('now'),
        ]);

        $roleId = DB::table('roles')->where('name', $role)->value('id');
        if ($roleId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $roleId,
                'model_type' => 'Modules\\User\\Entities\\User',
                'model_id' => $userId,
            ]);
        }

        return $userId;
    }

    protected function kenyanMobile(int $index): string
    {
        return '07' . str_pad((string)(10000000 + $index), 8, '0', STR_PAD_LEFT);
    }
}

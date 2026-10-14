<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaccoLoanProductTemplatesSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('loan_products')) {
            return;
        }

        $userId = DB::table('users')->value('id') ?? 1;
        $currencyId = DB::table('currencies')->value('id') ?? 1;
        $strategyId = DB::table('loan_transaction_processing_strategies')->value('id') ?? 1;
        $fundId = DB::table('funds')->value('id') ?? 1;
        $now = now();

        $defaults = [
            'created_by_id' => $userId,
            'currency_id' => $currencyId,
            'loan_transaction_processing_strategy_id' => $strategyId,
            'fund_id' => $fundId,
            'decimals' => 2,
            'instalment_multiple_of' => 1,
            'repayment_frequency' => 1,
            'active' => 1,
            'accounting_rule' => 'cash',
            'interest_methodology' => 'declining_balance',
            'amortization_method' => 'equal_installments',
            'loan_multiplier' => 3,
            'appraisal_method' => 'combined',
            'min_guarantors' => 2,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $templates = [
            [
                'name' => 'BOSA Development Loan',
                'short_name' => 'BOSA-DEV',
                'description' => 'Long-term BOSA asset financing (land, construction, vehicle)',
                'office_wing' => 'bosa',
                'is_salary_advance' => 0,
                'minimum_principal' => 50000,
                'default_principal' => 500000,
                'maximum_principal' => 5000000,
                'minimum_loan_term' => 12,
                'default_loan_term' => 36,
                'maximum_loan_term' => 96,
                'repayment_frequency_type' => 'months',
                'minimum_interest_rate' => 10,
                'default_interest_rate' => 12,
                'maximum_interest_rate' => 13,
                'interest_rate_type' => 'year',
            ],
            [
                'name' => 'FOSA Salary Advance',
                'short_name' => 'FOSA-ADV',
                'description' => 'Short-term salary advance against FOSA balance',
                'office_wing' => 'fosa',
                'is_salary_advance' => 1,
                'minimum_principal' => 1000,
                'default_principal' => 10000,
                'maximum_principal' => 100000,
                'minimum_loan_term' => 1,
                'default_loan_term' => 1,
                'maximum_loan_term' => 3,
                'repayment_frequency_type' => 'months',
                'minimum_interest_rate' => 1,
                'default_interest_rate' => 2,
                'maximum_interest_rate' => 2,
                'interest_rate_type' => 'month',
                'interest_methodology' => 'flat',
                'min_guarantors' => 0,
                'loan_multiplier' => 1,
            ],
            [
                'name' => 'FOSA Emergency Loan',
                'short_name' => 'FOSA-EMG',
                'description' => 'Short-term working capital for FOSA members',
                'office_wing' => 'fosa',
                'is_salary_advance' => 0,
                'minimum_principal' => 5000,
                'default_principal' => 25000,
                'maximum_principal' => 200000,
                'minimum_loan_term' => 1,
                'default_loan_term' => 6,
                'maximum_loan_term' => 12,
                'repayment_frequency_type' => 'months',
                'minimum_interest_rate' => 12,
                'default_interest_rate' => 15,
                'maximum_interest_rate' => 18,
                'interest_rate_type' => 'year',
                'min_guarantors' => 0,
                'loan_multiplier' => 1,
            ],
        ];

        foreach ($templates as $template) {
            $exists = DB::table('loan_products')->where('short_name', $template['short_name'])->exists();
            if ($exists) {
                continue;
            }
            DB::table('loan_products')->insert(array_merge($defaults, $template));
        }

        if (Schema::hasTable('sacco_cross_sell_campaigns')) {
            $exists = DB::table('sacco_cross_sell_campaigns')->where('name', 'FOSA to BOSA Conversion')->exists();
            if (!$exists) {
                DB::table('sacco_cross_sell_campaigns')->insert([
                    'name' => 'FOSA to BOSA Conversion',
                    'source_wing' => 'fosa',
                    'target_wing' => 'bosa',
                    'min_balance' => 10000,
                    'message' => 'You qualify to open a BOSA account and access development loans up to 3x your savings.',
                    'start_date' => date('Y-m-d'),
                    'end_date' => null,
                    'active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

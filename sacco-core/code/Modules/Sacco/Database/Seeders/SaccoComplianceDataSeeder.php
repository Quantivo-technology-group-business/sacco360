<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SaccoComplianceDataSeeder extends Seeder
{
    public function run()
    {
        if (Schema::hasTable('sacco_report_templates') && DB::table('sacco_report_templates')->count() === 0) {
            DB::table('sacco_report_templates')->insert([
                [
                    'code' => '1/008',
                    'name' => 'Other Disclosures',
                    'form_type' => 'disclosure',
                    'frequency' => 'adhoc',
                    'field_definitions' => json_encode(['related_party_transactions', 'contingent_liabilities', 'off_balance_sheet']),
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'code' => '4B',
                    'name' => 'Sectorial Lending Report',
                    'form_type' => 'lending',
                    'frequency' => 'adhoc',
                    'field_definitions' => json_encode(['sector_code', 'outstanding', 'loan_count']),
                    'active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }

        if (Schema::hasTable('chart_of_accounts')) {
            $loanParent = DB::table('chart_of_accounts')->where('gl_code', 1200)->value('id');
            if ($loanParent && !DB::table('chart_of_accounts')->where('gl_code', 1215)->exists()) {
                DB::table('chart_of_accounts')->insert([
                    'parent_id' => $loanParent,
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

            $liquidCategories = [
                1110 => 'liquid_asset',
                1120 => 'liquid_asset',
                4300 => 'investment',
            ];
            foreach ($liquidCategories as $glCode => $category) {
                if (Schema::hasColumn('chart_of_accounts', 'sasra_category')) {
                    DB::table('chart_of_accounts')->where('gl_code', $glCode)->update(['sasra_category' => $category]);
                }
            }
        }

        if (Schema::hasTable('sacco_provision_rates')) {
            DB::table('sacco_provision_rates')->truncate();
            (new SaccoProvisionRatesTableSeeder())->run();
        }
    }
}

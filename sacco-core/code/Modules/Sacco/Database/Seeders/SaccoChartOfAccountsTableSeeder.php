<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaccoChartOfAccountsTableSeeder extends Seeder
{
    public function run()
    {
        if (DB::table('chart_of_accounts')->where('gl_code', 1100)->exists()) {
            return;
        }

        $now = now();
        $accounts = [
            ['parent_gl' => null, 'name' => 'Assets', 'gl_code' => 1000, 'account_type' => 'asset', 'allow_manual' => 0],
            ['parent_gl' => 1000, 'name' => 'Cash and Cash Equivalents', 'gl_code' => 1100, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => 1100, 'name' => 'Cash on Hand', 'gl_code' => 1110, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => 1100, 'name' => 'Cash at Bank', 'gl_code' => 1120, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => 1000, 'name' => 'Loans and Advances', 'gl_code' => 1200, 'account_type' => 'asset', 'allow_manual' => 0],
            ['parent_gl' => 1200, 'name' => 'Normal Loans Portfolio', 'gl_code' => 1210, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => 1200, 'name' => 'Loans in Arrears', 'gl_code' => 1220, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => 1200, 'name' => 'Allowance for Loan Losses', 'gl_code' => 1215, 'account_type' => 'asset', 'allow_manual' => 0],
            ['parent_gl' => 1000, 'name' => 'Interest Receivable', 'gl_code' => 1300, 'account_type' => 'asset', 'allow_manual' => 1],
            ['parent_gl' => null, 'name' => 'Liabilities', 'gl_code' => 2000, 'account_type' => 'liability', 'allow_manual' => 0],
            ['parent_gl' => 2000, 'name' => 'Member Deposits', 'gl_code' => 2100, 'account_type' => 'liability', 'allow_manual' => 0],
            ['parent_gl' => 2100, 'name' => 'BOSA Savings Deposits', 'gl_code' => 2110, 'account_type' => 'liability', 'allow_manual' => 1],
            ['parent_gl' => 2100, 'name' => 'FOSA Current Deposits', 'gl_code' => 2120, 'account_type' => 'liability', 'allow_manual' => 1],
            ['parent_gl' => 2000, 'name' => 'Share Capital', 'gl_code' => 2200, 'account_type' => 'liability', 'allow_manual' => 0],
            ['parent_gl' => 2200, 'name' => 'Compulsory Shares', 'gl_code' => 2210, 'account_type' => 'liability', 'allow_manual' => 1],
            ['parent_gl' => 2200, 'name' => 'Voluntary Shares', 'gl_code' => 2220, 'account_type' => 'liability', 'allow_manual' => 1],
            ['parent_gl' => null, 'name' => 'Equity', 'gl_code' => 3000, 'account_type' => 'equity', 'allow_manual' => 0],
            ['parent_gl' => 3000, 'name' => 'Retained Earnings', 'gl_code' => 3100, 'account_type' => 'equity', 'allow_manual' => 1],
            ['parent_gl' => 3000, 'name' => 'Statutory Reserve', 'gl_code' => 3200, 'account_type' => 'equity', 'allow_manual' => 1],
            ['parent_gl' => null, 'name' => 'Income', 'gl_code' => 4000, 'account_type' => 'income', 'allow_manual' => 0],
            ['parent_gl' => 4000, 'name' => 'Interest Income on Loans', 'gl_code' => 4100, 'account_type' => 'income', 'allow_manual' => 1],
            ['parent_gl' => 4000, 'name' => 'Fee Income', 'gl_code' => 4200, 'account_type' => 'income', 'allow_manual' => 1],
            ['parent_gl' => 4000, 'name' => 'Investment Income', 'gl_code' => 4300, 'account_type' => 'income', 'allow_manual' => 1],
            ['parent_gl' => null, 'name' => 'Expenses', 'gl_code' => 5000, 'account_type' => 'expense', 'allow_manual' => 0],
            ['parent_gl' => 5000, 'name' => 'Interest on Member Deposits', 'gl_code' => 5100, 'account_type' => 'expense', 'allow_manual' => 1],
            ['parent_gl' => 5000, 'name' => 'Operating Expenses', 'gl_code' => 5200, 'account_type' => 'expense', 'allow_manual' => 1],
            ['parent_gl' => 5000, 'name' => 'Loan Loss Provision', 'gl_code' => 5300, 'account_type' => 'expense', 'allow_manual' => 1],
        ];

        $idByGl = [];
        foreach ($accounts as $account) {
            $parentId = $account['parent_gl'] ? ($idByGl[$account['parent_gl']] ?? null) : null;
            $id = DB::table('chart_of_accounts')->insertGetId([
                'parent_id' => $parentId,
                'name' => $account['name'],
                'gl_code' => $account['gl_code'],
                'account_type' => $account['account_type'],
                'allow_manual' => $account['allow_manual'],
                'active' => 1,
                'notes' => 'SACCO model chart of accounts',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $idByGl[$account['gl_code']] = $id;
        }
    }
}

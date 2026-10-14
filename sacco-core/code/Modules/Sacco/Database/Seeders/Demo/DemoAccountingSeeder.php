<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoAccountingSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('accounting')) {
            return;
        }

        $this->initRuntime();

        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $branchIds = self::context('branchIds', [1]);
        $coa = self::context('coa', []);
        $today = self::context('today');
        $now = self::context('now');
        $branchId = $branchIds[0];

        $expenseCoaId = $coa[5200] ?? DB::table('chart_of_accounts')->value('id');
        $incomeCoaId = $coa[4200] ?? $expenseCoaId;

        if (Schema::hasTable('expense_types')) {
            $expenseTypeIds = [];
            foreach (['Office Rent', 'Utilities', 'Staff Welfare', 'Transport'] as $name) {
                $expenseTypeIds[] = DB::table('expense_types')->insertGetId([
                    'name' => self::SEED_PREFIX . ' ' . $name,
                    'expense_chart_of_account_id' => $expenseCoaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (Schema::hasTable('expenses')) {
                $expenseRows = [];
                for ($i = 1; $i <= 20; $i++) {
                    $expenseRows[] = [
                        'created_by_id' => $userId,
                        'expense_type_id' => $expenseTypeIds[$i % count($expenseTypeIds)],
                        'expense_chart_of_account_id' => $expenseCoaId,
                        'currency_id' => $currencyId,
                        'branch_id' => $branchId,
                        'amount' => rand(5000, 50000),
                        'date' => date('Y-m-d', strtotime('-' . rand(1, 90) . ' days')),
                        'description' => self::SEED_PREFIX . ' operating expense #' . $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($expenseRows, 20) as $chunk) {
                    DB::table('expenses')->insert($chunk);
                }
            }
        }

        if (Schema::hasTable('income_types')) {
            $incomeTypeIds = [];
            foreach (['Membership Fees', 'Processing Fees', 'Penalty Income'] as $name) {
                $incomeTypeIds[] = DB::table('income_types')->insertGetId([
                    'name' => self::SEED_PREFIX . ' ' . $name,
                    'income_chart_of_account_id' => $incomeCoaId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (Schema::hasTable('income')) {
                $incomeRows = [];
                for ($i = 1; $i <= 20; $i++) {
                    $incomeRows[] = [
                        'created_by_id' => $userId,
                        'income_type_id' => $incomeTypeIds[$i % count($incomeTypeIds)],
                        'income_chart_of_account_id' => $incomeCoaId,
                        'currency_id' => $currencyId,
                        'branch_id' => $branchId,
                        'amount' => rand(3000, 80000),
                        'date' => date('Y-m-d', strtotime('-' . rand(1, 90) . ' days')),
                        'description' => self::SEED_PREFIX . ' income record #' . $i,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                foreach (array_chunk($incomeRows, 20) as $chunk) {
                    DB::table('income')->insert($chunk);
                }
            }
        }

        if (!empty($coa)) {
            $entries = [
                ['date' => date('Y-01-01'), 'amount' => 5000000, 'debit' => 1110, 'credit' => 3100, 'name' => 'Opening cash balance'],
                ['date' => date('Y-m-01', strtotime('-3 months')), 'amount' => 1200000, 'debit' => 1210, 'credit' => 1120, 'name' => 'Loan disbursements Q1'],
                ['date' => date('Y-m-15', strtotime('-2 months')), 'amount' => 850000, 'debit' => 1120, 'credit' => 2110, 'name' => 'BOSA member deposits'],
                ['date' => date('Y-m-10', strtotime('-1 month')), 'amount' => 420000, 'debit' => 1120, 'credit' => 2120, 'name' => 'FOSA member deposits'],
                ['date' => $today, 'amount' => 95000, 'debit' => 5200, 'credit' => 1120, 'name' => 'Monthly operating expenses'],
            ];

            foreach ($entries as $i => $entry) {
                if (!isset($coa[$entry['debit']], $coa[$entry['credit']])) {
                    continue;
                }
                $this->postJournalEntry(
                    self::SEED_PREFIX . '-JE-' . ($i + 1),
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
    }
}

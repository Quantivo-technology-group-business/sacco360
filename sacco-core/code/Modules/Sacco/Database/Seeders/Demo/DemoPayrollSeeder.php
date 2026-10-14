<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoPayrollSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('payroll')) {
            return;
        }

        $this->initRuntime();

        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $branchIds = self::context('branchIds', [1]);
        $staffUserIds = self::context('staffUserIds', []);
        $today = self::context('today');
        $now = self::context('now');
        $branchId = $branchIds[0];

        if (!Schema::hasTable('payroll_items')) {
            return;
        }

        $allowanceId = DB::table('payroll_items')->insertGetId([
            'name' => self::SEED_PREFIX . ' Housing Allowance',
            'type' => 'allowance',
            'amount_type' => 'fixed',
            'amount' => 15000,
            'description' => 'Monthly housing allowance',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $deductionId = DB::table('payroll_items')->insertGetId([
            'name' => self::SEED_PREFIX . ' NSSF Deduction',
            'type' => 'deduction',
            'amount_type' => 'percentage',
            'amount' => 6,
            'description' => 'NSSF statutory deduction',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $templateId = null;
        if (Schema::hasTable('payroll_templates')) {
            $templateId = DB::table('payroll_templates')->insertGetId([
                'name' => self::SEED_PREFIX . ' Standard Staff Template',
                'description' => 'Default payroll template for SACCO staff',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('payroll_template_items')) {
                DB::table('payroll_template_items')->insert([
                    [
                        'payroll_template_id' => $templateId,
                        'payroll_item_id' => $allowanceId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    [
                        'payroll_template_id' => $templateId,
                        'payroll_item_id' => $deductionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ]);
            }
        }

        $runId = null;
        if (Schema::hasTable('payroll_runs')) {
            $runId = DB::table('payroll_runs')->insertGetId([
                'name' => self::SEED_PREFIX . ' ' . date('F Y') . ' Payroll',
                'period' => date('Y-m'),
                'period_start' => date('Y-m-01'),
                'period_end' => date('Y-m-t'),
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'created_by_id' => $userId,
                'status' => 'approved',
                'total_gross' => 0,
                'total_net' => 0,
                'employee_count' => count($staffUserIds),
                'description' => 'Demo payroll run',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $staffToPay = array_slice($staffUserIds, 0, 5);
        if (empty($staffToPay)) {
            $staffToPay = [$userId];
        }

        foreach ($staffToPay as $i => $staffId) {
            $basic = rand(45000, 120000);
            $gross = $basic + 15000;
            $net = round($gross * 0.86, 2);

            if (Schema::hasTable('payroll_employees')) {
                DB::table('payroll_employees')->insert([
                    'user_id' => $staffId,
                    'branch_id' => $branchId,
                    'payroll_template_id' => $templateId,
                    'employee_number' => self::SEED_PREFIX . '-EMP-' . ($i + 1),
                    'department' => 'Operations',
                    'job_title' => $i < 3 ? 'Loan Officer' : 'Teller',
                    'salary_type' => 'monthly',
                    'basic_salary' => $basic,
                    'bank_name' => 'Demo Bank',
                    'account_number' => 'ACC' . rand(100000, 999999),
                    'date_hired' => date('Y-m-d', strtotime('-' . rand(1, 5) . ' years')),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (Schema::hasTable('payroll')) {
                $payrollRow = [
                    'branch_id' => $branchId,
                    'currency_id' => $currencyId,
                    'created_by_id' => $userId,
                    'payroll_template_id' => $templateId,
                    'user_id' => $staffId,
                    'employee_name' => 'Staff Member ' . ($i + 1),
                    'description' => self::SEED_PREFIX . ' payroll entry',
                    'gross_amount' => $gross,
                    'total_allowances' => 15000,
                    'total_deductions' => $gross - $net,
                    'date' => $today,
                    'year' => date('Y'),
                    'month' => date('m'),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                if (Schema::hasColumn('payroll', 'payroll_run_id')) {
                    $payrollRow['payroll_run_id'] = $runId;
                }
                foreach (['basic_salary' => $basic, 'gross_pay' => $gross, 'taxable_pay' => $gross, 'net_pay' => $net, 'paye' => round($gross * 0.1, 2)] as $col => $val) {
                    if (Schema::hasColumn('payroll', $col)) {
                        $payrollRow[$col] = $val;
                    }
                }
                $payrollId = DB::table('payroll')->insertGetId($payrollRow);

                if (Schema::hasTable('payroll_payments')) {
                    DB::table('payroll_payments')->insert([
                        'created_by_id' => $userId,
                        'branch_id' => $branchId,
                        'payroll_id' => $payrollId,
                        'amount' => $net,
                        'submitted_on' => $today,
                        'notes' => self::SEED_PREFIX . ' salary payment',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }

        if ($runId) {
            DB::table('payroll_runs')->where('id', $runId)->update([
                'total_gross' => count($staffToPay) * 80000,
                'total_net' => count($staffToPay) * 68000,
            ]);
        }
    }
}

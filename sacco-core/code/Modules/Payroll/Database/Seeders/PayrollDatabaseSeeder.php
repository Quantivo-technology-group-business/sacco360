<?php

namespace Modules\Payroll\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Payroll\Entities\PayrollItem;

class PayrollDatabaseSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('payroll_items')) {
            return;
        }

        $defaults = [
            ['name' => 'House Allowance', 'type' => 'allowance', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'allowance', 'is_taxable' => 1],
            ['name' => 'Transport Allowance', 'type' => 'allowance', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'allowance', 'is_taxable' => 1],
            ['name' => 'SACCO Savings', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'sacco', 'is_taxable' => 0],
            ['name' => 'Loan Repayment', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'loan', 'is_taxable' => 0],
            ['name' => 'Staff Welfare', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'voluntary', 'is_taxable' => 0],
        ];

        foreach ($defaults as $item) {
            PayrollItem::firstOrCreate(['name' => $item['name']], $item);
        }
    }
}

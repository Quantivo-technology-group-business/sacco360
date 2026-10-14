<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class ComprehensiveDemoDataSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            DemoBranchesSeeder::class,
            DemoStaffSeeder::class,
            DemoMembersSeeder::class,
            DemoAccountsSeeder::class,
            DemoLoansSeeder::class,
            DemoLoanApplicationsSeeder::class,
            DemoTransactionsSeeder::class,
            DemoSaccoRecordsSeeder::class,
            DemoAccountingSeeder::class,
            DemoPayrollSeeder::class,
            DemoAssetSeeder::class,
            DemoWalletSeeder::class,
            DemoCommunicationSeeder::class,
        ]);
    }
}

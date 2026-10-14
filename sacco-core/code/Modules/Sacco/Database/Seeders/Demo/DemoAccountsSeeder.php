<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;

class DemoAccountsSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('accounts')) {
            return;
        }

        $this->initRuntime();

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        if (empty($memberIds)) {
            return;
        }

        $products = $this->resolveProducts();
        self::setContextValue('productIds', $products);

        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $today = self::context('today');
        $now = self::context('now');

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

        $bosaRows = [];
        $fosaRows = [];
        $shareRows = [];

        foreach ($memberIds as $clientId) {
            $index = $memberMeta[$clientId]['index'] ?? $clientId;
            $branchId = $memberMeta[$clientId]['branch_id'] ?? self::context('branchIds')[0];

            $bosaRows[] = array_merge($savingsDefaults, [
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'savings_product_id' => $products['bosa'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'BOSA-DEMO-' . str_pad((string)$index, 4, '0', STR_PAD_LEFT),
                'balance_derived' => 0,
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $fosaRows[] = array_merge($savingsDefaults, [
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'savings_product_id' => $products['fosa'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'FOSA-DEMO-' . str_pad((string)$index, 4, '0', STR_PAD_LEFT),
                'interest_rate' => 2,
                'balance_derived' => 0,
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $shareRows[] = [
                'created_by_id' => $userId,
                'currency_id' => $currencyId,
                'share_product_id' => $products['share'],
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'account_number' => 'SHR-DEMO-' . str_pad((string)$index, 4, '0', STR_PAD_LEFT),
                'total_shares' => 20 + (($index % 10) * 5),
                'nominal_price' => 100,
                'lockin_period' => 0,
                'lockin_type' => 'days',
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($bosaRows, 50) as $chunk) {
            DB::table('savings')->insert($chunk);
        }
        foreach (array_chunk($fosaRows, 50) as $chunk) {
            DB::table('savings')->insert($chunk);
        }
        foreach (array_chunk($shareRows, 50) as $chunk) {
            DB::table('shares')->insert($chunk);
        }

        $bosaAccounts = DB::table('savings')
            ->where('account_number', 'like', 'BOSA-DEMO-%')
            ->pluck('id', 'client_id')
            ->all();
        $fosaAccounts = DB::table('savings')
            ->where('account_number', 'like', 'FOSA-DEMO-%')
            ->pluck('id', 'client_id')
            ->all();
        $shareAccounts = DB::table('shares')
            ->where('account_number', 'like', 'SHR-DEMO-%')
            ->pluck('id', 'client_id')
            ->all();

        $accounts['bosa'] = $bosaAccounts;
        $accounts['fosa'] = $fosaAccounts;
        $accounts['shares'] = $shareAccounts;

        self::setContextValue('accounts', $accounts);
    }
}

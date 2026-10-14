<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoWalletSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('wallets')) {
            return;
        }

        $this->initRuntime();

        if (!Schema::hasTable('wallets')) {
            return;
        }

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $today = self::context('today');
        $now = self::context('now');

        $walletMembers = array_slice($memberIds, 0, 30);

        foreach ($walletMembers as $i => $clientId) {
            $branchId = $memberMeta[$clientId]['branch_id'] ?? self::context('branchIds')[0];
            $balance = rand(1000, 25000);

            $walletId = DB::table('wallets')->insertGetId([
                'currency_id' => $currencyId,
                'created_by_id' => $userId,
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'status' => 'active',
                'balance' => $balance,
                'decimals' => 2,
                'description' => self::SEED_PREFIX . ' member wallet',
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'activated_on_date' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (!Schema::hasTable('wallet_transactions')) {
                continue;
            }

            $runningBalance = 0;
            $txnCount = rand(2, 3);
            for ($t = 1; $t <= $txnCount; $t++) {
                $amount = rand(500, 5000);
                $isDeposit = $t % 2 !== 0;
                $runningBalance = $isDeposit ? $runningBalance + $amount : max($runningBalance - $amount, 0);
                $txnDate = date('Y-m-d', strtotime('-' . ($txnCount - $t + 1) * 15 . ' days'));

                $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_wallet', $now);
                DB::table('wallet_transactions')->insert([
                    'wallet_id' => $walletId,
                    'created_by_id' => $userId,
                    'branch_id' => $branchId,
                    'payment_detail_id' => $paymentDetailId,
                    'transaction_type' => $isDeposit ? 'deposit' : 'withdrawal',
                    'name' => $isDeposit ? 'Wallet top-up' : 'Wallet withdrawal',
                    'amount' => $amount,
                    'credit' => $isDeposit ? $amount : null,
                    'debit' => $isDeposit ? null : $amount,
                    'balance' => $runningBalance,
                    'submitted_on' => $txnDate,
                    'created_on' => $txnDate,
                    'description' => self::SEED_PREFIX . ' wallet transaction',
                    'reference' => self::SEED_PREFIX . '-WTX-' . ($i + 1) . '-' . $t,
                    'status' => 'approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('wallets')->where('id', $walletId)->update(['balance' => $runningBalance]);
        }
    }
}

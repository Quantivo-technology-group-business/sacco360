<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;

class DemoTransactionsSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('transactions')) {
            return;
        }

        $this->initRuntime();

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        $accounts = self::context('accounts', []);

        if (empty($memberIds) || empty($accounts['bosa'])) {
            return;
        }

        $userId = self::context('adminUserId');
        $today = self::context('today');
        $now = self::context('now');
        $branchIds = self::context('branchIds', [1]);

        $depositTypeId = DB::table('savings_transaction_types')->where('name', 'Deposit')->value('id') ?? 1;
        $withdrawalTypeId = DB::table('savings_transaction_types')->where('name', 'Withdrawal')->value('id') ?? 2;
        $disbursementTypeId = DB::table('loan_transaction_types')->where('name', 'Disbursement')->value('id') ?? 1;
        $repaymentTypeId = DB::table('loan_transaction_types')->where('name', 'Repayment')->value('id') ?? 2;
        $sharePurchaseTypeId = DB::table('share_transaction_types')->where('name', 'Purchase')->value('id') ?? 1;

        $savingsTxns = [];
        $shareTxns = [];
        $loanTxns = [];
        $bosaBalances = [];
        $fosaBalances = [];

        foreach ($memberIds as $clientId) {
            $index = $memberMeta[$clientId]['index'] ?? 1;
            $branchId = $memberMeta[$clientId]['branch_id'] ?? $branchIds[0];

            $bosaAmount = rand(10000, 200000);
            $fosaAmount = rand(5000, 50000);
            $shareAmount = rand(2000, 5000);

            $bosaBalances[$clientId] = $bosaAmount;
            $fosaBalances[$clientId] = $fosaAmount;

            $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_deposit', $now);
            $savingsTxns[] = $this->savingsTxnRow([
                'savings_id' => $accounts['bosa'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Initial BOSA deposit',
                'amount' => $bosaAmount,
                'credit' => $bosaAmount,
                'balance' => $bosaAmount,
                'savings_transaction_type_id' => $depositTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
            ], $now);

            $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_deposit', $now);
            $savingsTxns[] = $this->savingsTxnRow([
                'savings_id' => $accounts['fosa'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Initial FOSA deposit',
                'amount' => $fosaAmount,
                'credit' => $fosaAmount,
                'balance' => $fosaAmount,
                'savings_transaction_type_id' => $depositTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
            ], $now);

            $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_share', $now);
            $shareTxns[] = [
                'share_id' => $accounts['shares'][$clientId],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Share purchase',
                'amount' => $shareAmount,
                'credit' => $shareAmount,
                'balance' => $shareAmount,
                'share_transaction_type_id' => $sharePurchaseTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
                'status' => 'approved',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($index % 7 === 0) {
                $extraAmount = rand(1000, 10000);
                $monthsAgo = rand(1, 11);
                $txnDate = date('Y-m-d', strtotime('-' . $monthsAgo . ' months'));
                $bosaBalances[$clientId] += $extraAmount;

                $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_deposit', $now);
                $savingsTxns[] = $this->savingsTxnRow([
                    'savings_id' => $accounts['bosa'][$clientId],
                    'created_by_id' => $userId,
                    'branch_id' => $branchId,
                    'payment_detail_id' => $paymentDetailId,
                    'name' => 'Monthly BOSA contribution',
                    'amount' => $extraAmount,
                    'credit' => $extraAmount,
                    'balance' => $bosaBalances[$clientId],
                    'savings_transaction_type_id' => $depositTypeId,
                    'submitted_on' => $txnDate,
                    'created_on' => $txnDate,
                ], $now);
            }
        }

        foreach ($accounts['loans'] ?? [] as $clientId => $loan) {
            $branchId = $memberMeta[$clientId]['branch_id'] ?? $branchIds[0];
            $principal = $loan['principal'];

            $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_disbursement', $now);
            $loanTxns[] = $this->loanTxnRow([
                'loan_id' => $loan['id'],
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Loan disbursement',
                'amount' => $principal,
                'debit' => $principal,
                'loan_transaction_type_id' => $disbursementTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
            ], $now);

            $repaymentCount = rand(1, 6);
            $totalRepaid = 0;
            for ($r = 1; $r <= $repaymentCount; $r++) {
                $repayment = round($principal * (rand(5, 15) / 100), 2);
                $totalRepaid += $repayment;
                $repayDate = date('Y-m-d', strtotime('-' . ($repaymentCount - $r + 1) * 30 . ' days'));

                $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_repayment', $now);
                $loanTxns[] = $this->loanTxnRow([
                    'loan_id' => $loan['id'],
                    'created_by_id' => $userId,
                    'branch_id' => $branchId,
                    'payment_detail_id' => $paymentDetailId,
                    'name' => 'Loan repayment',
                    'amount' => $repayment,
                    'credit' => $repayment,
                    'principal_repaid_derived' => $repayment,
                    'loan_transaction_type_id' => $repaymentTypeId,
                    'submitted_on' => $repayDate,
                    'created_on' => $repayDate,
                ], $now);
            }

            DB::table('loans')->where('id', $loan['id'])->update([
                'principal_repaid_derived' => $totalRepaid,
                'principal_outstanding_derived' => max($principal - $totalRepaid, 0),
            ]);
        }

        if (!empty($memberIds)) {
            $firstClient = $memberIds[0];
            $transferAmount = 5000;
            $bosaId = $accounts['bosa'][$firstClient];
            $fosaId = $accounts['fosa'][$firstClient];
            $branchId = $memberMeta[$firstClient]['branch_id'] ?? $branchIds[0];

            $paymentDetailId = $this->createPaymentDetail($userId, 'demo_seed_transfer', $now);
            $savingsTxns[] = $this->savingsTxnRow([
                'savings_id' => $bosaId,
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Transfer to FOSA',
                'amount' => $transferAmount,
                'debit' => $transferAmount,
                'balance' => $bosaBalances[$firstClient] - $transferAmount,
                'savings_transaction_type_id' => $withdrawalTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
            ], $now);
            $savingsTxns[] = $this->savingsTxnRow([
                'savings_id' => $fosaId,
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'payment_detail_id' => $paymentDetailId,
                'name' => 'Transfer from BOSA',
                'amount' => $transferAmount,
                'credit' => $transferAmount,
                'balance' => $fosaBalances[$firstClient] + $transferAmount,
                'savings_transaction_type_id' => $depositTypeId,
                'submitted_on' => $today,
                'created_on' => $today,
            ], $now);
            $bosaBalances[$firstClient] -= $transferAmount;
            $fosaBalances[$firstClient] += $transferAmount;
        }

        foreach (array_chunk($savingsTxns, 50) as $chunk) {
            DB::table('savings_transactions')->insert($chunk);
        }
        foreach (array_chunk($shareTxns, 50) as $chunk) {
            DB::table('share_transactions')->insert($chunk);
        }
        foreach (array_chunk($loanTxns, 50) as $chunk) {
            DB::table('loan_transactions')->insert($chunk);
        }

        foreach ($bosaBalances as $clientId => $balance) {
            if (isset($accounts['bosa'][$clientId])) {
                DB::table('savings')->where('id', $accounts['bosa'][$clientId])->update([
                    'balance_derived' => $balance,
                    'total_deposits_derived' => $balance,
                ]);
            }
        }
        foreach ($fosaBalances as $clientId => $balance) {
            if (isset($accounts['fosa'][$clientId])) {
                DB::table('savings')->where('id', $accounts['fosa'][$clientId])->update([
                    'balance_derived' => $balance,
                    'total_deposits_derived' => $balance,
                ]);
            }
        }
    }

    protected function savingsTxnRow(array $data, $now): array
    {
        return array_merge([
            'debit' => null,
            'credit' => null,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);
    }

    protected function loanTxnRow(array $data, $now): array
    {
        return array_merge([
            'debit' => null,
            'credit' => null,
            'principal_repaid_derived' => null,
            'status' => 'approved',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);
    }
}

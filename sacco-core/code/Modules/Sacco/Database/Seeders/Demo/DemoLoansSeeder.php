<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoLoansSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('loans')) {
            return;
        }

        $this->initRuntime();

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        $accounts = self::context('accounts', []);
        $products = self::context('productIds', []) ?: $this->resolveProducts();

        if (empty($memberIds) || empty($accounts['bosa'])) {
            return;
        }

        $userId = self::context('adminUserId');
        $currencyId = self::context('currencyId');
        $today = self::context('today');
        $now = self::context('now');
        $loanOfficerIds = self::context('loanOfficerIds', [$userId]);

        $fundId = DB::table('funds')->value('id') ?? 1;
        $strategyId = DB::table('loan_transaction_processing_strategies')->value('id') ?? 1;
        $loanPurposeId = DB::table('loan_purposes')->value('id') ?? 1;

        $loanCandidates = array_values(array_filter($memberIds, function ($clientId) use ($memberMeta) {
            return ($memberMeta[$clientId]['status'] ?? 'active') === 'active';
        }));

        $loanCount = (int)floor(count($loanCandidates) * 0.4);
        $loanClientIds = array_slice($loanCandidates, 0, max($loanCount, 1));

        $loanRows = [];
        $loanMeta = [];

        foreach ($loanClientIds as $i => $clientId) {
            $index = $memberMeta[$clientId]['index'] ?? ($i + 1);
            $branchId = $memberMeta[$clientId]['branch_id'] ?? self::context('branchIds')[0];
            $principal = rand(50000, 500000);
            $term = rand(12, 36);
            $statusRoll = $i % 10;

            if ($statusRoll === 0) {
                $status = 'closed';
            } elseif ($statusRoll <= 2) {
                $status = 'active';
            } else {
                $status = 'active';
            }

            $officerId = $loanOfficerIds[$i % count($loanOfficerIds)];
            $bosaId = $accounts['bosa'][$clientId] ?? null;

            $loanRows[] = [
                'created_by_id' => $userId,
                'client_type' => 'client',
                'client_id' => $clientId,
                'branch_id' => $branchId,
                'currency_id' => $currencyId,
                'loan_product_id' => $products['loan'],
                'loan_transaction_processing_strategy_id' => $strategyId,
                'fund_id' => $fundId,
                'loan_purpose_id' => $loanPurposeId,
                'loan_officer_id' => $officerId,
                'linked_savings_id' => $bosaId,
                'submitted_on_date' => $today,
                'approved_on_date' => $today,
                'disbursed_on_date' => $status !== 'pending' ? $today : null,
                'expected_disbursement_date' => $today,
                'expected_first_payment_date' => date('Y-m-d', strtotime('+1 month')),
                'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
                'expected_maturity_date' => date('Y-m-d', strtotime('+' . $term . ' months')),
                'external_id' => self::SEED_PREFIX . '-LOAN-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                'account_number' => 'LN-DEMO-' . str_pad((string)$index, 4, '0', STR_PAD_LEFT),
                'principal' => $principal,
                'approved_amount' => $principal,
                'applied_amount' => $principal,
                'interest_rate' => 12,
                'loan_term' => $term,
                'repayment_frequency' => 1,
                'repayment_frequency_type' => 'months',
                'interest_rate_type' => 'year',
                'interest_methodology' => 'flat',
                'amortization_method' => 'equal_installments',
                'status' => $status,
                'principal_disbursed_derived' => $principal,
                'principal_outstanding_derived' => $principal,
                'interest_outstanding_derived' => $principal * 0.12,
                'total_outstanding_derived' => $principal * 1.12,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $loanMeta[] = [
                'client_id' => $clientId,
                'principal' => $principal,
                'term' => $term,
                'status' => $status,
                'index' => $index,
            ];
        }

        foreach (array_chunk($loanRows, 25) as $chunk) {
            DB::table('loans')->insert($chunk);
        }

        $loans = DB::table('loans')
            ->where('external_id', 'like', self::SEED_PREFIX . '-LOAN-%')
            ->get(['id', 'client_id', 'principal', 'loan_term', 'status']);

        $accounts['loans'] = [];
        $scheduleRows = [];

        foreach ($loans as $loan) {
            $accounts['loans'][$loan->client_id] = [
                'id' => $loan->id,
                'principal' => (float)$loan->principal,
                'term' => (int)$loan->loan_term,
                'status' => $loan->status,
            ];

            if (Schema::hasTable('loan_repayment_schedules')) {
                $installment = round($loan->principal / max($loan->loan_term, 1), 2);
                for ($n = 1; $n <= min(3, $loan->loan_term); $n++) {
                    $dueDate = date('Y-m-d', strtotime('+' . $n . ' months'));
                    $scheduleRows[] = [
                        'loan_id' => $loan->id,
                        'installment' => $n,
                        'due_date' => $dueDate,
                        'from_date' => date('Y-m-d', strtotime('+' . ($n - 1) . ' months')),
                        'principal' => round($installment * 0.8, 2),
                        'interest' => round($installment * 0.2, 2),
                        'fees' => 0,
                        'penalties' => 0,
                        'total_due' => $installment,
                        'month' => date('m', strtotime($dueDate)),
                        'year' => date('Y', strtotime($dueDate)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($scheduleRows) && Schema::hasTable('loan_repayment_schedules')) {
            foreach (array_chunk($scheduleRows, 50) as $chunk) {
                DB::table('loan_repayment_schedules')->insert($chunk);
            }
        }

        self::setContextValue('accounts', $accounts);
    }
}

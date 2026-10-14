<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoSaccoRecordsSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('sacco')) {
            return;
        }

        $this->initRuntime();

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        $accounts = self::context('accounts', []);
        $products = self::context('productIds', []);
        $userId = self::context('adminUserId');
        $today = self::context('today');
        $now = self::context('now');
        $branchIds = self::context('branchIds', [1]);
        $tellerIds = self::context('tellerIds', []);

        if (empty($memberIds)) {
            return;
        }

        if (Schema::hasTable('sacco_financial_periods')) {
            DB::table('sacco_financial_periods')->insert([
                'name' => self::SEED_PREFIX . ' ' . date('Y') . ' Financial Year',
                'start_date' => date('Y-01-01'),
                'end_date' => date('Y-12-31'),
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $loanClientIds = array_keys($accounts['loans'] ?? []);
        foreach (array_slice($loanClientIds, 0, 20) as $i => $clientId) {
            $bosaId = $accounts['bosa'][$clientId] ?? null;
            $loanId = $accounts['loans'][$clientId]['id'] ?? null;
            $branchId = $memberMeta[$clientId]['branch_id'] ?? $branchIds[0];

            if ($bosaId && $loanId && Schema::hasTable('sacco_transfers')) {
                DB::table('sacco_transfers')->insert([
                    'created_by_id' => $userId,
                    'client_id' => $clientId,
                    'branch_id' => $branchId,
                    'transfer_type' => 'savings_to_loan',
                    'source_id' => $bosaId,
                    'destination_id' => $loanId,
                    'amount' => rand(5000, 20000),
                    'transfer_date' => date('Y-m-d', strtotime('-' . rand(5, 60) . ' days')),
                    'notes' => 'BOSA to loan repayment transfer',
                    'reference' => self::SEED_PREFIX . '-TRF-' . str_pad((string)($i + 1), 4, '0', STR_PAD_LEFT),
                    'status' => 'completed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($bosaId && $loanId && Schema::hasTable('sacco_standing_orders')) {
                DB::table('sacco_standing_orders')->insert([
                    'created_by_id' => $userId,
                    'client_id' => $clientId,
                    'source_savings_id' => $bosaId,
                    'destination_loan_id' => $loanId,
                    'amount' => rand(3000, 10000),
                    'frequency' => 'monthly',
                    'start_date' => $today,
                    'next_run_date' => date('Y-m-d', strtotime('+1 month')),
                    'status' => 'active',
                    'description' => 'Monthly loan repayment from BOSA',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_dividends') && !empty($products['share'])) {
            $dividendId = DB::table('sacco_dividends')->insertGetId([
                'created_by_id' => $userId,
                'share_product_id' => $products['share'],
                'name' => self::SEED_PREFIX . ' ' . date('Y', strtotime('-1 year')) . ' AGM Dividend',
                'dividend_per_share' => 5,
                'total_pool' => 250000,
                'declaration_date' => date('Y-m-d', strtotime('-6 months')),
                'payment_date' => date('Y-m-d', strtotime('-5 months')),
                'payout_method' => 'savings',
                'status' => 'processed',
                'agm_resolution' => 'AGM Resolution - 5 per share dividend',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (array_slice($memberIds, 0, 50) as $i => $memberId) {
                if (!isset($accounts['shares'][$memberId])) {
                    continue;
                }
                $sharesHeld = 20 + (($i % 10) * 5);
                DB::table('sacco_dividend_allocations')->insert([
                    'sacco_dividend_id' => $dividendId,
                    'share_id' => $accounts['shares'][$memberId],
                    'client_id' => $memberId,
                    'shares_held' => $sharesHeld,
                    'amount' => $sharesHeld * 5,
                    'status' => 'paid',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_kyc_documents')) {
            foreach (array_slice($memberIds, 0, 80) as $i => $clientId) {
                DB::table('sacco_kyc_documents')->insert([
                    'client_id' => $clientId,
                    'uploaded_by_id' => $userId,
                    'document_type' => $i % 2 === 0 ? 'national_id' : 'kra_pin',
                    'document_number' => (string)(30000000 + $i),
                    'status' => $i % 5 === 0 ? 'pending' : 'approved',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_checkoff_batches') && !empty($loanClientIds)) {
            $batchId = DB::table('sacco_checkoff_batches')->insertGetId([
                'created_by_id' => $userId,
                'employer_name' => self::SEED_PREFIX . ' County Government',
                'period' => date('Y-m'),
                'upload_date' => $today,
                'filename' => 'checkoff_' . date('Ym') . '.csv',
                'total_records' => min(15, count($loanClientIds)),
                'matched_records' => min(12, count($loanClientIds)),
                'unmatched_records' => 3,
                'total_amount' => 150000,
                'status' => 'reconciled',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (array_slice($loanClientIds, 0, 15) as $i => $clientId) {
                $loan = $accounts['loans'][$clientId];
                DB::table('sacco_checkoff_items')->insert([
                    'sacco_checkoff_batch_id' => $batchId,
                    'employee_id' => 'EMP-' . ($i + 1),
                    'employee_name' => 'Employee ' . ($i + 1),
                    'loan_account_number' => 'LN-DEMO-' . str_pad((string)($memberMeta[$clientId]['index'] ?? ($i + 1)), 4, '0', STR_PAD_LEFT),
                    'loan_id' => $loan['id'],
                    'client_id' => $clientId,
                    'amount' => rand(5000, 15000),
                    'status' => $i < 12 ? 'posted' : 'pending',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_teller_sessions') && !empty($tellerIds)) {
            $tellerId = DB::table('sacco_tellers')->whereIn('user_id', $tellerIds)->value('id');
            if ($tellerId) {
                DB::table('sacco_teller_sessions')->insert([
                    'sacco_teller_id' => $tellerId,
                    'user_id' => $tellerIds[0],
                    'opening_balance' => 50000,
                    'closing_balance' => 48500,
                    'expected_balance' => 48500,
                    'variance' => 0,
                    'opened_at' => date('Y-m-d 08:00:00', strtotime('-1 day')),
                    'closed_at' => date('Y-m-d 17:00:00', strtotime('-1 day')),
                    'status' => 'balanced',
                    'notes' => 'Demo teller session',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_aml_alerts')) {
            foreach (array_slice($memberIds, 0, 8) as $i => $clientId) {
                DB::table('sacco_aml_alerts')->insert([
                    'client_id' => $clientId,
                    'alert_type' => $i % 2 === 0 ? 'large_cash_deposit' : 'rapid_transfers',
                    'severity' => $i % 3 === 0 ? 'high' : 'medium',
                    'description' => 'Automated AML monitoring alert for demo member',
                    'amount' => rand(100000, 500000),
                    'status' => $i < 3 ? 'open' : 'closed',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_member_earmarks')) {
            foreach (array_slice($memberIds, 0, 5) as $i => $clientId) {
                DB::table('sacco_member_earmarks')->insert([
                    'created_by_id' => $userId,
                    'client_id' => $clientId,
                    'severity' => $i === 0 ? 'critical' : 'warning',
                    'restriction_type' => $i === 0 ? 'block_withdrawal' : 'alert',
                    'title' => self::SEED_PREFIX . ' compliance hold',
                    'message' => 'Demo earmark for member review',
                    'show_at_teller' => 1,
                    'expires_at' => date('Y-m-d', strtotime('+30 days')),
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_fixed_deposits')) {
            foreach (array_slice($memberIds, 0, 15) as $i => $clientId) {
                $bosaId = $accounts['bosa'][$clientId] ?? null;
                if (!$bosaId) {
                    continue;
                }
                $principal = rand(50000, 300000);
                DB::table('sacco_fixed_deposits')->insert([
                    'savings_id' => $bosaId,
                    'client_id' => $clientId,
                    'principal' => $principal,
                    'interest_rate' => 8.5,
                    'tenure_months' => 12,
                    'start_date' => date('Y-m-d', strtotime('-' . rand(1, 6) . ' months')),
                    'maturity_date' => date('Y-m-d', strtotime('+' . rand(6, 12) . ' months')),
                    'status' => 'active',
                    'renewal_option' => 'payout',
                    'accrued_interest' => round($principal * 0.04, 2),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('share_listings')) {
            foreach (array_slice($memberIds, 0, 5) as $i => $clientId) {
                if (!isset($accounts['shares'][$clientId])) {
                    continue;
                }
                DB::table('share_listings')->insert([
                    'created_by_id' => $userId,
                    'share_id' => $accounts['shares'][$clientId],
                    'seller_client_id' => $clientId,
                    'quantity' => 10,
                    'remaining_quantity' => 10 - $i,
                    'price_per_share' => 120,
                    'status' => 'active',
                    'notes' => 'Demo share listing',
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('sacco_beneficiaries')) {
            foreach (array_slice($memberIds, 0, 20) as $i => $clientId) {
                DB::table('sacco_beneficiaries')->insert([
                    'client_id' => $clientId,
                    'nickname' => $i % 2 === 0 ? 'Spouse' : 'Child',
                    'name' => 'Beneficiary ' . ($i + 1),
                    'phone' => $this->kenyanMobile(8000 + $i),
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

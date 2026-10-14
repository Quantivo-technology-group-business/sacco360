<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoFixedDeposit;
use Modules\Savings\Entities\Savings;

class FixedDepositService
{
    public function create(Savings $savings, array $data): SaccoFixedDeposit
    {
        $savings->loadMissing('savings_product');
        if ($savings->savings_product->account_product_type !== 'fixed_deposit') {
            throw new \RuntimeException('Account must be a fixed deposit product');
        }

        $tenure = (int)($data['tenure_months'] ?? 12);
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $maturityDate = date('Y-m-d', strtotime($startDate . ' +' . $tenure . ' months'));

        $fd = SaccoFixedDeposit::create([
            'savings_id' => $savings->id,
            'client_id' => $savings->client_id,
            'principal' => $data['principal'] ?? (new WingService())->getAccountBalance($savings->id),
            'interest_rate' => $data['interest_rate'] ?? $savings->savings_product->default_interest_rate ?? 0,
            'tenure_months' => $tenure,
            'start_date' => $startDate,
            'maturity_date' => $maturityDate,
            'renewal_option' => $data['renewal_option'] ?? 'payout',
            'penalty_rate' => $data['penalty_rate'] ?? 1,
            'lien_loan_id' => $data['lien_loan_id'] ?? null,
            'status' => 'active',
        ]);

        if (!empty($data['lien_loan_id'])) {
            (new EarmarkService())->create([
                'client_id' => $savings->client_id,
                'restriction_type' => 'block_withdrawal',
                'title' => 'FD Lien Hold',
                'message' => 'Fixed deposit lien for loan #' . $data['lien_loan_id'],
                'severity' => 'critical',
            ]);
        }

        return $fd;
    }

    public function processMaturities(?string $asOfDate = null): int
    {
        $asOfDate = $asOfDate ?? date('Y-m-d');
        $processed = 0;
        $fds = SaccoFixedDeposit::where('status', 'active')
            ->where('maturity_date', '<=', $asOfDate)
            ->get();

        foreach ($fds as $fd) {
            $this->mature($fd);
            $processed++;
        }

        return $processed;
    }

    public function mature(SaccoFixedDeposit $fd): void
    {
        DB::transaction(function () use ($fd) {
            $interest = $this->calculateInterest($fd);
            $fd->accrued_interest = $interest;
            $fd->status = 'matured';
            $fd->save();

            $savings = Savings::with('savings_product')->find($fd->savings_id);
            if (!$savings) {
                return;
            }

            if ($fd->renewal_option === 'payout') {
                (new SavingsChannelService())->postDeposit(
                    $savings,
                    $interest,
                    'fixed_deposit_maturity',
                    'FD-MAT-' . $fd->id
                );
            } elseif ($fd->renewal_option === 'renew_principal_interest') {
                (new SavingsChannelService())->postDeposit(
                    $savings,
                    $interest,
                    'fixed_deposit_maturity',
                    'FD-MAT-' . $fd->id
                );
                $fd->principal += $interest;
            }

            if (in_array($fd->renewal_option, ['renew_principal', 'renew_principal_interest'], true)) {
                $fd->start_date = $fd->maturity_date;
                $fd->maturity_date = date('Y-m-d', strtotime($fd->start_date . ' +' . $fd->tenure_months . ' months'));
                $fd->status = 'active';
                $fd->accrued_interest = 0;
            }

            $fd->save();
        });
    }

    public function prematureWithdraw(SaccoFixedDeposit $fd): float
    {
        $penalty = $fd->principal * ($fd->penalty_rate / 100);
        $interest = max(0, $this->calculateInterest($fd) - $penalty);
        $fd->status = 'premature_withdrawn';
        $fd->accrued_interest = $interest;
        $fd->save();

        return $interest;
    }

    public function calculateInterest(SaccoFixedDeposit $fd): float
    {
        $days = max(1, (strtotime($fd->maturity_date) - strtotime($fd->start_date)) / 86400);
        return round($fd->principal * ($fd->interest_rate / 100) * ($days / 365), 2);
    }
}

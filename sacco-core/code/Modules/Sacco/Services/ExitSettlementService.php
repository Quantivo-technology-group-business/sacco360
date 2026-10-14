<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\PaymentDetail;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Events\TransactionUpdated as LoanTransactionUpdated;
use Modules\Sacco\Entities\SaccoExitSettlement;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated as SavingsTransactionUpdated;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;

class ExitSettlementService
{
    public function calculate(int $clientId): array
    {
        $wingService = new WingService();
        $bosaBalance = $wingService->getSavingsBalanceByWing($clientId, WingService::WING_BOSA);
        $shareValue = (new GuarantorLockService())->getFreeShareValue($clientId);

        $loanBalance = (float)DB::table('loans')
            ->leftJoin('loan_transactions', 'loan_transactions.loan_id', '=', 'loans.id')
            ->where('loans.client_id', $clientId)
            ->whereIn('loans.status', ['active', 'disbursed'])
            ->selectRaw('COALESCE(SUM(loan_transactions.debit) - SUM(loan_transactions.credit), 0) as balance')
            ->value('balance');

        $loanOffset = min($loanBalance, $bosaBalance + $shareValue);
        $netPayout = max(0, $bosaBalance + $shareValue - $loanOffset);

        $client = Client::find($clientId);

        return [
            'client_id' => $clientId,
            'bosa_balance' => $bosaBalance,
            'share_value' => $shareValue,
            'loan_balance' => $loanBalance,
            'loan_offset' => $loanOffset,
            'net_payout' => $netPayout,
            'nominee_name' => $client->nominee_name ?? null,
        ];
    }

    public function createDraft(int $clientId, array $data): SaccoExitSettlement
    {
        $calc = $this->calculate($clientId);
        $settlement = new SaccoExitSettlement();
        $settlement->created_by_id = Auth::id();
        $settlement->client_id = $clientId;
        $settlement->exit_reason = $data['exit_reason'];
        $settlement->exit_date = $data['exit_date'] ?? date('Y-m-d');
        $settlement->bosa_balance = $calc['bosa_balance'];
        $settlement->share_value = $calc['share_value'];
        $settlement->loan_offset = $calc['loan_offset'];
        $settlement->net_payout = $calc['net_payout'];
        $settlement->payout_account = $data['payout_account'] ?? null;
        $settlement->nominee_name = $data['nominee_name'] ?? $calc['nominee_name'];
        $settlement->notes = $data['notes'] ?? null;
        $settlement->status = 'draft';
        $settlement->save();

        return $settlement;
    }

    public function approve(SaccoExitSettlement $settlement): SaccoExitSettlement
    {
        $settlement->approved_by_id = Auth::id();
        $settlement->status = 'approved';
        $settlement->save();

        $client = Client::find($settlement->client_id);
        if ($client) {
            $client->member_status_detail = $settlement->exit_reason === 'death' ? 'deceased' : 'exit';
            if ($settlement->exit_reason === 'death') {
                $client->status = 'deceased';
            }
            $client->save();
        }

        return $settlement;
    }

    public function process(SaccoExitSettlement $settlement): void
    {
        if ($settlement->status !== 'approved') {
            throw new \RuntimeException('Settlement must be approved before processing');
        }

        DB::transaction(function () use ($settlement) {
            $clientId = $settlement->client_id;
            $wingService = new WingService();
            $date = $settlement->exit_date;

            $loans = Loan::where('client_id', $clientId)
                ->whereIn('status', ['active', 'disbursed'])
                ->get();

            $remainingOffset = $settlement->loan_offset;
            foreach ($loans as $loan) {
                if ($remainingOffset <= 0) {
                    break;
                }
                $balance = (float)DB::table('loan_transactions')
                    ->where('loan_id', $loan->id)
                    ->selectRaw('COALESCE(SUM(debit) - SUM(credit), 0) as balance')
                    ->value('balance');
                $offsetAmount = min($balance, $remainingOffset);
                if ($offsetAmount <= 0) {
                    continue;
                }

                $paymentDetail = new PaymentDetail();
                $paymentDetail->created_by_id = Auth::id();
                $paymentDetail->payment_type_id = 1;
                $paymentDetail->transaction_type = 'exit_settlement';
                $paymentDetail->save();

                $repayment = new LoanTransaction();
                $repayment->created_by_id = Auth::id();
                $repayment->loan_id = $loan->id;
                $repayment->branch_id = $loan->branch_id;
                $repayment->payment_detail_id = $paymentDetail->id;
                $repayment->name = 'Exit Settlement Loan Offset';
                $repayment->loan_transaction_type_id = 2;
                $repayment->submitted_on = $date;
                $repayment->created_on = date('Y-m-d');
                $repayment->amount = $offsetAmount;
                $repayment->credit = $offsetAmount;
                $repayment->save();

                $remainingOffset -= $offsetAmount;
                event(new LoanTransactionUpdated($loan));
            }

            $shares = Share::with('share_product')->where('client_id', $clientId)->where('status', 'active')->get();
            foreach ($shares as $share) {
                if ($share->total_shares <= 0) {
                    continue;
                }
                $shareTxn = new ShareTransaction();
                $shareTxn->created_by_id = Auth::id();
                $shareTxn->share_id = $share->id;
                $shareTxn->branch_id = $share->branch_id;
                $shareTxn->name = 'Exit Settlement Share Redemption';
                $shareTxn->share_transaction_type_id = 2;
                $shareTxn->submitted_on = $date;
                $shareTxn->created_on = date('Y-m-d');
                $shareTxn->amount = $share->total_shares * ($share->share_product->nominal_price ?? 0);
                $shareTxn->debit = $share->total_shares;
                $shareTxn->save();
                $share->total_shares = 0;
                $share->status = 'closed';
                $share->save();
            }

            $bosa = $wingService->getBosaAccount($clientId);
            if ($bosa && $settlement->net_payout > 0) {
                $paymentDetail = new PaymentDetail();
                $paymentDetail->created_by_id = Auth::id();
                $paymentDetail->payment_type_id = 1;
                $paymentDetail->transaction_type = 'exit_settlement';
                $paymentDetail->account_number = $settlement->payout_account;
                $paymentDetail->save();

                $withdrawal = new SavingsTransaction();
                $withdrawal->created_by_id = Auth::id();
                $withdrawal->savings_id = $bosa->id;
                $withdrawal->branch_id = $bosa->branch_id;
                $withdrawal->payment_detail_id = $paymentDetail->id;
                $withdrawal->name = 'Exit Settlement Payout to ' . ($settlement->nominee_name ?? 'Member');
                $withdrawal->savings_transaction_type_id = 2;
                $withdrawal->submitted_on = $date;
                $withdrawal->created_on = date('Y-m-d');
                $withdrawal->amount = $settlement->net_payout;
                $withdrawal->debit = $settlement->net_payout;
                $withdrawal->save();
                event(new SavingsTransactionUpdated($bosa));
            }

            Savings::where('client_id', $clientId)->where('status', 'active')->update(['status' => 'closed']);

            $settlement->status = 'processed';
            $settlement->save();
        });
    }
}

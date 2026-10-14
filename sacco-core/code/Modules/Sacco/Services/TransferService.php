<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Core\Entities\PaymentDetail;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Events\TransactionUpdated as LoanTransactionUpdated;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoTransfer;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated as SavingsTransactionUpdated;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;

class TransferService
{
    public function execute(array $data): SaccoTransfer
    {
        return DB::transaction(function () use ($data) {
            $this->validateTransferWingRules($data);

            $transfer = new SaccoTransfer();
            $transfer->created_by_id = Auth::id();
            $transfer->client_id = $data['client_id'];
            $transfer->recipient_client_id = $data['recipient_client_id'] ?? null;
            $transfer->branch_id = $data['branch_id'] ?? null;
            $transfer->transfer_type = $data['transfer_type'];
            $transfer->source_id = $data['source_id'];
            $transfer->destination_id = $data['destination_id'];
            $transfer->amount = $data['amount'];
            $transfer->transfer_date = $data['transfer_date'] ?? date('Y-m-d');
            $transfer->notes = $data['notes'] ?? null;
            $transfer->reference = 'TRF-' . time();
            $transfer->status = 'completed';

            switch ($data['transfer_type']) {
                case 'savings_to_loan':
                    $this->savingsToLoan($data);
                    break;
                case 'savings_to_savings':
                    $this->savingsToSavings($data);
                    break;
                case 'share_to_savings':
                    $this->shareToSavings($data);
                    break;
                case 'savings_to_share':
                    $this->savingsToShare($data);
                    break;
                case 'savings_to_member_savings':
                    $this->savingsToMemberSavings($data);
                    break;
                default:
                    throw new \InvalidArgumentException('Unsupported transfer type');
            }

            $transfer->save();

            $client = Client::find($data['client_id']);
            if ($client) {
                (new TransactionNotificationService())->notify(
                    $client,
                    'transfer',
                    (float) $data['amount'],
                    $transfer->reference
                );
            }

            if (!empty($data['recipient_client_id'])) {
                $recipient = Client::find($data['recipient_client_id']);
                if ($recipient) {
                    (new TransactionNotificationService())->notify(
                        $recipient,
                        'transfer received',
                        (float) $data['amount'],
                        $transfer->reference
                    );
                }
            }

            return $transfer;
        });
    }

    protected function savingsToLoan(array $data): void
    {
        $savings = Savings::with('savings_product')->findOrFail($data['source_id']);
        $loan = Loan::findOrFail($data['destination_id']);
        $amount = $data['amount'];
        $date = $data['transfer_date'] ?? date('Y-m-d');

        $balance = $this->getSavingsBalance($savings->id);
        if ($balance < $amount) {
            throw new \RuntimeException('Insufficient savings balance');
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $data['payment_type_id'] ?? 1;
        $paymentDetail->transaction_type = 'sacco_transfer';
        $paymentDetail->description = 'BOSA/FOSA transfer - loan repayment';
        $paymentDetail->save();

        $withdrawal = new SavingsTransaction();
        $withdrawal->created_by_id = Auth::id();
        $withdrawal->savings_id = $savings->id;
        $withdrawal->branch_id = $savings->branch_id;
        $withdrawal->payment_detail_id = $paymentDetail->id;
        $withdrawal->name = 'Transfer to Loan #' . $loan->id;
        $withdrawal->savings_transaction_type_id = 7;
        $withdrawal->submitted_on = $date;
        $withdrawal->created_on = date('Y-m-d');
        $withdrawal->amount = $amount;
        $withdrawal->debit = $amount;
        $withdrawal->reversible = 1;
        $withdrawal->save();

        $this->postSavingsJournal($savings, $withdrawal, 'withdrawal');

        $loanPayment = new PaymentDetail();
        $loanPayment->created_by_id = Auth::id();
        $loanPayment->payment_type_id = $data['payment_type_id'] ?? 1;
        $loanPayment->transaction_type = 'sacco_transfer';
        $loanPayment->description = 'Repayment via savings transfer';
        $loanPayment->save();

        $repayment = new LoanTransaction();
        $repayment->created_by_id = Auth::id();
        $repayment->loan_id = $loan->id;
        $repayment->branch_id = $loan->branch_id;
        $repayment->payment_detail_id = $loanPayment->id;
        $repayment->name = 'Repayment via Savings Transfer';
        $repayment->loan_transaction_type_id = 2;
        $repayment->submitted_on = $date;
        $repayment->created_on = date('Y-m-d');
        $repayment->amount = $amount;
        $repayment->credit = $amount;
        $repayment->save();

        event(new SavingsTransactionUpdated($savings));
        event(new LoanTransactionUpdated($loan));
    }

    protected function savingsToSavings(array $data): void
    {
        $source = Savings::with('savings_product')->findOrFail($data['source_id']);
        $dest = Savings::with('savings_product')->findOrFail($data['destination_id']);
        $amount = $data['amount'];
        $date = $data['transfer_date'] ?? date('Y-m-d');

        if ($this->getSavingsBalance($source->id) < $amount) {
            throw new \RuntimeException('Insufficient savings balance');
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $data['payment_type_id'] ?? 1;
        $paymentDetail->transaction_type = 'sacco_transfer';
        $paymentDetail->save();

        $withdrawal = new SavingsTransaction();
        $withdrawal->created_by_id = Auth::id();
        $withdrawal->savings_id = $source->id;
        $withdrawal->branch_id = $source->branch_id;
        $withdrawal->payment_detail_id = $paymentDetail->id;
        $withdrawal->name = 'Transfer to Savings #' . $dest->id;
        $withdrawal->savings_transaction_type_id = 8;
        $withdrawal->submitted_on = $date;
        $withdrawal->created_on = date('Y-m-d');
        $withdrawal->amount = $amount;
        $withdrawal->debit = $amount;
        $withdrawal->save();
        $this->postSavingsJournal($source, $withdrawal, 'withdrawal');

        $deposit = new SavingsTransaction();
        $deposit->created_by_id = Auth::id();
        $deposit->savings_id = $dest->id;
        $deposit->branch_id = $dest->branch_id;
        $deposit->payment_detail_id = $paymentDetail->id;
        $deposit->name = 'Transfer from Savings #' . $source->id;
        $deposit->savings_transaction_type_id = 8;
        $deposit->submitted_on = $date;
        $deposit->created_on = date('Y-m-d');
        $deposit->amount = $amount;
        $deposit->credit = $amount;
        $deposit->save();
        $this->postSavingsJournal($dest, $deposit, 'deposit');

        event(new SavingsTransactionUpdated($source));
        event(new SavingsTransactionUpdated($dest));
    }

    protected function shareToSavings(array $data): void
    {
        $share = Share::with('share_product')->findOrFail($data['source_id']);
        $savings = Savings::with('savings_product')->findOrFail($data['destination_id']);
        $amount = $data['amount'];
        $date = $data['transfer_date'] ?? date('Y-m-d');

        $freeShares = ($share->total_shares ?? 0) - ($share->locked_shares ?? 0);
        $shareValue = $freeShares * ($share->share_product->nominal_price ?? 0);
        if ($shareValue < $amount) {
            throw new \RuntimeException('Insufficient free share value');
        }

        $sharesToRedeem = (int)ceil($amount / ($share->share_product->nominal_price ?: 1));
        $share->total_shares = max(0, $share->total_shares - $sharesToRedeem);
        $share->save();

        $shareTxn = new ShareTransaction();
        $shareTxn->created_by_id = Auth::id();
        $shareTxn->share_id = $share->id;
        $shareTxn->branch_id = $share->branch_id;
        $shareTxn->name = 'Redeem for Savings Transfer';
        $shareTxn->share_transaction_type_id = 2;
        $shareTxn->submitted_on = $date;
        $shareTxn->created_on = date('Y-m-d');
        $shareTxn->amount = $amount;
        $shareTxn->debit = $sharesToRedeem;
        $shareTxn->save();

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = 1;
        $paymentDetail->transaction_type = 'sacco_transfer';
        $paymentDetail->save();

        $deposit = new SavingsTransaction();
        $deposit->created_by_id = Auth::id();
        $deposit->savings_id = $savings->id;
        $deposit->branch_id = $savings->branch_id;
        $deposit->payment_detail_id = $paymentDetail->id;
        $deposit->name = 'Transfer from Share #' . $share->id;
        $deposit->savings_transaction_type_id = 8;
        $deposit->submitted_on = $date;
        $deposit->created_on = date('Y-m-d');
        $deposit->amount = $amount;
        $deposit->credit = $amount;
        $deposit->save();
        $this->postSavingsJournal($savings, $deposit, 'deposit');

        event(new SavingsTransactionUpdated($savings));
    }

    protected function validateTransferWingRules(array $data): void
    {
        $wingService = new WingService();
        $type = $data['transfer_type'];

        if ($type === 'savings_to_loan') {
            $source = Savings::with('savings_product')->findOrFail($data['source_id']);
            return;
        }

        if (in_array($type, ['savings_to_savings', 'savings_to_share'], true)) {
            $source = Savings::with('savings_product')->findOrFail($data['source_id']);
            $dest = $type === 'savings_to_savings'
                ? Savings::with('savings_product')->findOrFail($data['destination_id'])
                : null;
            $check = $wingService->validateTransfer($source, $dest ?? $source, $type);
            if (!$check['allowed']) {
                throw new \RuntimeException(implode(' ', $check['violations']));
            }
        }

        if ($type === 'share_to_savings') {
            $dest = Savings::with('savings_product')->findOrFail($data['destination_id']);
            $check = $wingService->validateTransfer($dest, $dest, $type);
            if (!$check['allowed']) {
                throw new \RuntimeException(implode(' ', $check['violations']));
            }
        }

        if ($type === 'savings_to_member_savings') {
            $source = Savings::with('savings_product')->findOrFail($data['source_id']);
            if ((int) $data['client_id'] === (int) ($data['recipient_client_id'] ?? 0)) {
                throw new \RuntimeException('Cannot transfer to yourself');
            }
            $earmark = (new EarmarkService())->validateTransaction($data['client_id'], 'withdrawal', $data['amount']);
            if (!$earmark['allowed']) {
                throw new \RuntimeException(implode('; ', $earmark['violations']));
            }
            $withdrawCheck = $wingService->validateWithdrawalAmount($source, $data['amount']);
            if (!$withdrawCheck['allowed']) {
                throw new \RuntimeException(implode(' ', $withdrawCheck['violations']));
            }
        }
    }

    public function resolveMemberByNumber(string $memberNumber): ?Client
    {
        return Client::where('member_number', $memberNumber)
            ->orWhere('external_id', $memberNumber)
            ->first();
    }

    protected function savingsToMemberSavings(array $data): void
    {
        $source = Savings::with('savings_product')->findOrFail($data['source_id']);
        $dest = Savings::with('savings_product')->findOrFail($data['destination_id']);
        $amount = $data['amount'];
        $date = $data['transfer_date'] ?? date('Y-m-d');
        $recipientLabel = $data['recipient_member_number'] ?? ('Member #' . ($data['recipient_client_id'] ?? ''));

        if ($this->getSavingsBalance($source->id) < $amount) {
            throw new \RuntimeException('Insufficient savings balance');
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $data['payment_type_id'] ?? 1;
        $paymentDetail->transaction_type = 'member_transfer';
        $paymentDetail->description = 'Transfer to ' . $recipientLabel;
        $paymentDetail->save();

        $withdrawal = new SavingsTransaction();
        $withdrawal->created_by_id = Auth::id();
        $withdrawal->savings_id = $source->id;
        $withdrawal->branch_id = $source->branch_id;
        $withdrawal->payment_detail_id = $paymentDetail->id;
        $withdrawal->name = 'Transfer to ' . $recipientLabel;
        $withdrawal->savings_transaction_type_id = 8;
        $withdrawal->submitted_on = $date;
        $withdrawal->created_on = date('Y-m-d');
        $withdrawal->amount = $amount;
        $withdrawal->debit = $amount;
        $withdrawal->save();
        $this->postSavingsJournal($source, $withdrawal, 'withdrawal');

        $deposit = new SavingsTransaction();
        $deposit->created_by_id = Auth::id();
        $deposit->savings_id = $dest->id;
        $deposit->branch_id = $dest->branch_id;
        $deposit->payment_detail_id = $paymentDetail->id;
        $deposit->name = 'Transfer from member #' . ($data['client_id'] ?? '');
        $deposit->savings_transaction_type_id = 8;
        $deposit->submitted_on = $date;
        $deposit->created_on = date('Y-m-d');
        $deposit->amount = $amount;
        $deposit->credit = $amount;
        $deposit->save();
        $this->postSavingsJournal($dest, $deposit, 'deposit');

        event(new SavingsTransactionUpdated($source));
        event(new SavingsTransactionUpdated($dest));
    }

    protected function savingsToShare(array $data): void
    {
        $savings = Savings::with('savings_product')->findOrFail($data['source_id']);
        $share = Share::with('share_product')->findOrFail($data['destination_id']);
        $amount = $data['amount'];
        $date = $data['transfer_date'] ?? date('Y-m-d');

        if ($this->getSavingsBalance($savings->id) < $amount) {
            throw new \RuntimeException('Insufficient savings balance');
        }

        $nominalPrice = $share->share_product->nominal_price ?: 1;
        $sharesToAdd = (int)floor($amount / $nominalPrice);
        if ($sharesToAdd <= 0) {
            throw new \RuntimeException('Amount too small to purchase shares');
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $data['payment_type_id'] ?? 1;
        $paymentDetail->transaction_type = 'sacco_transfer';
        $paymentDetail->description = 'BOSA to share purchase';
        $paymentDetail->save();

        $withdrawal = new SavingsTransaction();
        $withdrawal->created_by_id = Auth::id();
        $withdrawal->savings_id = $savings->id;
        $withdrawal->branch_id = $savings->branch_id;
        $withdrawal->payment_detail_id = $paymentDetail->id;
        $withdrawal->name = 'Transfer to Share #' . $share->id;
        $withdrawal->savings_transaction_type_id = 8;
        $withdrawal->submitted_on = $date;
        $withdrawal->created_on = date('Y-m-d');
        $withdrawal->amount = $amount;
        $withdrawal->debit = $amount;
        $withdrawal->save();
        $this->postSavingsJournal($savings, $withdrawal, 'withdrawal');

        $shareTxn = new ShareTransaction();
        $shareTxn->created_by_id = Auth::id();
        $shareTxn->share_id = $share->id;
        $shareTxn->branch_id = $share->branch_id;
        $shareTxn->name = 'Purchase via BOSA Transfer';
        $shareTxn->share_transaction_type_id = 1;
        $shareTxn->submitted_on = $date;
        $shareTxn->created_on = date('Y-m-d');
        $shareTxn->amount = $amount;
        $shareTxn->credit = $sharesToAdd;
        $shareTxn->save();

        $share->total_shares += $sharesToAdd;
        $share->save();

        event(new SavingsTransactionUpdated($savings));
    }

    protected function getSavingsBalance(int $savingsId): float
    {
        return (float)DB::table('savings_transactions')
            ->where('savings_id', $savingsId)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance');
    }

    protected function postSavingsJournal(Savings $savings, SavingsTransaction $txn, string $type): void
    {
        if ($savings->savings_product->accounting_rule !== 'cash') {
            return;
        }
        $date = $txn->submitted_on;
        $dateParts = explode('-', $date);
        $prefix = 'S' . $txn->id;

        if ($type === 'deposit') {
            $this->createJournalEntry($prefix, $savings, $savings->savings_product->savings_control_chart_of_account_id, 0, $txn->amount, 'savings_deposit', $date, $dateParts);
            $this->createJournalEntry($prefix, $savings, $savings->savings_product->savings_reference_chart_of_account_id, $txn->amount, 0, 'savings_deposit', $date, $dateParts);
        } else {
            $this->createJournalEntry($prefix, $savings, $savings->savings_product->savings_reference_chart_of_account_id, 0, $txn->amount, 'savings_withdrawal', $date, $dateParts);
            $this->createJournalEntry($prefix, $savings, $savings->savings_product->savings_control_chart_of_account_id, $txn->amount, 0, 'savings_withdrawal', $date, $dateParts);
        }
    }

    protected function createJournalEntry($prefix, $savings, $coaId, $debit, $credit, $type, $date, $dateParts): void
    {
        $entry = new JournalEntry();
        $entry->created_by_id = Auth::id();
        $entry->transaction_number = $prefix;
        $entry->branch_id = $savings->branch_id;
        $entry->currency_id = $savings->currency_id;
        $entry->chart_of_account_id = $coaId;
        $entry->transaction_type = $type;
        $entry->date = $date;
        $entry->month = $dateParts[1] ?? date('m');
        $entry->year = $dateParts[0] ?? date('Y');
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->reference = $savings->id;
        $entry->save();
    }
}

<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Core\Entities\PaymentDetail;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated;

class SavingsChannelService
{
    public function postWithdrawal(Savings $savings, float $amount, string $channel, string $reference, ?string $date = null): SavingsTransaction
    {
        $date = $date ?? date('Y-m-d');
        $savings->loadMissing('savings_product');

        $wingCheck = (new WingService())->isWithdrawable($savings);
        if (!$wingCheck['allowed']) {
            throw new \RuntimeException(implode(' ', $wingCheck['violations']));
        }

        $balance = (new WingService())->getAccountBalance($savings->id);
        if ($amount > $balance) {
            $product = $savings->savings_product;
            if (!$product || !$product->allow_overdraft || $amount > ($product->overdraft_limit ?? 0)) {
                throw new \RuntimeException('Insufficient balance');
            }
        }

        return DB::transaction(function () use ($savings, $amount, $channel, $reference, $date) {
            $paymentDetail = new PaymentDetail();
            $paymentDetail->created_by_id = Auth::id();
            $paymentDetail->payment_type_id = 1;
            $paymentDetail->transaction_type = $channel;
            $paymentDetail->receipt = $reference;
            $paymentDetail->save();

            $txn = new SavingsTransaction();
            $txn->created_by_id = Auth::id();
            $txn->savings_id = $savings->id;
            $txn->branch_id = $savings->branch_id;
            $txn->payment_detail_id = $paymentDetail->id;
            $txn->name = ucfirst($channel) . ' Withdrawal';
            $txn->savings_transaction_type_id = 2;
            $txn->submitted_on = $date;
            $txn->created_on = date('Y-m-d');
            $txn->amount = $amount;
            $txn->debit = $amount;
            $txn->reversible = 1;
            $txn->save();

            $this->postJournal($savings, $txn, 'withdrawal');
            event(new TransactionUpdated($savings));

            return $txn;
        });
    }

    public function postDeposit(Savings $savings, float $amount, string $channel, string $reference, ?string $date = null): SavingsTransaction
    {
        $date = $date ?? date('Y-m-d');
        $savings->loadMissing('savings_product');

        return DB::transaction(function () use ($savings, $amount, $channel, $reference, $date) {
            $paymentDetail = new PaymentDetail();
            $paymentDetail->created_by_id = Auth::id();
            $paymentDetail->payment_type_id = 1;
            $paymentDetail->transaction_type = $channel;
            $paymentDetail->receipt = $reference;
            $paymentDetail->save();

            $txn = new SavingsTransaction();
            $txn->created_by_id = Auth::id();
            $txn->savings_id = $savings->id;
            $txn->branch_id = $savings->branch_id;
            $txn->payment_detail_id = $paymentDetail->id;
            $txn->name = ucfirst($channel) . ' Deposit';
            $txn->savings_transaction_type_id = 1;
            $txn->submitted_on = $date;
            $txn->created_on = date('Y-m-d');
            $txn->amount = $amount;
            $txn->credit = $amount;
            $txn->save();

            $this->postJournal($savings, $txn, 'deposit');
            event(new TransactionUpdated($savings));

            return $txn;
        });
    }

    protected function postJournal(Savings $savings, SavingsTransaction $txn, string $type): void
    {
        if (!$savings->savings_product || $savings->savings_product->accounting_rule !== 'cash') {
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

    protected function createJournalEntry($prefix, Savings $savings, $coaId, $debit, $credit, $type, $date, $dateParts): void
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

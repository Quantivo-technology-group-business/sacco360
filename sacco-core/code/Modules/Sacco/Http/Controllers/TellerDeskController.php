<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Core\Entities\PaymentDetail;
use Modules\Core\Entities\PaymentType;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Sacco\Entities\SaccoTeller;
use Modules\Sacco\Entities\SaccoTellerSession;
use Modules\Sacco\Services\TellerService;
use Modules\Accounting\Entities\JournalEntry;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated;

class TellerDeskController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.teller.index']);
    }

    // ─── CLIENT PANEL (AJAX) ─────────────────────────────────────────────────
    // Returns a client's active savings accounts and active loans for the teller panel.

    public function clientPanel(Request $request)
    {
        $clientId = $request->input('client_id');
        $request->merge(['client_id' => $clientId]);
        $request->validate(['client_id' => 'required|exists:clients,id']);
        $client = Client::findOrFail($clientId);

        $savings = Savings::with('savings_product')
            ->where('client_id', $client->id)
            ->whereIn('status', ['active', 'approved'])
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'product'     => optional($s->savings_product)->name,
                'balance'     => (float)($s->balance ?? 0),
                'account_no'  => $s->account_number ?? ('SAV-' . $s->id),
                'status'      => $s->status,
            ]);

        $loans = Loan::with('loan_product')
            ->where('client_id', $client->id)
            ->where('status', 'active')
            ->get()
            ->map(fn($l) => [
                'id'            => $l->id,
                'product'       => optional($l->loan_product)->name,
                'outstanding'   => (float)($l->total_outstanding ?? 0),
                'next_payment'  => $l->next_payment_date ?? null,
                'account_no'    => 'LN-' . $l->id,
            ]);

        return response()->json([
            'client' => [
                'id'            => $client->id,
                'name'          => $client->first_name . ' ' . $client->last_name,
                'first_name'    => $client->first_name,
                'last_name'     => $client->last_name,
                'phone'         => $client->mobile,
                'email'         => $client->email,
                'member_number' => $client->member_number ?? $client->account_number ?? ('CLT-' . $client->id),
                'photo'         => $client->photo ? url('storage/' . $client->photo) : null,
                'address'       => $client->address,
                'status'        => $client->status,
            ],
            'savings' => $savings,
            'loans'   => $loans,
        ]);
    }

    // ─── RECEIVE DEPOSIT ─────────────────────────────────────────────────────
    // Teller posts a savings deposit from the desk. Delegates to existing savings logic.

    public function receiveDeposit(Request $request)
    {
        $request->validate([
            'savings_id'      => ['required', 'exists:savings,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'date'            => ['required', 'date'],
            'payment_type_id' => ['required', 'exists:payment_types,id'],
        ]);

        // Teller limit check
        if (function_exists('sacco_validate_teller_transaction')) {
            $check = sacco_validate_teller_transaction('deposit', (float)$request->amount);
            if (!$check['allowed']) {
                return response()->json(['success' => false, 'message' => implode(' | ', $check['violations'])], 422);
            }
        }

        $savings = Savings::with('savings_product')->findOrFail($request->savings_id);

        // Payment detail
        $pd                   = new PaymentDetail();
        $pd->created_by_id    = Auth::id();
        $pd->payment_type_id  = $request->payment_type_id;
        $pd->transaction_type = 'savings_transaction';
        $pd->cheque_number    = $request->cheque_number;
        $pd->receipt          = $request->receipt_no;
        $pd->notes            = $request->notes;
        $pd->save();

        $txn                              = new SavingsTransaction();
        $txn->created_by_id               = Auth::id();
        $txn->savings_id                  = $savings->id;
        $txn->branch_id                   = $savings->branch_id;
        $txn->payment_detail_id           = $pd->id;
        $txn->name                        = 'Deposit';
        $txn->savings_transaction_type_id = 1;
        $txn->submitted_on                = $request->date;
        $txn->created_on                  = date('Y-m-d');
        $txn->reversible                  = 1;
        $txn->amount                      = $request->amount;
        $txn->credit                      = $request->amount;
        $txn->save();

        // Record in teller till
        if (function_exists('sacco_record_teller_transaction')) {
            sacco_record_teller_transaction('deposit', (float)$request->amount);
        } else {
            $teller = SaccoTeller::where('user_id', Auth::id())->where('status', 'open')->first();
            if ($teller) {
                app(TellerService::class)->recordTransaction($teller, (float)$request->amount, 'deposit');
            }
        }

        activity()->log('Teller Deposit — Savings #' . $savings->id . ' — Amount: ' . $request->amount);

        return response()->json([
            'success'        => true,
            'message'        => 'Deposit of ' . number_format($request->amount, 2) . ' received successfully.',
            'transaction_id' => $txn->id,
            'receipt_url'    => url('sacco/teller/receipt/savings/' . $txn->id),
        ]);
    }

    // ─── PROCESS WITHDRAWAL ───────────────────────────────────────────────────
    // Teller processes a cash withdrawal from a savings account.

    public function processWithdrawal(Request $request)
    {
        $request->validate([
            'savings_id'      => ['required', 'exists:savings,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'date'            => ['required', 'date'],
            'payment_type_id' => ['required', 'exists:payment_types,id'],
        ]);

        $savings = Savings::with('savings_product')->findOrFail($request->savings_id);
        $amount = (float) $request->amount;

        // Check earmarks
        if (function_exists('sacco_check_earmarks')) {
            $earmarkCheck = sacco_check_earmarks($savings->client_id, 'withdrawal', $amount);
            if (!$earmarkCheck['allowed']) {
                return response()->json(['success' => false, 'message' => implode(' | ', $earmarkCheck['violations'])], 422);
            }
        }

        // Teller limit check
        if (function_exists('sacco_validate_teller_transaction')) {
            $check = sacco_validate_teller_transaction('withdrawal', $amount);
            if (!$check['allowed']) {
                return response()->json(['success' => false, 'message' => implode(' | ', $check['violations'])], 422);
            }
        }

        // SACCO wing-specific withdrawal validation
        if (function_exists('sacco_validate_withdrawal')) {
            $wingCheck = sacco_validate_withdrawal($savings, $amount);
            if (!$wingCheck['allowed']) {
                return response()->json(['success' => false, 'message' => implode(' | ', $wingCheck['violations'])], 422);
            }
        }

        // Balance check
        $balance = $savings->transactions()->where('reversed', 0)->sum('credit')
                 - $savings->transactions()->where('reversed', 0)->sum('debit');

        if ($amount > $balance && optional($savings->savings_product)->allow_overdraft == 0) {
            return response()->json(['success' => false, 'message' => 'Insufficient balance for this withdrawal.'], 422);
        }

        if ($amount > $balance && optional($savings->savings_product)->allow_overdraft == 1
            && $amount > optional($savings->savings_product)->overdraft_limit) {
            return response()->json(['success' => false, 'message' => 'Amount exceeds overdraft limit.'], 422);
        }

        // Payment detail
        $pd                   = new PaymentDetail();
        $pd->created_by_id    = Auth::id();
        $pd->payment_type_id  = $request->payment_type_id;
        $pd->transaction_type = 'savings_transaction';
        $pd->cheque_number    = $request->cheque_number;
        $pd->receipt          = $request->receipt_no;
        $pd->notes            = $request->notes;
        $pd->save();

        // Create savings transaction
        $txn                              = new SavingsTransaction();
        $txn->created_by_id               = Auth::id();
        $txn->savings_id                  = $savings->id;
        $txn->branch_id                   = $savings->branch_id;
        $txn->payment_detail_id           = $pd->id;
        $txn->name                        = 'Withdrawal';
        $txn->savings_transaction_type_id = 2; // withdrawal
        $txn->submitted_on                = $request->date;
        $txn->created_on                  = date('Y-m-d');
        $txn->reversible                  = 1;
        $txn->amount                      = $amount;
        $txn->debit                       = $amount;

        // Check maker-checker threshold
        $needsApproval = function_exists('sacco_requires_maker_checker') && sacco_requires_maker_checker($amount);
        if ($needsApproval) {
            $txn->status = 'pending';
            $txn->requires_approval = 1;
        }
        $txn->save();

        // Queue for approval if needed
        if ($needsApproval && function_exists('sacco_queue_transaction_approval')) {
            sacco_queue_transaction_approval('savings_transaction', $txn->id, $amount, 'Large withdrawal pending approval');
            return response()->json([
                'success' => true,
                'message' => 'Withdrawal of ' . number_format($amount, 2) . ' submitted for approval.',
                'pending_approval' => true,
                'transaction_id' => $txn->id,
            ]);
        }

        // Post journal entries if accounting rule is cash-based
        if (optional($savings->savings_product)->accounting_rule == 'cash') {
            $date = explode('-', $request->date);

            // Credit the reference account (cash goes out)
            $je1 = new JournalEntry();
            $je1->created_by_id = Auth::id();
            $je1->transaction_number = 'S' . $txn->id;
            $je1->branch_id = $savings->branch_id;
            $je1->currency_id = $savings->currency_id;
            $je1->chart_of_account_id = $savings->savings_product->savings_reference_chart_of_account_id;
            $je1->transaction_type = 'savings_withdrawal';
            $je1->date = $request->date;
            $je1->month = $date[1];
            $je1->year = $date[0];
            $je1->credit = $amount;
            $je1->reference = $savings->id;
            $je1->save();

            // Debit the control account (reduce liability)
            $je2 = new JournalEntry();
            $je2->created_by_id = Auth::id();
            $je2->transaction_number = 'S' . $txn->id;
            $je2->branch_id = $savings->branch_id;
            $je2->currency_id = $savings->currency_id;
            $je2->chart_of_account_id = $savings->savings_product->savings_control_chart_of_account_id;
            $je2->transaction_type = 'savings_withdrawal';
            $je2->date = $request->date;
            $je2->month = $date[1];
            $je2->year = $date[0];
            $je2->debit = $amount;
            $je2->reference = $savings->id;
            $je2->save();
        }

        // Record in teller till (cash out)
        if (function_exists('sacco_record_teller_transaction')) {
            sacco_record_teller_transaction('withdrawal', $amount);
        } else {
            $teller = SaccoTeller::where('user_id', Auth::id())->where('status', 'open')->first();
            if ($teller) {
                app(TellerService::class)->recordTransaction($teller, $amount, 'withdrawal');
            }
        }

        // Send transaction notification
        if (class_exists(\Modules\Sacco\Services\TransactionNotificationService::class)) {
            $member = \Modules\Client\Entities\Client::find($savings->client_id);
            if ($member) {
                (new \Modules\Sacco\Services\TransactionNotificationService())->notify($member, 'withdrawal', $amount, 'S' . $txn->id);
            }
        }

        // Fire transaction updated event
        if (class_exists(TransactionUpdated::class)) {
            event(new TransactionUpdated($savings));
        }

        activity()->log('Teller Withdrawal — Savings #' . $savings->id . ' — Amount: ' . $amount);

        return response()->json([
            'success'        => true,
            'message'        => 'Withdrawal of ' . number_format($amount, 2) . ' processed successfully.',
            'transaction_id' => $txn->id,
            'receipt_url'    => url('sacco/teller/receipt/withdrawal/' . $txn->id),
        ]);
    }

    // ─── RECEIVE LOAN REPAYMENT ───────────────────────────────────────────────

    public function receiveLoanRepayment(Request $request)
    {
        $request->validate([
            'loan_id'         => ['required', 'exists:loans,id'],
            'amount'          => ['required', 'numeric', 'min:0.01'],
            'date'            => ['required', 'date'],
            'payment_type_id' => ['required', 'exists:payment_types,id'],
        ]);

        if (function_exists('sacco_validate_teller_transaction')) {
            $check = sacco_validate_teller_transaction('repayment', (float)$request->amount);
            if (!$check['allowed']) {
                return response()->json(['success' => false, 'message' => implode(' | ', $check['violations'])], 422);
            }
        }

        $loan = Loan::with('loan_product')->findOrFail($request->loan_id);

        $pd                   = new PaymentDetail();
        $pd->created_by_id    = Auth::id();
        $pd->payment_type_id  = $request->payment_type_id;
        $pd->transaction_type = 'loan_transaction';
        $pd->cheque_number    = $request->cheque_number;
        $pd->receipt          = $request->receipt_no;
        $pd->notes            = $request->notes;
        $pd->save();

        $txn                          = new LoanTransaction();
        $txn->created_by_id           = Auth::id();
        $txn->loan_id                 = $loan->id;
        $txn->payment_detail_id       = $pd->id;
        $txn->name                    = 'Repayment';
        $txn->loan_transaction_type_id = 2;
        $txn->submitted_on            = $request->date;
        $txn->created_on              = date('Y-m-d');
        $txn->amount                  = $request->amount;
        $txn->credit                  = $request->amount;
        $txn->save();

        // Fire loan updated event if it exists
        if (class_exists('\Modules\Loan\Events\TransactionUpdated')) {
            event(new \Modules\Loan\Events\TransactionUpdated($loan));
        }

        // Record in teller till
        if (function_exists('sacco_record_teller_transaction')) {
            sacco_record_teller_transaction('deposit', (float)$request->amount); // cash in = deposit
        } else {
            $teller = SaccoTeller::where('user_id', Auth::id())->where('status', 'open')->first();
            if ($teller) {
                app(TellerService::class)->recordTransaction($teller, (float)$request->amount, 'deposit');
            }
        }

        activity()->log('Teller Repayment — Loan #' . $loan->id . ' — Amount: ' . $request->amount);

        return response()->json([
            'success'        => true,
            'message'        => 'Repayment of ' . number_format($request->amount, 2) . ' received successfully.',
            'transaction_id' => $txn->id,
            'receipt_url'    => url('sacco/teller/receipt/loan/' . $txn->id),
        ]);
    }

    // ─── RECEIPT: SAVINGS DEPOSIT ─────────────────────────────────────────────

    public function receiptSavings($id)
    {
        $txn     = SavingsTransaction::with('savings.client', 'savings.savings_product', 'payment_detail.payment_type')->findOrFail($id);
        $teller  = SaccoTeller::where('user_id', Auth::id())->first();
        return theme_view('sacco::teller.receipt_savings', compact('txn', 'teller'));
    }

    // ─── RECEIPT: WITHDRAWAL ──────────────────────────────────────────────────

    public function receiptWithdrawal($id)
    {
        $txn    = SavingsTransaction::with('savings.client', 'savings.savings_product', 'payment_detail.payment_type')->findOrFail($id);
        $teller = SaccoTeller::where('user_id', Auth::id())->first();
        return theme_view('sacco::teller.receipt_withdrawal', compact('txn', 'teller'));
    }

    // ─── RECEIPT: LOAN REPAYMENT ─────────────────────────────────────────────

    public function receiptLoan($id)
    {
        $txn    = LoanTransaction::with('loan.client', 'loan.loan_product', 'payment_detail.payment_type')->findOrFail($id);
        $teller = SaccoTeller::where('user_id', Auth::id())->first();
        return theme_view('sacco::teller.receipt_loan', compact('txn', 'teller'));
    }

    // ─── DAILY TELLER LEDGER ─────────────────────────────────────────────────
    // Shows all transactions processed by this teller today (or selected date).

    public function ledger(Request $request)
    {
        $date   = $request->date ?? date('Y-m-d');
        $teller = SaccoTeller::where('user_id', Auth::id())->first();

        // Savings deposits made by this teller today
        $deposits = SavingsTransaction::with('savings.client', 'savings.savings_product')
            ->where('created_by_id', Auth::id())
            ->whereDate('created_on', $date)
            ->where('savings_transaction_type_id', 1) // deposit
            ->get();

        // Loan repayments received by this teller today
        $repayments = LoanTransaction::with('loan.client', 'loan.loan_product')
            ->where('created_by_id', Auth::id())
            ->whereDate('created_on', $date)
            ->where('loan_transaction_type_id', 2) // repayment
            ->get();

        // Withdrawals paid out
        $withdrawals = SavingsTransaction::with('savings.client', 'savings.savings_product')
            ->where('created_by_id', Auth::id())
            ->whereDate('created_on', $date)
            ->where('savings_transaction_type_id', 2) // withdrawal
            ->get();

        $totalDeposits    = $deposits->sum('amount');
        $totalRepayments  = $repayments->sum('amount');
        $totalWithdrawals = $withdrawals->sum('amount');
        $netCash          = $totalDeposits + $totalRepayments - $totalWithdrawals;

        $sessions = $teller
            ? SaccoTellerSession::where('sacco_teller_id', $teller->id)
                ->whereDate('opened_at', $date)->get()
            : collect();

        return theme_view('sacco::teller.ledger',
            compact('date','teller','deposits','repayments','withdrawals',
                    'totalDeposits','totalRepayments','totalWithdrawals','netCash','sessions'));
    }
}

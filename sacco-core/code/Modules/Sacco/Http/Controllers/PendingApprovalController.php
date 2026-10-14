<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Accounting\Entities\JournalEntry;
use Modules\ActivityLog\Services\AuditLogger;
use Modules\Client\Entities\Client;
use Modules\Expense\Entities\Expense;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanHistory;
use Modules\Loan\Entities\LoanTransaction;
use Modules\Loan\Events\LoanStatusChanged;
use Modules\Loan\Events\TransactionUpdated as LoanTransactionUpdated;
use Modules\Payroll\Entities\Payroll;
use Modules\Payroll\Entities\PayrollRun;
use Modules\Payroll\Services\PayrollCalculationService;
use Modules\Sacco\Entities\SaccoPendingApproval;
use Modules\Sacco\Entities\SaccoProvisionRun;
use Modules\Sacco\Services\ComplianceAuditService;
use Modules\Sacco\Services\MakerCheckerService;
use Modules\Sacco\Services\ProvisionPostingService;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\SavingsStatusChanged;
use Modules\Savings\Events\TransactionUpdated as SavingsTransactionUpdated;
use Modules\Share\Entities\Share;
use Modules\Share\Entities\ShareTransaction;
use Modules\Share\Events\ShareStatusChanged;
use Modules\Share\Events\TransactionUpdated as ShareTransactionUpdated;

class PendingApprovalController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:sacco.checker.approve|sacco.teller.manage']);
    }

    public function index()
    {
        $pending = SaccoPendingApproval::with('creator')
            ->where('status', 'pending')
            ->orderByDesc('id')
            ->paginate(20);
        return theme_view('sacco::approval.index', compact('pending'));
    }

    public function approve($id, MakerCheckerService $makerChecker)
    {
        $approval = SaccoPendingApproval::findOrFail($id);

        try {
            $makerChecker->approve($approval);
        } catch (\RuntimeException $e) {
            \flash($e->getMessage())->error()->important();
            return redirect()->back();
        }

        $payload = $makerChecker->getPayload($approval);

        match ($approval->approvable_type) {
            // ── Loan ──────────────────────────────────────────────────
            'loan_approval'            => $this->finalizeLoanApproval($approval->approvable_id, $payload),
            'loan_disbursement'        => $this->finalizeLoanDisbursementGate($approval->approvable_id),
            'loan_writeoff'            => $this->finalizeLoanWriteOffGate($approval->approvable_id),
            'loan_repayment'           => $this->finalizeLoanRepayment($approval->approvable_id),
            // ── Savings ───────────────────────────────────────────────
            'savings_transaction'      => $this->finalizeSavingsTransaction($approval->approvable_id),
            'savings_approval'         => $this->finalizeSavingsApproval($approval->approvable_id, $payload),
            'savings_account_closure'  => $this->finalizeSavingsClosure($approval->approvable_id, $payload),
            // ── Share ─────────────────────────────────────────────────
            'share_approval'           => $this->finalizeShareApproval($approval->approvable_id, $payload),
            'share_deposit'            => $this->finalizeShareTransaction($approval->approvable_id, true),
            'share_withdrawal'         => $this->finalizeShareTransaction($approval->approvable_id, false),
            // ── Other ─────────────────────────────────────────────────
            'payroll_approval'         => $this->finalizePayrollApproval($approval->approvable_id),
            'expense'                  => $this->finalizeExpense($approval->approvable_id, $payload),
            'client_activation'        => $this->finalizeClientActivation($approval->approvable_id, $payload),
            'provision_run'            => $this->finalizeProvisionRun($approval->approvable_id),
            default                    => null,
        };

        (new ComplianceAuditService())->log('approve', 'maker_checker', $approval->approvable_type, $approval->approvable_id, 'Approved pending transaction');
        AuditLogger::log(
            'Approve Transaction',
            $approval,
            ['id' => $approval->id, 'approvable_type' => $approval->approvable_type, 'approvable_id' => $approval->approvable_id],
            'sacco',
            'approve'
        );

        \flash('Transaction approved and processed.')->success()->important();
        return redirect('sacco/approval');
    }

    public function decline(Request $request, $id, MakerCheckerService $makerChecker)
    {
        $approval = SaccoPendingApproval::findOrFail($id);
        $makerChecker->decline($approval, $request->notes);

        match ($approval->approvable_type) {
            'loan_repayment'      => $this->declineLoanRepayment($approval->approvable_id),
            'savings_transaction' => $this->declineSavingsTransaction($approval->approvable_id),
            'share_deposit',
            'share_withdrawal'    => $this->declineShareTransaction($approval->approvable_id),
            'expense'             => $this->declineExpense($approval->approvable_id),
            default               => null,
        };

        (new ComplianceAuditService())->log('decline', 'maker_checker', $approval->approvable_type, $approval->approvable_id, 'Declined: ' . $request->notes);
        AuditLogger::log(
            'Reject Transaction',
            $approval,
            ['id' => $approval->id, 'approvable_type' => $approval->approvable_type, 'approvable_id' => $approval->approvable_id, 'notes' => $request->notes],
            'sacco',
            'reject'
        );

        \flash('Transaction declined.')->warning()->important();
        return redirect('sacco/approval');
    }

    // ─── Loan ─────────────────────────────────────────────────────────────

    /**
     * Loan Approval: fully execute the status change using the stored payload.
     */
    protected function finalizeLoanApproval(int $loanId, array $payload): void
    {
        $loan = Loan::find($loanId);
        if (!$loan || $loan->status === 'approved') {
            return;
        }
        $previous = $loan->status;
        $loan->approved_by_user_id = Auth::id();
        $loan->approved_amount     = $payload['approved_amount'] ?? $loan->applied_amount;
        $loan->approved_on_date    = $payload['approved_on_date'] ?? date('Y-m-d');
        $loan->status              = 'approved';
        $loan->approved_notes      = $payload['approved_notes'] ?? null;
        $loan->save();

        $history = new LoanHistory();
        $history->loan_id        = $loan->id;
        $history->created_by_id  = Auth::id();
        $history->user           = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $history->action         = 'Loan Approved (Checker)';
        $history->save();

        activity()->on($loan)->withProperties(['id' => $loan->id])->log('Approve Loan (Checker)');
        if (class_exists(LoanStatusChanged::class)) {
            event(new LoanStatusChanged($loan, $previous));
        }
    }

    /**
     * Loan Disbursement: set the checker-approved gate flag so the maker can
     * execute the actual disbursement without triggering the maker-checker check again.
     */
    protected function finalizeLoanDisbursementGate(int $loanId): void
    {
        $loan = Loan::find($loanId);
        if (!$loan) {
            return;
        }
        $loan->checker_approved_disbursement = 1;
        $loan->save();

        $history = new LoanHistory();
        $history->loan_id        = $loan->id;
        $history->created_by_id  = Auth::id();
        $history->user           = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $history->action         = 'Disbursement Checker-Approved — maker may now execute';
        $history->save();

        activity()->on($loan)->withProperties(['id' => $loan->id])->log('Checker-Approve Loan Disbursement');
    }

    /**
     * Loan Write-Off: set the checker-approved gate flag.
     */
    protected function finalizeLoanWriteOffGate(int $loanId): void
    {
        $loan = Loan::find($loanId);
        if (!$loan) {
            return;
        }
        $loan->checker_approved_writeoff = 1;
        $loan->save();

        $history = new LoanHistory();
        $history->loan_id        = $loan->id;
        $history->created_by_id  = Auth::id();
        $history->user           = Auth::user()->first_name . ' ' . Auth::user()->last_name;
        $history->action         = 'Write-Off Checker-Approved — maker may now execute';
        $history->save();

        activity()->on($loan)->withProperties(['id' => $loan->id])->log('Checker-Approve Loan Write-Off');
    }

    /**
     * Loan Repayment: approve the pending transaction and fire the balance recalculation event.
     */
    protected function finalizeLoanRepayment(int $txnId): void
    {
        $txn = LoanTransaction::find($txnId);
        if (!$txn || $txn->status === 'approved') {
            return;
        }
        $txn->status = 'approved';
        $txn->save();

        $loan = Loan::find($txn->loan_id);
        if ($loan) {
            activity()->on($txn)->withProperties(['id' => $txn->id])->log('Approve Loan Repayment (Checker)');
            if (class_exists(LoanTransactionUpdated::class)) {
                event(new LoanTransactionUpdated($loan));
            }
        }
    }

    protected function declineLoanRepayment(int $txnId): void
    {
        $txn = LoanTransaction::find($txnId);
        if ($txn) {
            $txn->status = 'declined';
            $txn->save();
        }
    }

    // ─── Savings ──────────────────────────────────────────────────────────

    protected function finalizeSavingsTransaction(int $txnId): void
    {
        $txn = SavingsTransaction::find($txnId);
        if (!$txn || $txn->status === 'approved') {
            return;
        }
        $savings = Savings::with('savings_product')->find($txn->savings_id);
        $txn->status = 'approved';
        $txn->approved_by_id = Auth::id();
        $txn->save();

        if ($savings->savings_product->accounting_rule === 'cash') {
            $date = $txn->submitted_on;
            $dateParts = explode('-', $date);
            $prefix = 'S' . $txn->id;
            $isDeposit = $txn->credit > 0;
            if ($isDeposit) {
                $this->postJe($prefix, $savings->branch_id, $savings->currency_id, $savings->id,
                    $savings->savings_product->savings_control_chart_of_account_id, 0, $txn->amount, 'savings_deposit', $date, $dateParts);
                $this->postJe($prefix, $savings->branch_id, $savings->currency_id, $savings->id,
                    $savings->savings_product->savings_reference_chart_of_account_id, $txn->amount, 0, 'savings_deposit', $date, $dateParts);
            } else {
                $this->postJe($prefix, $savings->branch_id, $savings->currency_id, $savings->id,
                    $savings->savings_product->savings_reference_chart_of_account_id, 0, $txn->amount, 'savings_withdrawal', $date, $dateParts);
                $this->postJe($prefix, $savings->branch_id, $savings->currency_id, $savings->id,
                    $savings->savings_product->savings_control_chart_of_account_id, $txn->amount, 0, 'savings_withdrawal', $date, $dateParts);
            }
        }
        event(new SavingsTransactionUpdated($savings));
    }

    protected function declineSavingsTransaction(int $txnId): void
    {
        $txn = SavingsTransaction::find($txnId);
        if ($txn) {
            $txn->status = 'declined';
            $txn->save();
        }
    }

    protected function finalizeSavingsApproval(int $savingsId, array $payload): void
    {
        $savings = Savings::find($savingsId);
        if (!$savings) {
            return;
        }
        $previous = $savings->status;
        $savings->approved_by_user_id = Auth::id();
        $savings->approved_on_date = $payload['approved_on_date'] ?? date('Y-m-d');
        $savings->status = 'approved';
        $savings->approved_notes = $payload['approved_notes'] ?? null;
        $savings->save();
        activity()->on($savings)->withProperties(['id' => $savings->id])->log('Approve Savings (Checker)');
        if (class_exists(SavingsStatusChanged::class)) {
            event(new SavingsStatusChanged($savings, $previous));
        }
    }

    protected function finalizeSavingsClosure(int $savingsId, array $payload): void
    {
        $savings = Savings::find($savingsId);
        if (!$savings) {
            return;
        }
        $previous = $savings->status;
        $savings->withdrawn_by_user_id = Auth::id();
        $savings->withdrawn_on_date = date('Y-m-d');
        $savings->status = 'withdrawn';
        $savings->withdrawn_notes = $payload['withdrawn_notes'] ?? null;
        $savings->save();
        activity()->on($savings)->withProperties(['id' => $savings->id])->log('Withdraw Savings (Checker)');
        if (class_exists(SavingsStatusChanged::class)) {
            event(new SavingsStatusChanged($savings, $previous));
        }
    }

    // ─── Share ────────────────────────────────────────────────────────────

    protected function finalizeShareApproval(int $shareId, array $payload): void
    {
        $share = Share::find($shareId);
        if (!$share) {
            return;
        }
        $previous = $share->status;
        $share->approved_by_user_id = Auth::id();
        $share->approved_on_date = $payload['approved_on_date'] ?? date('Y-m-d');
        $share->status = 'approved';
        $share->approved_notes = $payload['approved_notes'] ?? null;
        $share->save();
        activity()->on($share)->withProperties(['id' => $share->id])->log('Approve Share (Checker)');
        if (class_exists(ShareStatusChanged::class)) {
            event(new ShareStatusChanged($share, $previous));
        }
    }

    protected function finalizeShareTransaction(int $txnId, bool $isDeposit): void
    {
        $txn = ShareTransaction::find($txnId);
        if (!$txn || $txn->status === 'approved') {
            return;
        }
        $share = Share::with('share_product')->find($txn->share_id);
        $txn->status = 'approved';
        $txn->save();

        if (($share->share_product->accounting_rule ?? '') === 'cash') {
            $date = $txn->submitted_on;
            $dateParts = explode('-', $date);
            $prefix = 'SH' . $txn->id;
            if ($isDeposit) {
                $this->postJe($prefix, $share->branch_id, $share->currency_id, $share->id,
                    $share->share_product->share_control_chart_of_account_id ?? null, 0, $txn->amount, 'share_deposit', $date, $dateParts);
                $this->postJe($prefix, $share->branch_id, $share->currency_id, $share->id,
                    $share->share_product->share_reference_chart_of_account_id ?? null, $txn->amount, 0, 'share_deposit', $date, $dateParts);
            } else {
                $this->postJe($prefix, $share->branch_id, $share->currency_id, $share->id,
                    $share->share_product->share_reference_chart_of_account_id ?? null, 0, $txn->amount, 'share_withdrawal', $date, $dateParts);
                $this->postJe($prefix, $share->branch_id, $share->currency_id, $share->id,
                    $share->share_product->share_control_chart_of_account_id ?? null, $txn->amount, 0, 'share_withdrawal', $date, $dateParts);
            }
        }
        event(new ShareTransactionUpdated($share));
    }

    protected function declineShareTransaction(int $txnId): void
    {
        $txn = ShareTransaction::find($txnId);
        if ($txn) {
            $txn->status = 'declined';
            $txn->save();
        }
    }

    // ─── Payroll ──────────────────────────────────────────────────────────

    protected function finalizePayrollApproval(int $runId): void
    {
        $run = PayrollRun::find($runId);
        if (!$run || $run->status === 'approved') {
            return;
        }
        $run->status = 'approved';
        $run->approved_by_id = Auth::id();
        $run->approved_at = now();
        $run->save();
        Payroll::where('payroll_run_id', $run->id)->update(['status' => 'approved']);
        if (class_exists(PayrollCalculationService::class)) {
            (new PayrollCalculationService())->recalculateRunTotals($run);
        }
        activity()->log('Approve Payroll Run (Checker) #' . $run->id);
    }

    // ─── Expense ──────────────────────────────────────────────────────────

    protected function finalizeExpense(int $expenseId, array $payload): void
    {
        $expense = Expense::find($expenseId);
        if (!$expense) {
            return;
        }
        $expense->status = 'active';
        $expense->save();

        $date = $payload['date'] ?? date('Y-m-d');
        $dateParts = explode('-', $date);
        $pdId = $payload['payment_detail_id'] ?? null;

        if (!empty($payload['expense_chart_of_account_id'])) {
            $je = new JournalEntry();
            $je->created_by_id = Auth::id();
            $je->payment_detail_id = $pdId;
            $je->transaction_number = $expense->id;
            $je->branch_id = $payload['branch_id'] ?? null;
            $je->currency_id = $payload['currency_id'] ?? null;
            $je->chart_of_account_id = $payload['expense_chart_of_account_id'];
            $je->transaction_type = 'expense';
            $je->date = $date;
            $je->month = $dateParts[1] ?? date('m');
            $je->year = $dateParts[0] ?? date('Y');
            $je->debit = $expense->amount;
            $je->reference = $expense->id;
            $je->notes = $payload['notes'] ?? null;
            $je->save();
        }
        if (!empty($payload['asset_chart_of_account_id'])) {
            $je = new JournalEntry();
            $je->created_by_id = Auth::id();
            $je->payment_detail_id = $pdId;
            $je->transaction_number = $expense->id;
            $je->branch_id = $payload['branch_id'] ?? null;
            $je->currency_id = $payload['currency_id'] ?? null;
            $je->chart_of_account_id = $payload['asset_chart_of_account_id'];
            $je->transaction_type = 'expense';
            $je->date = $date;
            $je->month = $dateParts[1] ?? date('m');
            $je->year = $dateParts[0] ?? date('Y');
            $je->credit = $expense->amount;
            $je->reference = $expense->id;
            $je->notes = $payload['notes'] ?? null;
            $je->save();
        }
        activity()->on($expense)->withProperties(['id' => $expense->id])->log('Approve Expense (Checker)');
    }

    protected function declineExpense(int $expenseId): void
    {
        $expense = Expense::find($expenseId);
        if ($expense) {
            $expense->status = 'declined';
            $expense->save();
        }
    }

    // ─── Client ───────────────────────────────────────────────────────────

    protected function finalizeClientActivation(int $clientId, array $payload): void
    {
        $client = Client::find($clientId);
        if (!$client) {
            return;
        }
        $client->status = $payload['status'] ?? 'active';
        if (!empty($payload['member_status_detail'])) {
            $client->member_status_detail = $payload['member_status_detail'];
        }
        if (isset($payload['kyc_verified'])) {
            $client->kyc_verified = (int)$payload['kyc_verified'];
        }
        $client->save();
        activity()->on($client)->withProperties(['id' => $client->id])->log('Activate Client (Checker)');
    }

    // ─── Provision Run ────────────────────────────────────────────────────

    protected function finalizeProvisionRun(int $runId): void
    {
        $run = SaccoProvisionRun::find($runId);
        if ($run) {
            $run->approved_by_id = Auth::id();
            $run->status = 'approved';
            $run->save();
            (new ProvisionPostingService())->postProvision($run);
        }
    }

    // ─── JE helper ────────────────────────────────────────────────────────

    protected function postJe(
        string $prefix,
        ?int $branchId,
        ?int $currencyId,
        int $reference,
        ?int $coaId,
        float $debit,
        float $credit,
        string $type,
        string $date,
        array $dateParts
    ): void {
        if (!$coaId) {
            return;
        }
        $entry = new JournalEntry();
        $entry->created_by_id = Auth::id();
        $entry->transaction_number = $prefix;
        $entry->branch_id = $branchId;
        $entry->currency_id = $currencyId;
        $entry->chart_of_account_id = $coaId;
        $entry->transaction_type = $type;
        $entry->date = $date;
        $entry->month = $dateParts[1] ?? date('m');
        $entry->year = $dateParts[0] ?? date('Y');
        $entry->debit = $debit;
        $entry->credit = $credit;
        $entry->reference = $reference;
        $entry->save();
    }
}

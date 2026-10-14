<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Modules\Sacco\Entities\SaccoMpesaReconciliation;
use Modules\Sacco\Entities\SaccoMpesaReconciliationItem;
use Modules\Sacco\Entities\SaccoMpesaReconciliationAuditLog;
use Modules\Sacco\Entities\SaccoMpesaFloatAccount;
use Modules\Sacco\Entities\SaccoMpesaFloatEntry;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\Sacco\Entities\SaccoMpesaB2cTransaction;

class MpesaReconciliationService
{
    // ─── Session Management ───────────────────────────────────────────────────

    /**
     * Create a new reconciliation session.
     */
    public function createSession(array $data, int $userId): SaccoMpesaReconciliation
    {
        $recon = SaccoMpesaReconciliation::create(array_merge($data, [
            'created_by_id' => $userId,
            'reference'     => 'RECON-' . strtoupper(Str::random(8)),
            'status'        => 'draft',
        ]));

        $this->audit($recon, 'session_created', 'Reconciliation session created', [], $userId, [
            'status_after' => 'draft',
        ]);

        return $recon;
    }

    /**
     * Import CSV rows into reconciliation items.
     * Expects $rows as array-of-arrays from a parsed CSV.
     * Safaricom CSV columns (standard):
     *   Receipt No, Completion Time, Details, Transaction Status,
     *   Paid In, Withdrawn, Amount, Other Party Info, Transaction Type,
     *   Other Party, Account No, Running Balance
     */
    public function importRows(SaccoMpesaReconciliation $recon, array $rows, int $userId): array
    {
        $this->changeStatus($recon, 'importing', $userId);

        $imported  = 0;
        $skipped   = 0;
        $duplicate = 0;

        foreach ($rows as $row) {
            // Normalise column names (handle variations)
            $row = array_change_key_case($row, CASE_LOWER);

            $receipt = trim($row['receipt no'] ?? $row['receipt_no'] ?? $row['transaction id'] ?? '');
            if (empty($receipt)) { $skipped++; continue; }

            // Check for duplicate receipt in this session
            $existsInSession = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)
                ->where('receipt_number', $receipt)->exists();
            if ($existsInSession) { $duplicate++; continue; }

            $paidIn    = (float) str_replace(',', '', $row['paid in'] ?? $row['paid_in'] ?? $row['amount'] ?? 0);
            $withdrawn = (float) str_replace(',', '', $row['withdrawn'] ?? $row['withdrawn'] ?? 0);
            $amount    = $paidIn > 0 ? $paidIn : $withdrawn;
            $direction = $paidIn > 0 ? 'in' : 'out';

            $completionTime = null;
            $rawTime = $row['completion time'] ?? $row['completion_time'] ?? null;
            if ($rawTime) {
                try { $completionTime = Carbon::parse($rawTime); } catch (\Exception $e) {}
            }

            // Check if receipt already exists in system C2B table (detect duplicates in our DB)
            $existsC2b = SaccoMpesaC2bTransaction::where('trans_id', $receipt)->exists();

            SaccoMpesaReconciliationItem::create([
                'sacco_mpesa_reconciliation_id' => $recon->id,
                'receipt_number'      => $receipt,
                'transaction_id'      => $row['transaction id'] ?? $row['transaction_id'] ?? $receipt,
                'completion_time'     => $completionTime,
                'details'             => $row['details'] ?? null,
                'transaction_status'  => $row['transaction status'] ?? $row['transaction_status'] ?? 'Completed',
                'paid_in_number'      => $row['other party'] ?? $row['other_party'] ?? $row['paid_in_number'] ?? null,
                'account_reference'   => $row['account no'] ?? $row['account_no'] ?? $row['bill ref'] ?? null,
                'amount'              => $amount,
                'transaction_cost'    => (float) str_replace(',', '', $row['transaction cost'] ?? $row['transaction_cost'] ?? 0),
                'running_balance'     => strlen((string)($row['running balance'] ?? '')) > 0
                    ? (float) str_replace(',', '', $row['running balance']) : null,
                'direction'           => $direction,
                'match_status'        => 'unmatched',
                'is_duplicate'        => ($duplicate > 0 && !$existsC2b) ? 0 : 0,
            ]);

            $imported++;
        }

        // Recompute statement totals
        $this->refreshStatementTotals($recon);
        $this->changeStatus($recon, 'pending_match', $userId);

        $this->audit($recon, 'csv_imported',
            "Imported {$imported} rows. Skipped: {$skipped}. Duplicates: {$duplicate}.",
            ['imported' => $imported, 'skipped' => $skipped, 'duplicate' => $duplicate],
            $userId
        );

        return ['imported' => $imported, 'skipped' => $skipped, 'duplicate' => $duplicate];
    }

    /**
     * Run automatic matching against internal C2B and B2C records.
     * Matching strategy (priority order):
     *   1. Exact receipt number match on trans_id
     *   2. Fuzzy: same amount + same phone + same date (±1 day)
     */
    public function autoMatch(SaccoMpesaReconciliation $recon, int $userId): array
    {
        $matched   = 0;
        $variance  = 0;
        $unmatched = 0;

        $items = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)
            ->where('match_status', 'unmatched')
            ->get();

        foreach ($items as $item) {
            $result = $this->matchSingleItem($item);

            if ($result['matched']) {
                $item->match_status        = $result['has_variance'] ? 'matched_with_variance' : 'matched';
                $item->variance_amount     = $result['variance'];
                $item->c2b_transaction_id  = $result['c2b_id'] ?? null;
                $item->b2c_transaction_id  = $result['b2c_id'] ?? null;
                $item->matched_by_id       = null; // system
                $item->matched_at          = Carbon::now();
                $item->match_notes         = $result['notes'];
                $item->save();

                // Mark internal transaction as reconciled
                if ($result['c2b_id']) {
                    SaccoMpesaC2bTransaction::where('id', $result['c2b_id'])->update([
                        'reconciliation_item_id' => $item->id,
                        'reconciled_at'          => Carbon::now(),
                        'is_reconciled'          => 1,
                    ]);
                }
                if ($result['b2c_id']) {
                    SaccoMpesaB2cTransaction::where('id', $result['b2c_id'])->update([
                        'reconciliation_item_id' => $item->id,
                        'reconciled_at'          => Carbon::now(),
                        'is_reconciled'          => 1,
                    ]);
                }

                $matched++;
                if ($result['has_variance']) $variance++;
            } else {
                $unmatched++;
            }
        }

        $this->refreshSummary($recon);
        $this->changeStatus($recon, 'matched', $userId);

        $this->audit($recon, 'auto_matched',
            "Auto-match complete. Matched: {$matched}, With variance: {$variance}, Unmatched: {$unmatched}.",
            ['matched' => $matched, 'variance' => $variance, 'unmatched' => $unmatched],
            $userId
        );

        return ['matched' => $matched, 'variance' => $variance, 'unmatched' => $unmatched];
    }

    /**
     * Manually match a statement item to an internal transaction.
     */
    public function manualMatch(
        SaccoMpesaReconciliationItem $item,
        string $transactionType, // 'c2b' | 'b2c'
        int $transactionId,
        int $userId,
        string $notes = ''
    ): void {
        $previousStatus = $item->match_status;

        if ($transactionType === 'c2b') {
            $txn = SaccoMpesaC2bTransaction::findOrFail($transactionId);
            $variance = $item->amount - $txn->trans_amount;
            $item->c2b_transaction_id = $transactionId;
            $txn->reconciliation_item_id = $item->id;
            $txn->reconciled_at = Carbon::now();
            $txn->is_reconciled = 1;
            $txn->save();
        } else {
            $txn = SaccoMpesaB2cTransaction::findOrFail($transactionId);
            $variance = $item->amount - $txn->amount;
            $item->b2c_transaction_id = $transactionId;
            $txn->reconciliation_item_id = $item->id;
            $txn->reconciled_at = Carbon::now();
            $txn->is_reconciled = 1;
            $txn->save();
        }

        $item->match_status    = abs($variance) > 0.01 ? 'matched_with_variance' : 'manually_matched';
        $item->variance_amount = $variance;
        $item->matched_by_id   = $userId;
        $item->matched_at      = Carbon::now();
        $item->match_notes     = $notes;
        $item->save();

        $this->refreshSummary($item->reconciliation);

        $this->audit($item->reconciliation, 'item_matched',
            "Item #{$item->id} ({$item->receipt_number}) manually matched to {$transactionType} #{$transactionId}",
            ['item_id' => $item->id, 'transaction_type' => $transactionType, 'transaction_id' => $transactionId, 'variance' => $variance],
            $userId,
            ['item_id' => $item->id, 'status_before' => $previousStatus, 'status_after' => $item->match_status]
        );
    }

    /**
     * Un-match a previously matched item.
     */
    public function unmatch(SaccoMpesaReconciliationItem $item, int $userId, string $reason = ''): void
    {
        $previousStatus = $item->match_status;

        // De-link from internal transaction
        if ($item->c2b_transaction_id) {
            SaccoMpesaC2bTransaction::where('id', $item->c2b_transaction_id)->update([
                'reconciliation_item_id' => null,
                'reconciled_at'          => null,
                'is_reconciled'          => 0,
            ]);
        }
        if ($item->b2c_transaction_id) {
            SaccoMpesaB2cTransaction::where('id', $item->b2c_transaction_id)->update([
                'reconciliation_item_id' => null,
                'reconciled_at'          => null,
                'is_reconciled'          => 0,
            ]);
        }

        $item->match_status       = 'unmatched';
        $item->c2b_transaction_id = null;
        $item->b2c_transaction_id = null;
        $item->variance_amount    = null;
        $item->matched_by_id      = null;
        $item->matched_at         = null;
        $item->match_notes        = $reason;
        $item->save();

        $this->refreshSummary($item->reconciliation);

        $this->audit($item->reconciliation, 'item_unmatched',
            "Item #{$item->id} ({$item->receipt_number}) unmatched. Reason: {$reason}",
            ['item_id' => $item->id, 'reason' => $reason],
            $userId,
            ['item_id' => $item->id, 'status_before' => $previousStatus, 'status_after' => 'unmatched']
        );
    }

    /**
     * Flag an item as an exception with a reason.
     */
    public function flagException(
        SaccoMpesaReconciliationItem $item,
        string $reason,
        int $userId
    ): void {
        $item->is_exception     = 1;
        $item->exception_reason = $reason;
        $item->save();

        $this->audit($item->reconciliation, 'exception_flagged',
            "Item #{$item->id} flagged as exception: {$reason}",
            ['item_id' => $item->id, 'reason' => $reason],
            $userId
        );
    }

    /**
     * Resolve a flagged exception.
     */
    public function resolveException(
        SaccoMpesaReconciliationItem $item,
        string $resolution,
        int $userId
    ): void {
        $item->is_exception     = 0;
        $item->match_notes      = $resolution;
        $item->match_status     = 'excluded';
        $item->save();

        $this->refreshSummary($item->reconciliation);

        $this->audit($item->reconciliation, 'exception_resolved',
            "Exception on item #{$item->id} resolved: {$resolution}",
            ['item_id' => $item->id, 'resolution' => $resolution],
            $userId
        );
    }

    /**
     * Approve the reconciliation.
     */
    public function approve(SaccoMpesaReconciliation $recon, int $userId): void
    {
        $hasDiscrepancy = $recon->discrepancy_count > 0 || $recon->net_variance != 0;
        $status = $hasDiscrepancy ? 'discrepancy' : 'approved';

        $recon->status       = $status;
        $recon->approved_by_id = $userId;
        $recon->approved_at  = Carbon::now();
        $recon->save();

        $this->audit($recon, 'approved',
            "Reconciliation approved. Net variance: {$recon->net_variance}. Status: {$status}",
            ['net_variance' => $recon->net_variance, 'unmatched' => $recon->unmatched_statement],
            $userId,
            ['status_before' => 'under_review', 'status_after' => $status]
        );
    }

    // ─── Float Account ────────────────────────────────────────────────────────

    /**
     * Add a float entry and update the account balance.
     */
    public function addFloatEntry(SaccoMpesaFloatAccount $account, array $data, int $userId): SaccoMpesaFloatEntry
    {
        $signedAmount = in_array($data['entry_type'], ['deduction'])
            ? -abs($data['amount'])
            : abs($data['amount']);

        $newBalance = (float) $account->current_balance + $signedAmount;

        $entry = SaccoMpesaFloatEntry::create(array_merge($data, [
            'sacco_mpesa_float_account_id' => $account->id,
            'created_by_id'                => $userId,
            'balance_after'                => $newBalance,
        ]));

        $account->current_balance = $newBalance;
        if ($data['entry_type'] === 'confirmed_balance') {
            $account->last_confirmed_balance = abs($data['amount']);
            $account->last_confirmed_date    = $data['entry_date'] ?? Carbon::today();
        }
        $account->save();

        return $entry;
    }

    // ─── Dashboard Stats ──────────────────────────────────────────────────────

    /**
     * Overall M-Pesa reconciliation dashboard stats.
     */
    public function getDashboardStats(): array
    {
        $today     = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'c2b_today'            => SaccoMpesaC2bTransaction::whereDate('created_at', $today)->count(),
            'c2b_today_amount'     => SaccoMpesaC2bTransaction::whereDate('created_at', $today)->where('status','posted')->sum('trans_amount'),
            'c2b_unreconciled'     => SaccoMpesaC2bTransaction::where('is_reconciled', 0)->where('status','posted')->count(),
            'b2c_today'            => SaccoMpesaB2cTransaction::whereDate('created_at', $today)->count(),
            'b2c_today_amount'     => SaccoMpesaB2cTransaction::whereDate('created_at', $today)->where('status','completed')->sum('amount'),
            'b2c_unreconciled'     => SaccoMpesaB2cTransaction::where('is_reconciled', 0)->where('status','completed')->count(),
            'open_sessions'        => SaccoMpesaReconciliation::whereNotIn('status',['approved','archived'])->count(),
            'sessions_with_variance' => SaccoMpesaReconciliation::where('status','discrepancy')->count(),
            'c2b_failed_today'     => SaccoMpesaC2bTransaction::whereDate('created_at', $today)->where('status','failed')->count(),
            'b2c_failed_today'     => SaccoMpesaB2cTransaction::whereDate('created_at', $today)->where('status','failed')->count(),
            'c2b_month_amount'     => SaccoMpesaC2bTransaction::where('created_at','>=',$thisMonth)->where('status','posted')->sum('trans_amount'),
            'b2c_month_amount'     => SaccoMpesaB2cTransaction::where('created_at','>=',$thisMonth)->where('status','completed')->sum('amount'),
            'float_accounts'       => SaccoMpesaFloatAccount::where('is_active', 1)->get(),
        ];
    }

    /**
     * Get unreconciled C2B transactions for a date range.
     */
    public function getUnreconciledC2b(string $from, string $to): \Illuminate\Database\Eloquent\Collection
    {
        return SaccoMpesaC2bTransaction::where('is_reconciled', 0)
            ->where('status', 'posted')
            ->whereBetween('created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->orderByDesc('created_at')
            ->get();
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    private function matchSingleItem(SaccoMpesaReconciliationItem $item): array
    {
        $noMatch = ['matched' => false, 'has_variance' => false, 'variance' => 0,
                    'c2b_id' => null, 'b2c_id' => null, 'notes' => ''];

        if ($item->direction === 'in') {
            // Strategy 1: exact receipt number
            $c2b = SaccoMpesaC2bTransaction::where('trans_id', $item->receipt_number)
                ->where('is_reconciled', 0)->first();

            if ($c2b) {
                $variance = (float) $item->amount - (float) $c2b->trans_amount;
                return ['matched' => true, 'has_variance' => abs($variance) > 0.01,
                        'variance' => $variance, 'c2b_id' => $c2b->id, 'b2c_id' => null,
                        'notes' => 'Exact receipt match'];
            }

            // Strategy 2: fuzzy — same amount + same phone + within 1 day
            if ($item->completion_time && $item->paid_in_number) {
                $c2b = SaccoMpesaC2bTransaction::where('trans_amount', $item->amount)
                    ->where('msisdn', $item->paid_in_number)
                    ->where('is_reconciled', 0)
                    ->whereBetween('created_at', [
                        $item->completion_time->copy()->subDay(),
                        $item->completion_time->copy()->addDay(),
                    ])->first();

                if ($c2b) {
                    return ['matched' => true, 'has_variance' => false,
                            'variance' => 0, 'c2b_id' => $c2b->id, 'b2c_id' => null,
                            'notes' => 'Fuzzy match: amount + phone + date'];
                }
            }
        } else {
            // Outbound: match against B2C
            $b2c = SaccoMpesaB2cTransaction::where('mpesa_receipt', $item->receipt_number)
                ->where('is_reconciled', 0)->first();

            if ($b2c) {
                $variance = (float) $item->amount - (float) $b2c->amount;
                return ['matched' => true, 'has_variance' => abs($variance) > 0.01,
                        'variance' => $variance, 'c2b_id' => null, 'b2c_id' => $b2c->id,
                        'notes' => 'Exact receipt match (B2C)'];
            }
        }

        return $noMatch;
    }

    private function refreshStatementTotals(SaccoMpesaReconciliation $recon): void
    {
        $recon->statement_total_in  = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)->where('direction','in')->sum('amount');
        $recon->statement_total_out = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)->where('direction','out')->sum('amount');
        $recon->statement_count_in  = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)->where('direction','in')->count();
        $recon->statement_count_out = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)->where('direction','out')->count();
        $recon->save();
    }

    private function refreshSummary(SaccoMpesaReconciliation $recon): void
    {
        $matched   = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)
            ->whereIn('match_status', ['matched','matched_with_variance','manually_matched'])->count();
        $unmatched = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)
            ->where('match_status', 'unmatched')->count();
        $variance  = SaccoMpesaReconciliationItem::where('sacco_mpesa_reconciliation_id', $recon->id)
            ->where('match_status', 'matched_with_variance')->count();

        // System totals from C2B/B2C
        $systemInAmount  = SaccoMpesaC2bTransaction::where('reconciliation_item_id', '!=', null)
            ->whereHas('reconciliation_item', fn($q) => $q->where('sacco_mpesa_reconciliation_id', $recon->id))
            ->sum('trans_amount');
        $systemOutAmount = SaccoMpesaB2cTransaction::where('reconciliation_item_id', '!=', null)
            ->whereHas('reconciliation_item', fn($q) => $q->where('sacco_mpesa_reconciliation_id', $recon->id))
            ->sum('amount');

        $recon->matched_count        = $matched;
        $recon->unmatched_statement  = $unmatched;
        $recon->discrepancy_count    = $variance;
        $recon->system_total_in      = $systemInAmount;
        $recon->system_total_out     = $systemOutAmount;
        $recon->net_variance         = ($recon->statement_total_in - $systemInAmount)
                                      - ($recon->statement_total_out - $systemOutAmount);
        $recon->save();
    }

    private function changeStatus(SaccoMpesaReconciliation $recon, string $newStatus, int $userId): void
    {
        $old = $recon->status;
        $recon->status = $newStatus;
        $recon->save();

        if ($old !== $newStatus) {
            $this->audit($recon, 'status_changed',
                "Status changed from {$old} to {$newStatus}",
                [], $userId,
                ['status_before' => $old, 'status_after' => $newStatus]
            );
        }
    }

    /**
     * Write an immutable audit log entry.
     */
    public function audit(
        SaccoMpesaReconciliation $recon,
        string $action,
        string $description,
        array $meta = [],
        ?int $userId = null,
        array $extra = []
    ): SaccoMpesaReconciliationAuditLog
    {
        return SaccoMpesaReconciliationAuditLog::create([
            'sacco_mpesa_reconciliation_id' => $recon->id,
            'user_id'       => $userId,
            'item_id'       => $extra['item_id'] ?? null,
            'action'        => $action,
            'description'   => $description,
            'meta'          => $meta ?: null,
            'ip_address'    => request()->ip(),
            'user_agent'    => request()->userAgent(),
            'status_before' => $extra['status_before'] ?? null,
            'status_after'  => $extra['status_after']  ?? null,
            'amount_before' => $extra['amount_before'] ?? null,
            'amount_after'  => $extra['amount_after']  ?? null,
        ]);
    }
}

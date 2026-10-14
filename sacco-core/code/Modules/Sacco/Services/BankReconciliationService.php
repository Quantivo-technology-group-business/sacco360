<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Sacco\Entities\SaccoBankReconciliation;
use Modules\Sacco\Entities\SaccoBankReconciliationItem;

class BankReconciliationService
{
    public function create(array $data): SaccoBankReconciliation
    {
        $bookBalance = $this->getBookBalance(
            (int)$data['chart_of_account_id'],
            $data['statement_date'],
            $data['branch_id'] ?? null
        );

        $reconciliation = new SaccoBankReconciliation();
        $reconciliation->created_by_id = Auth::id();
        $reconciliation->chart_of_account_id = $data['chart_of_account_id'];
        $reconciliation->branch_id = $data['branch_id'] ?? null;
        $reconciliation->statement_date = $data['statement_date'];
        $reconciliation->statement_balance = $data['statement_balance'];
        $reconciliation->book_balance = $bookBalance;
        $reconciliation->variance = $data['statement_balance'] - $bookBalance;
        $reconciliation->status = 'draft';
        $reconciliation->notes = $data['notes'] ?? null;
        $reconciliation->save();

        $this->importBookEntries($reconciliation);
        if (!empty($data['statement_items'])) {
            $this->importStatementItems($reconciliation, $data['statement_items']);
        }

        return $reconciliation;
    }

    public function getBookBalance(int $chartOfAccountId, string $asOfDate, ?int $branchId = null): float
    {
        $account = ChartOfAccount::findOrFail($chartOfAccountId);
        $query = DB::table('journal_entries')
            ->where('chart_of_account_id', $chartOfAccountId)
            ->where('date', '<=', $asOfDate);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $debit = (float)$query->sum('debit');
        $credit = (float)$query->sum('credit');

        return in_array($account->account_type, ['asset', 'expense'])
            ? $debit - $credit
            : $credit - $debit;
    }

    protected function importBookEntries(SaccoBankReconciliation $reconciliation): void
    {
        $query = DB::table('journal_entries')
            ->where('chart_of_account_id', $reconciliation->chart_of_account_id)
            ->where('date', '<=', $reconciliation->statement_date)
            ->orderBy('date');

        if ($reconciliation->branch_id) {
            $query->where('branch_id', $reconciliation->branch_id);
        }

        foreach ($query->get() as $entry) {
            SaccoBankReconciliationItem::create([
                'sacco_bank_reconciliation_id' => $reconciliation->id,
                'transaction_date' => $entry->date,
                'description' => $entry->transaction_type . ' #' . $entry->transaction_number,
                'reference' => $entry->transaction_number,
                'amount' => max($entry->debit, $entry->credit),
                'entry_type' => $entry->debit > 0 ? 'debit' : 'credit',
                'source' => 'book',
                'journal_entry_id' => $entry->id,
                'match_status' => 'unmatched',
            ]);
        }
    }

    protected function importStatementItems(SaccoBankReconciliation $reconciliation, array $items): void
    {
        foreach ($items as $item) {
            SaccoBankReconciliationItem::create([
                'sacco_bank_reconciliation_id' => $reconciliation->id,
                'transaction_date' => $item['date'],
                'description' => $item['description'] ?? null,
                'reference' => $item['reference'] ?? null,
                'amount' => $item['amount'],
                'entry_type' => $item['entry_type'],
                'source' => 'statement',
                'match_status' => 'unmatched',
            ]);
        }
    }

    public function autoMatch(SaccoBankReconciliation $reconciliation): array
    {
        $statementItems = SaccoBankReconciliationItem::where('sacco_bank_reconciliation_id', $reconciliation->id)
            ->where('source', 'statement')
            ->where('match_status', 'unmatched')
            ->get();

        $bookItems = SaccoBankReconciliationItem::where('sacco_bank_reconciliation_id', $reconciliation->id)
            ->where('source', 'book')
            ->where('match_status', 'unmatched')
            ->get();

        $matched = 0;
        foreach ($statementItems as $stmt) {
            foreach ($bookItems as $book) {
                if ($book->match_status !== 'unmatched') {
                    continue;
                }
                if (abs($stmt->amount - $book->amount) < 0.01
                    && $stmt->entry_type !== $book->entry_type
                    && abs(strtotime($stmt->transaction_date) - strtotime($book->transaction_date)) <= 86400 * 3
                ) {
                    $stmt->match_status = 'matched';
                    $stmt->journal_entry_id = $book->journal_entry_id;
                    $stmt->save();
                    $book->match_status = 'matched';
                    $book->save();
                    $matched++;
                    break;
                }
            }
        }

        $unmatchedStmt = SaccoBankReconciliationItem::where('sacco_bank_reconciliation_id', $reconciliation->id)
            ->where('source', 'statement')
            ->where('match_status', 'unmatched')
            ->count();

        $reconciliation->reconciled_balance = $reconciliation->statement_balance;
        $reconciliation->variance = $unmatchedStmt > 0 ? $reconciliation->statement_balance - $reconciliation->book_balance : 0;
        $reconciliation->status = $unmatchedStmt === 0 ? 'reconciled' : 'discrepancy';
        $reconciliation->save();

        return [
            'matched' => $matched,
            'unmatched_statement_items' => $unmatchedStmt,
            'status' => $reconciliation->status,
        ];
    }
}

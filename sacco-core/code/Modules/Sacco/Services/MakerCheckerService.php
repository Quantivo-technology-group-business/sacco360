<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Entities\SaccoPendingApproval;

class MakerCheckerService
{
    /**
     * These types always require a checker regardless of amount.
     */
    protected static array $alwaysRequireTypes = [
        // Loan
        'loan_approval', 'loan_disbursement', 'loan_writeoff',
        // Savings account lifecycle
        'savings_approval', 'savings_account_closure',
        // Share account lifecycle + transactions
        'share_approval', 'share_deposit', 'share_withdrawal',
        // Payroll
        'payroll_approval',
        // Accounting
        'journal_entry', 'dividend', 'provision_run', 'classification_override',
        // Client lifecycle
        'client_activation',
    ];

    public function requiresApproval(float $amount, ?string $type = null): bool
    {
        if ($type && in_array($type, self::$alwaysRequireTypes, true)) {
            return true;
        }

        return $amount >= config('sacco.maker_checker_threshold', 50000);
    }

    /**
     * @param array<string,mixed> $payload  Contextual data needed to finalize the action on approval.
     */
    public function submitForApproval(
        string $type,
        int $id,
        float $amount,
        ?string $notes = null,
        ?string $label = null,
        array $payload = []
    ): SaccoPendingApproval {
        $approval = new SaccoPendingApproval();
        $approval->created_by_id = Auth::id();
        $approval->approvable_type = $type;
        $approval->approvable_id = $id;
        $approval->amount = $amount;
        $approval->notes = $notes;
        $approval->label = $label ?? $this->defaultLabel($type);
        $approval->status = 'pending';
        if (!empty($payload)) {
            $approval->payload = json_encode($payload);
        }
        $approval->save();
        return $approval;
    }

    public function approve(SaccoPendingApproval $approval): bool
    {
        if ($approval->created_by_id === Auth::id()) {
            throw new \RuntimeException('Maker cannot approve own transaction');
        }
        if (!Auth::user()->can('sacco.checker.approve') && !Auth::user()->can('sacco.teller.manage')) {
            throw new \RuntimeException('User lacks checker approval permission');
        }
        $approval->approved_by_id = Auth::id();
        $approval->status = 'approved';
        $approval->save();
        return true;
    }

    public function decline(SaccoPendingApproval $approval, ?string $notes = null): void
    {
        $approval->approved_by_id = Auth::id();
        $approval->status = 'declined';
        $approval->notes = $notes ?? $approval->notes;
        $approval->save();
    }

    public function isCriticalType(string $type): bool
    {
        return in_array($type, self::$alwaysRequireTypes, true);
    }

    public function getPayload(SaccoPendingApproval $approval): array
    {
        return $approval->payload ? json_decode($approval->payload, true) : [];
    }

    private function defaultLabel(string $type): string
    {
        return match ($type) {
            'loan_approval'          => 'Loan Approval',
            'loan_disbursement'      => 'Loan Disbursement',
            'loan_writeoff'          => 'Loan Write-Off',
            'savings_approval'       => 'Savings Account Approval',
            'savings_account_closure'=> 'Savings Account Closure',
            'share_approval'         => 'Share Account Approval',
            'share_deposit'          => 'Share Deposit',
            'share_withdrawal'       => 'Share Withdrawal',
            'payroll_approval'       => 'Payroll Run Approval',
            'journal_entry'          => 'Manual Journal Entry',
            'dividend'               => 'Dividend Declaration',
            'provision_run'          => 'Provision Run',
            'classification_override'=> 'Classification Override',
            'client_activation'      => 'Client Activation',
            'savings_transaction'    => 'Savings Transaction',
            'expense'                => 'Expense (Large Amount)',
            default                  => ucwords(str_replace('_', ' ', $type)),
        };
    }
}

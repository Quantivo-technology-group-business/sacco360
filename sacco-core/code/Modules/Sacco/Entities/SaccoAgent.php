<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Modules\Accounting\Entities\ChartOfAccount;
use Modules\Branch\Entities\Branch;
use Modules\User\Entities\User;

class SaccoAgent extends Model
{
    protected $fillable = [
        'user_id', 'created_by_id', 'branch_id', 'float_chart_of_account_id', 'commission_chart_of_account_id',
        'agent_code', 'agent_number', 'agent_name', 'phone', 'pin',
        'pin_attempts', 'locked_until', 'email', 'id_number', 'location', 'address',
        'float_balance', 'float_limit', 'daily_transaction_limit', 'single_transaction_limit',
        'commission_rate', 'commission_balance', 'total_commissions_earned',
        'total_commissions_withdrawn', 'commission_tier', 'status', 'kyc_status',
        'kyc_verified_at', 'kyc_verified_by_id', 'kyc_expiry_date', 'kyc_notes',
        'suspended_reason', 'activated_at', 'last_transaction_at', 'notes',
    ];

    protected $casts = [
        'float_balance' => 'decimal:2',
        'float_limit' => 'decimal:2',
        'daily_transaction_limit' => 'decimal:2',
        'single_transaction_limit' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_balance' => 'decimal:2',
        'total_commissions_earned' => 'decimal:2',
        'total_commissions_withdrawn' => 'decimal:2',
        'activated_at' => 'datetime',
        'last_transaction_at' => 'datetime',
        'locked_until' => 'datetime',
        'kyc_verified_at' => 'datetime',
        'kyc_expiry_date' => 'date',
    ];

    protected $hidden = ['pin'];

    protected $appends = ['status_badge', 'float_utilization', 'kyc_status_badge'];

    // ─── Relationships ──────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function floatAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'float_chart_of_account_id');
    }

    public function commissionAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'commission_chart_of_account_id');
    }

    public function transactions()
    {
        return $this->hasMany(SaccoAgentTransaction::class, 'sacco_agent_id');
    }

    public function commissionLedger()
    {
        return $this->hasMany(SaccoAgentCommissionLedger::class, 'sacco_agent_id');
    }

    public function memberRegistrations()
    {
        return $this->hasMany(SaccoAgentMemberRegistration::class, 'sacco_agent_id');
    }

    public function utilityPayments()
    {
        return $this->hasMany(SaccoAgentUtilityPayment::class, 'sacco_agent_id');
    }

    public function kycDocuments()
    {
        return $this->hasMany(SaccoAgentKycDocument::class, 'sacco_agent_id');
    }

    public function kycVerifiedBy()
    {
        return $this->belongsTo(User::class, 'kyc_verified_by_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeLowFloat($query, $threshold = 1000)
    {
        return $query->where('float_balance', '<', $threshold);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('agent_code', 'like', "%{$term}%")
              ->orWhere('agent_name', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%");
        });
    }

    // ─── Computed Attributes ────────────────────────────────────────────────

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'active' => '<span class="badge badge-success">Active</span>',
            'suspended' => '<span class="badge badge-warning">Suspended</span>',
            'closed' => '<span class="badge badge-danger">Closed</span>',
            default => '<span class="badge badge-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    public function getFloatUtilizationAttribute(): float
    {
        if (!$this->float_limit || $this->float_limit <= 0) {
            return 0;
        }
        return round(($this->float_balance / $this->float_limit) * 100, 2);
    }

    public function getFormattedCommissionRateAttribute(): string
    {
        return number_format($this->commission_rate, 2) . '%';
    }

    public function getKycStatusBadgeAttribute(): string
    {
        return match ($this->kyc_status) {
            'verified' => '<span class="badge badge-success">KYC Verified</span>',
            'pending_review' => '<span class="badge badge-warning">Pending Review</span>',
            'rejected' => '<span class="badge badge-danger">KYC Rejected</span>',
            'expired' => '<span class="badge badge-secondary">KYC Expired</span>',
            default => '<span class="badge badge-light">Incomplete</span>',
        };
    }

    public function isKycVerified(): bool
    {
        return $this->kyc_status === 'verified' && !$this->isKycExpired();
    }

    public function isKycExpired(): bool
    {
        return $this->kyc_expiry_date && $this->kyc_expiry_date->isPast();
    }

    public function canOperate(): bool
    {
        return $this->isActive() && $this->isKycVerified();
    }

    // ─── Helper Methods ─────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canProcessTransaction(float $amount, string $type = 'withdrawal'): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        if ($type === 'withdrawal' && $this->float_balance < $amount) {
            return false;
        }

        return true;
    }

    public function getTotalCommissionsEarned(): float
    {
        return $this->transactions()
            ->whereIn('transaction_type', ['deposit', 'withdrawal'])
            ->sum('commission');
    }

    public function getTodayTransactionCount(): int
    {
        return $this->transactions()
            ->whereDate('created_at', today())
            ->count();
    }

    public function getTodayTransactionVolume(): float
    {
        return $this->transactions()
            ->whereDate('created_at', today())
            ->whereIn('transaction_type', ['deposit', 'withdrawal'])
            ->sum('amount');
    }

    public function getMonthlyStats(): array
    {
        $transactions = $this->transactions()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();

        return [
            'deposits' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
            'withdrawals' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
            'commissions' => $transactions->sum('commission'),
            'count' => $transactions->whereIn('transaction_type', ['deposit', 'withdrawal'])->count(),
        ];
    }

    // ─── PIN & Security Methods ──────────────────────────────────────────────

    public function setPin(string $pin): void
    {
        $this->pin = Hash::make($pin);
        $this->pin_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    public function verifyPin(string $pin): bool
    {
        if ($this->isLocked()) {
            return false;
        }

        if (Hash::check($pin, $this->pin)) {
            $this->pin_attempts = 0;
            $this->save();
            return true;
        }

        $this->pin_attempts++;
        if ($this->pin_attempts >= 3) {
            $this->locked_until = now()->addMinutes(30);
        }
        $this->save();

        return false;
    }

    public function isLocked(): bool
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    public function unlock(): void
    {
        $this->pin_attempts = 0;
        $this->locked_until = null;
        $this->save();
    }

    // ─── Float Management ────────────────────────────────────────────────────

    public function addFloat(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $newBalance = (float)$this->float_balance + $amount;
        if ($this->float_limit && $newBalance > $this->float_limit) {
            return false;
        }

        $this->float_balance = $newBalance;
        $this->save();
        return true;
    }

    public function deductFloat(float $amount): bool
    {
        if ($amount <= 0 || $this->float_balance < $amount) {
            return false;
        }

        $this->float_balance = (float)$this->float_balance - $amount;
        $this->save();
        return true;
    }

    public function hasEnoughFloat(float $amount): bool
    {
        return (float)$this->float_balance >= $amount;
    }

    // ─── Commission Management ───────────────────────────────────────────────

    public function addCommission(float $amount, ?int $transactionId = null, string $description = ''): void
    {
        $balanceBefore = (float)$this->commission_balance;
        $balanceAfter = $balanceBefore + $amount;

        SaccoAgentCommissionLedger::create([
            'sacco_agent_id' => $this->id,
            'transaction_id' => $transactionId,
            'type' => 'earned',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description ?: 'Commission earned',
        ]);

        $this->commission_balance = $balanceAfter;
        $this->total_commissions_earned = (float)$this->total_commissions_earned + $amount;
        $this->save();
    }

    public function withdrawCommission(float $amount, string $description = ''): bool
    {
        if ($amount <= 0 || (float)$this->commission_balance < $amount) {
            return false;
        }

        $balanceBefore = (float)$this->commission_balance;
        $balanceAfter = $balanceBefore - $amount;

        SaccoAgentCommissionLedger::create([
            'sacco_agent_id' => $this->id,
            'type' => 'withdrawn',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $description ?: 'Commission withdrawal',
        ]);

        $this->commission_balance = $balanceAfter;
        $this->total_commissions_withdrawn = (float)$this->total_commissions_withdrawn + $amount;
        $this->save();

        return true;
    }

    // ─── Daily Limits ────────────────────────────────────────────────────────

    public function getTodayTransactionTotal(): float
    {
        return $this->transactions()
            ->whereDate('created_at', today())
            ->whereIn('transaction_type', ['deposit', 'withdrawal', 'loan_repayment'])
            ->where('status', 'completed')
            ->sum('amount');
    }

    public function canProcessAmount(float $amount): array
    {
        $result = ['allowed' => true, 'violations' => []];

        if (!$this->isActive()) {
            $result['allowed'] = false;
            $result['violations'][] = 'Agent account is not active';
            return $result;
        }

        if ($this->isLocked()) {
            $result['allowed'] = false;
            $result['violations'][] = 'Agent account is temporarily locked';
            return $result;
        }

        if ($this->single_transaction_limit && $amount > $this->single_transaction_limit) {
            $result['allowed'] = false;
            $result['violations'][] = 'Amount exceeds single transaction limit of ' . number_format($this->single_transaction_limit, 2);
        }

        $todayTotal = $this->getTodayTransactionTotal();
        if ($this->daily_transaction_limit && ($todayTotal + $amount) > $this->daily_transaction_limit) {
            $result['allowed'] = false;
            $result['violations'][] = 'Transaction would exceed daily limit of ' . number_format($this->daily_transaction_limit, 2);
        }

        return $result;
    }

    // ─── Statistics ──────────────────────────────────────────────────────────

    public function getWeeklyStats(): array
    {
        $transactions = $this->transactions()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->where('status', 'completed')
            ->get();

        return [
            'deposits' => $transactions->where('transaction_type', 'deposit')->sum('amount'),
            'withdrawals' => $transactions->where('transaction_type', 'withdrawal')->sum('amount'),
            'loan_repayments' => $transactions->where('transaction_type', 'loan_repayment')->sum('amount'),
            'utilities' => $this->utilityPayments()->completed()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('amount'),
            'registrations' => $this->memberRegistrations()->approved()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'commissions' => $transactions->sum('commission'),
            'count' => $transactions->count(),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'today' => [
                'transactions' => $this->getTodayTransactionCount(),
                'volume' => $this->getTodayTransactionVolume(),
                'commissions' => $this->commissionLedger()->today()->earned()->sum('amount'),
            ],
            'month' => $this->getMonthlyStats(),
            'float_balance' => (float)$this->float_balance,
            'commission_balance' => (float)$this->commission_balance,
            'daily_limit_remaining' => $this->daily_transaction_limit 
                ? max(0, (float)$this->daily_transaction_limit - $this->getTodayTransactionTotal())
                : null,
        ];
    }
}

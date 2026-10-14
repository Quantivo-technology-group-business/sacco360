<?php

namespace Modules\Accounting\Entities;

use Illuminate\Database\Eloquent\Model;

class ChartOfAccount extends Model
{
    protected $table = "chart_of_accounts";
    protected $fillable = [
        'name', 'parent_id', 'gl_code', 'account_type', 'cash_flow_category',
        'allow_manual', 'active', 'notes', 'description',
        'opening_debit', 'opening_credit',
    ];

    /** Parent account (for sub-account hierarchy). */
    public function parent()
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    /** Child accounts. */
    public function children()
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    /** All journal entries posted to this account. */
    public function journal_entries()
    {
        return $this->hasMany(JournalEntry::class, 'chart_of_account_id');
    }

    /** Budget entries for this account. */
    public function budgets()
    {
        return $this->hasMany(AccountBudget::class, 'chart_of_account_id');
    }

    /**
     * Net balance accessor.
     * Asset/Expense = debit normal; Liability/Equity/Income = credit normal.
     */
    public function getNetBalanceAttribute()
    {
        $totalDebit  = $this->journal_entries()->where('reversed', 0)->sum('debit')  + ($this->opening_debit  ?? 0);
        $totalCredit = $this->journal_entries()->where('reversed', 0)->sum('credit') + ($this->opening_credit ?? 0);
        if (in_array($this->account_type, ['asset', 'expense'])) {
            return $totalDebit - $totalCredit;
        }
        return $totalCredit - $totalDebit;
    }

    /** Human-readable account type. */
    public function getAccountTypeLabelAttribute()
    {
        $map = [
            'asset'     => 'Asset',
            'liability' => 'Liability',
            'equity'    => 'Equity',
            'income'    => 'Income',
            'expense'   => 'Expense',
        ];
        return $map[$this->account_type] ?? ucfirst($this->account_type);
    }

    /** Human-readable cash flow category. */
    public function getCashFlowCategoryLabelAttribute()
    {
        $map = [
            'operating'  => 'Operating Activities',
            'investing'  => 'Investing Activities',
            'financing'  => 'Financing Activities',
            'none'       => 'Not Classified',
        ];
        return $map[$this->cash_flow_category ?? 'none'] ?? 'Not Classified';
    }

    public function scopeActive($query)       { return $query->where('active', 1); }
    public function scopeManualAllowed($query) { return $query->where('allow_manual', 1); }
    public function scopeOfType($query, $type) { return $query->where('account_type', $type); }
}

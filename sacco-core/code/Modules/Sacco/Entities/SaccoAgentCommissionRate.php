<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoAgentCommissionRate extends Model
{
    protected $table = 'sacco_agent_commission_rates';

    protected $fillable = [
        'transaction_type', 'min_amount', 'max_amount', 'rate_type',
        'rate_value', 'min_commission', 'max_commission', 'active',
    ];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'rate_value' => 'decimal:4',
        'min_commission' => 'decimal:2',
        'max_commission' => 'decimal:2',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function scopeForType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    public function scopeForAmount($query, $amount)
    {
        return $query->where('min_amount', '<=', $amount)
                     ->where(function ($q) use ($amount) {
                         $q->whereNull('max_amount')
                           ->orWhere('max_amount', '>=', $amount);
                     });
    }

    public function calculateCommission(float $amount): float
    {
        if ($this->rate_type === 'flat') {
            $commission = (float)$this->rate_value;
        } else {
            $commission = $amount * ((float)$this->rate_value / 100);
        }

        if ($this->min_commission && $commission < $this->min_commission) {
            $commission = (float)$this->min_commission;
        }

        if ($this->max_commission && $commission > $this->max_commission) {
            $commission = (float)$this->max_commission;
        }

        return round($commission, 2);
    }

    public static function getCommissionForTransaction(string $type, float $amount): float
    {
        $rate = self::active()
            ->forType($type)
            ->forAmount($amount)
            ->first();

        if (!$rate) {
            return 0;
        }

        return $rate->calculateCommission($amount);
    }

    public function getFormattedRateAttribute(): string
    {
        if ($this->rate_type === 'flat') {
            return number_format($this->rate_value, 2) . ' (flat)';
        }
        return number_format($this->rate_value, 2) . '%';
    }

    public function getAmountRangeAttribute(): string
    {
        $min = number_format($this->min_amount, 0);
        $max = $this->max_amount ? number_format($this->max_amount, 0) : 'Unlimited';
        return "{$min} - {$max}";
    }
}

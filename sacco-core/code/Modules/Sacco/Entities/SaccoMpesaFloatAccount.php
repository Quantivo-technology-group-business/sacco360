<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoMpesaFloatAccount extends Model
{
    protected $fillable = [
        'created_by_id', 'provider', 'short_code', 'account_name', 'type',
        'current_balance', 'last_confirmed_balance', 'last_confirmed_date',
        'minimum_float', 'low_float_alert', 'is_active', 'notes',
    ];

    protected $dates = ['last_confirmed_date'];

    public function entries()
    {
        return $this->hasMany(SaccoMpesaFloatEntry::class);
    }

    public function isLowFloat(): bool
    {
        return $this->minimum_float > 0 && $this->current_balance < $this->minimum_float;
    }

    public function getVarianceAttribute(): float
    {
        if ($this->last_confirmed_balance === null) return 0;
        return (float) $this->current_balance - (float) $this->last_confirmed_balance;
    }
}

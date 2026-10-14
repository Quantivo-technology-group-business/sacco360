<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;

class SaccoDebitCard extends Model
{
    protected $fillable = [
        'client_id', 'savings_id',
        'card_type', 'card_network', 'card_name',
        'card_number', 'card_token',
        'issue_date', 'expiry_date', 'status',
        'daily_limit', 'atm_daily_limit', 'pos_daily_limit',
        'online_daily_limit', 'contactless_limit', 'monthly_limit',
        'atm_enabled', 'pos_enabled', 'online_enabled',
        'contactless_enabled', 'international_enabled',
        'pin_set', 'pin_failed_attempts', 'pin_locked',
        'delivery_status', 'dispatched_date', 'delivered_date', 'delivery_address',
        'sms_alerts', 'email_alerts', 'alert_threshold',
        'issued_by_id', 'blocked_reason', 'cancelled_reason', 'notes',
    ];

    protected $dates = ['issue_date', 'expiry_date', 'dispatched_date', 'delivered_date'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function savings()
    {
        return $this->belongsTo(Savings::class);
    }

    public function transactions()
    {
        return $this->hasMany(SaccoCardTransaction::class);
    }

    public function disputes()
    {
        return $this->hasMany(SaccoCardDispute::class);
    }

    public function pinLogs()
    {
        return $this->hasMany(SaccoCardPinLog::class);
    }

    public function requests()
    {
        return $this->hasMany(SaccoCardRequest::class);
    }

    public function getMaskedNumberAttribute(): string
    {
        return '**** **** **** ' . substr($this->card_number, -4);
    }

    public function getLastFourAttribute(): string
    {
        return substr($this->card_number, -4);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && now()->greaterThan($this->expiry_date);
    }

    public function getMonthlySpend(): float
    {
        return (float) $this->transactions()
            ->whereMonth('transaction_at', now()->month)
            ->whereYear('transaction_at', now()->year)
            ->where('status', 'approved')
            ->whereIn('transaction_type', ['purchase', 'withdrawal'])
            ->sum('amount');
    }

    public function getTodaySpend(?string $channel = null): float
    {
        $q = $this->transactions()
            ->whereDate('transaction_at', today())
            ->where('status', 'approved')
            ->whereIn('transaction_type', ['purchase', 'withdrawal']);
        if ($channel) {
            $q->where('channel', $channel);
        }
        return (float) $q->sum('amount');
    }
}

<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Client\Entities\Client;
use Modules\User\Entities\User;

class UssdMemberConfig extends Model
{
    protected $table = 'sacco_ussd_member_configs';

    protected $fillable = [
        'client_id', 'ussd_enabled', 'pin_hash', 'pin_set',
        'daily_transaction_limit', 'allowed_services',
        'last_access_at', 'failed_pin_attempts', 'locked_at',
        'enabled_by_id', 'enabled_at', 'notes',
    ];

    protected $casts = [
        'ussd_enabled'        => 'boolean',
        'pin_set'             => 'boolean',
        'allowed_services'    => 'array',
        'last_access_at'      => 'datetime',
        'locked_at'           => 'datetime',
        'enabled_at'          => 'datetime',
    ];

    protected $hidden = ['pin_hash'];

    /** All available USSD service keys */
    public const ALL_SERVICES = [
        'balance'   => 'Balance Inquiry',
        'statement' => 'Mini Statement',
        'transfer'  => 'Fund Transfer',
        'loan'      => 'Loan Application',
        'utility'   => 'Utility Payments',
        'deposit'   => 'Savings Deposit',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function enabledBy()
    {
        return $this->belongsTo(User::class, 'enabled_by_id');
    }

    public function isLocked(): bool
    {
        return $this->locked_at !== null;
    }

    public function isServiceAllowed(string $service): bool
    {
        if (empty($this->allowed_services)) {
            return true; // no restriction = all allowed
        }
        return in_array($service, $this->allowed_services, true);
    }

    public function getAllowedServicesLabels(): array
    {
        $services = $this->allowed_services ?? array_keys(self::ALL_SERVICES);
        return array_intersect_key(self::ALL_SERVICES, array_flip($services));
    }
}

<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoPendingApproval extends Model
{
    protected $fillable = [
        'created_by_id', 'approved_by_id', 'approvable_type', 'approvable_id',
        'amount', 'status', 'notes', 'payload', 'label',
    ];

    public function creator()
    {
        return $this->belongsTo(\Modules\User\Entities\User::class, 'created_by_id');
    }

    public function checker()
    {
        return $this->belongsTo(\Modules\User\Entities\User::class, 'approved_by_id');
    }

    public function getDecodedPayloadAttribute(): array
    {
        return $this->payload ? json_decode($this->payload, true) : [];
    }
}

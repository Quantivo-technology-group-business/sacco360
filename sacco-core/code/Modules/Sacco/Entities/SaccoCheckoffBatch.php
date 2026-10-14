<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoCheckoffBatch extends Model
{
    protected $fillable = [
        'created_by_id', 'employer_name', 'period', 'upload_date', 'filename',
        'total_records', 'matched_records', 'unmatched_records', 'total_amount', 'status',
    ];

    public function items()
    {
        return $this->hasMany(SaccoCheckoffItem::class);
    }
}

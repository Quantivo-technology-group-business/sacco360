<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoWelfareClaimDocument extends Model
{
    protected $fillable = [
        'sacco_welfare_claim_id', 'document_type',
        'file_path', 'original_filename', 'uploaded_by_id',
    ];

    public function claim()
    {
        return $this->belongsTo(SaccoWelfareClaim::class);
    }
}

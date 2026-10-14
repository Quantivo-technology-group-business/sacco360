<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoProvisionRun extends Model
{
    protected $table = 'sacco_provision_runs';

    protected $fillable = [
        'created_by_id', 'approved_by_id', 'period_end', 'total_provision',
        'previous_allowance', 'adjustment_amount', 'journal_reference', 'status',
    ];
}

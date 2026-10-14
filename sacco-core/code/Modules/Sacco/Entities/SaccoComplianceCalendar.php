<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoComplianceCalendar extends Model
{
    protected $table = 'sacco_compliance_calendar';

    protected $fillable = [
        'form_code', 'form_name', 'frequency', 'due_date', 'period_start', 'period_end',
        'status', 'submitted_date', 'submitted_by_id',
    ];
}

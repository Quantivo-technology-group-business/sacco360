<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoAuditorAppointment extends Model
{
    protected $table = 'sacco_auditor_appointments';

    protected $fillable = [
        'auditor_name', 'agm_date', 'appointment_date', 'sasra_submission_deadline',
        'sasra_submitted_date', 'status', 'notes',
    ];
}

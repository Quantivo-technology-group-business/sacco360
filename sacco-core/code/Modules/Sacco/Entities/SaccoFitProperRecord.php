<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoFitProperRecord extends Model
{
    protected $table = 'sacco_fit_proper_records';

    protected $fillable = [
        'person_name', 'role', 'appointment_date', 'last_review_date', 'status', 'document_path', 'notes',
    ];
}

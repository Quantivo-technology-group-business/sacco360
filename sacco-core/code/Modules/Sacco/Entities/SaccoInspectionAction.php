<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoInspectionAction extends Model
{
    protected $table = 'sacco_inspection_actions';

    protected $fillable = [
        'created_by_id', 'assigned_to_id', 'inspection_reference', 'inspection_date',
        'action_point', 'deadline', 'status', 'evidence_notes', 'evidence_path', 'completed_date',
    ];
}

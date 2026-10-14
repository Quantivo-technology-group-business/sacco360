<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoReportTemplate extends Model
{
    protected $table = 'sacco_report_templates';

    protected $fillable = [
        'code', 'name', 'form_type', 'frequency', 'field_definitions',
        'query_binding', 'output_mapping', 'active',
    ];

    protected $casts = [
        'field_definitions' => 'array',
        'output_mapping' => 'array',
    ];
}

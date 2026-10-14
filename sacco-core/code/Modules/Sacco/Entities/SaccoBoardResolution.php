<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoBoardResolution extends Model
{
    protected $table = 'sacco_board_resolutions';

    protected $fillable = [
        'created_by_id', 'resolution_number', 'title', 'meeting_type',
        'meeting_date', 'resolution_text', 'document_path', 'status',
    ];
}

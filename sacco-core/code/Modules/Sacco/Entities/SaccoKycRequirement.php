<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoKycRequirement extends Model
{
    protected $fillable = [
        'code', 'name', 'description', 'category', 'mandatory',
        'requires_iprs', 'sort_order', 'active', 'applies_to',
    ];
}

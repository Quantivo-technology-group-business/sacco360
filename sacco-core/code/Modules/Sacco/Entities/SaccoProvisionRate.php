<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoProvisionRate extends Model
{
    protected $fillable = [
        'classification', 'days_in_arrears_min', 'days_in_arrears_max', 'provision_rate',
    ];
}

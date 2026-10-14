<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoUtilityProvider extends Model
{
    protected $table = 'sacco_utility_providers';

    protected $guarded = ['id'];
}

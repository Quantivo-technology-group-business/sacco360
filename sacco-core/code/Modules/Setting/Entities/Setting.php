<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = "settings";

    protected $fillable = [
        'name',
        'setting_key',
        'module',
        'setting_value',
        'category',
        'type',
        'options',
        'class',
        'required',
        'db_columns',
        'displayed',
        'rules',
        'order',
        'info',
    ];

}

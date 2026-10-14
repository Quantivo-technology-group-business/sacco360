<?php

namespace Modules\Client\Entities;

use Illuminate\Database\Eloquent\Model;

class ClientDirector extends Model
{
    protected $table = 'client_directors';

    protected $fillable = [
        'client_id',
        'full_name',
        'id_number',
        'passport_number',
        'kra_pin',
        'phone_number',
        'position',
        'photo',
        'id_copy',
        'pin_certificate',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id', 'id');
    }
}

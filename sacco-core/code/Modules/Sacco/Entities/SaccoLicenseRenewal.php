<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class SaccoLicenseRenewal extends Model
{
    protected $table = 'sacco_license_renewals';

    protected $fillable = [
        'license_number', 'issue_date', 'expiry_date', 'renewal_submitted_date', 'status', 'notes',
    ];
}

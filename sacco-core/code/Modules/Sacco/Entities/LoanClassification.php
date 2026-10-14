<?php

namespace Modules\Sacco\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanClassification extends Model
{
    protected $table = 'loan_classifications';

    protected $fillable = [
        'loan_id', 'classification', 'days_in_arrears', 'outstanding',
        'provision_rate', 'provision_amount', 'previous_classification',
        'classified_by', 'user_id', 'classified_at',
    ];
}

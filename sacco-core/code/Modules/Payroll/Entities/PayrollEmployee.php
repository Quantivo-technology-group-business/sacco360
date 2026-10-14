<?php

namespace Modules\Payroll\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\User\Entities\User;

class PayrollEmployee extends Model
{
    protected $fillable = [
        'user_id', 'branch_id', 'payroll_template_id', 'employee_number', 'department',
        'job_title', 'kra_pin', 'nssf_number', 'shif_number', 'salary_type',
        'basic_salary', 'hourly_rate', 'daily_rate', 'bank_name', 'account_number',
        'date_hired', 'is_active', 'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function payroll_template()
    {
        return $this->belongsTo(PayrollTemplate::class, 'payroll_template_id');
    }
}

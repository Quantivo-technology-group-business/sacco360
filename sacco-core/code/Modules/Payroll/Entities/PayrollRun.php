<?php

namespace Modules\Payroll\Entities;

use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    protected $fillable = [
        'name', 'period', 'period_start', 'period_end', 'branch_id', 'currency_id',
        'created_by_id', 'approved_by_id', 'approved_at', 'status', 'total_gross',
        'total_net', 'total_paye', 'total_nssf', 'total_shif', 'total_housing_levy',
        'employee_count', 'description',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function payrolls()
    {
        return $this->hasMany(Payroll::class, 'payroll_run_id');
    }
}

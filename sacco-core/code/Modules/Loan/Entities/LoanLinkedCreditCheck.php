<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanLinkedCreditCheck extends Model
{
    protected $fillable = ['loan_id', 'loan_credit_check_id', 'status', 'message'];
    public $table = "loan_linked_credit_checks";
}

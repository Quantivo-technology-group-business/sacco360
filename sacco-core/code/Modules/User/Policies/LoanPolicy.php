<?php

namespace Modules\User\Policies;

use Modules\Loan\Entities\Loan;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class LoanPolicy
{
    protected AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function viewAny(User $user): bool
    {
        return $this->auth->checkPermission($user, 'loan.loans.index')
            || $this->auth->checkPermission($user, 'loan.loans.index_own');
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->auth->can($user, 'loan.loans.index', $loan)
            || $this->auth->can($user, 'loan.loans.index_own', $loan);
    }

    public function create(User $user): bool
    {
        return $this->auth->checkPermission($user, 'loan.loans.create');
    }

    public function update(User $user, Loan $loan): bool
    {
        return $this->auth->can($user, 'loan.loans.edit', $loan)
            || $this->auth->can($user, 'loan.loans.edit_own', $loan);
    }

    public function delete(User $user, Loan $loan): bool
    {
        return $this->auth->checkPermission($user, 'loan.loans.destroy');
    }

    public function approve(User $user, Loan $loan): bool
    {
        return $this->auth->can($user, 'loan.loans.approve_loan', $loan);
    }

    public function disburse(User $user, Loan $loan): bool
    {
        return $this->auth->can($user, 'loan.loans.disburse_loan', $loan);
    }
}

<?php

namespace Modules\User\Policies;

use Modules\Savings\Entities\Savings;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class SavingsPolicy
{
    protected AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function viewAny(User $user): bool
    {
        return $this->auth->checkPermission($user, 'savings.savings.index')
            || $this->auth->checkPermission($user, 'savings.savings.index_own');
    }

    public function view(User $user, Savings $savings): bool
    {
        return $this->auth->can($user, 'savings.savings.index', $savings)
            || $this->auth->can($user, 'savings.savings.index_own', $savings);
    }

    public function create(User $user): bool
    {
        return $this->auth->checkPermission($user, 'savings.savings.create');
    }

    public function update(User $user, Savings $savings): bool
    {
        return $this->auth->can($user, 'savings.savings.edit', $savings);
    }
}

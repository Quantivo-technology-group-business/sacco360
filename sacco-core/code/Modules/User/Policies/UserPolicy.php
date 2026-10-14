<?php

namespace Modules\User\Policies;

use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class UserPolicy
{
    protected AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function viewAny(User $user): bool
    {
        return $this->auth->checkPermission($user, 'user.users.index');
    }

    public function view(User $user, User $model): bool
    {
        return $this->auth->checkPermission($user, 'user.users.index');
    }

    public function create(User $user): bool
    {
        return $this->auth->checkPermission($user, 'user.users.create');
    }

    public function update(User $user, User $model): bool
    {
        if ($model->hasRole('admin') && !$user->hasRole('admin')) {
            return false;
        }

        return $this->auth->checkPermission($user, 'user.users.edit');
    }

    public function delete(User $user, User $model): bool
    {
        if ($model->hasRole('admin') && !$user->hasRole('admin')) {
            return false;
        }

        return $this->auth->checkPermission($user, 'user.users.destroy');
    }
}

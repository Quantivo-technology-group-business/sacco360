<?php

namespace Modules\User\Policies;

use Modules\Share\Entities\Share;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class SharePolicy
{
    protected AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function viewAny(User $user): bool
    {
        return $this->auth->checkPermission($user, 'share.shares.index')
            || $this->auth->checkPermission($user, 'share.shares.index_own');
    }

    public function view(User $user, Share $share): bool
    {
        return $this->auth->can($user, 'share.shares.index', $share)
            || $this->auth->can($user, 'share.shares.index_own', $share);
    }

    public function create(User $user): bool
    {
        return $this->auth->checkPermission($user, 'share.shares.create');
    }

    public function update(User $user, Share $share): bool
    {
        return $this->auth->can($user, 'share.shares.edit', $share);
    }
}

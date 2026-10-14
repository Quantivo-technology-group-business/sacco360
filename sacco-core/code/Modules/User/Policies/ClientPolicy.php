<?php

namespace Modules\User\Policies;

use Modules\Client\Entities\Client;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class ClientPolicy
{
    protected AuthorizationService $auth;

    public function __construct(AuthorizationService $auth)
    {
        $this->auth = $auth;
    }

    public function viewAny(User $user): bool
    {
        return $this->auth->checkPermission($user, 'client.clients.index')
            || $this->auth->checkPermission($user, 'client.clients.index_own');
    }

    public function view(User $user, Client $client): bool
    {
        return $this->auth->can($user, 'client.clients.index', $client)
            || $this->auth->can($user, 'client.clients.index_own', $client);
    }

    public function create(User $user): bool
    {
        return $this->auth->checkPermission($user, 'client.clients.create');
    }

    public function update(User $user, Client $client): bool
    {
        return $this->auth->can($user, 'client.clients.edit', $client)
            || $this->auth->can($user, 'client.clients.edit_own', $client);
    }

    public function delete(User $user, Client $client): bool
    {
        return $this->auth->can($user, 'client.clients.destroy', $client)
            || $this->auth->can($user, 'client.clients.destroy_own', $client);
    }
}

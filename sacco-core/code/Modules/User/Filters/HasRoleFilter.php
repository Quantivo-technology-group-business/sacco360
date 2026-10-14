<?php

namespace Modules\User\Filters;

use Illuminate\Database\Eloquent\Builder;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class HasRoleFilter
{
    protected AuthorizationService $authService;

    public function __construct(AuthorizationService $authService)
    {
        $this->authService = $authService;
    }

    public function apply(Builder $query, User $user, string $permission, string $branchColumn = 'branch_id', string $ownerColumn = 'loan_officer_id'): Builder
    {
        return $this->authService->applyAuthorizationScopes($query, $user, $permission, $branchColumn, $ownerColumn);
    }
}

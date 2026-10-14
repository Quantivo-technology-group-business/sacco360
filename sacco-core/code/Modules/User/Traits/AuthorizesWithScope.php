<?php

namespace Modules\User\Traits;

use Illuminate\Database\Eloquent\Builder;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

trait AuthorizesWithScope
{
    protected function authorizeScoped(string $permission, $model = null): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (!app(AuthorizationService::class)->can($user, $permission, $model)) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function applyAuthorizationScopes(Builder $query, string $permission, string $branchColumn = 'branch_id', string $ownerColumn = 'loan_officer_id'): Builder
    {
        /** @var User $user */
        $user = auth()->user();

        return app(AuthorizationService::class)->applyAuthorizationScopes(
            $query,
            $user,
            $permission,
            $branchColumn,
            $ownerColumn
        );
    }
}

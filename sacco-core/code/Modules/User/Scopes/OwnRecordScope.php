<?php

namespace Modules\User\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class OwnRecordScope implements Scope
{
    protected string $permission;
    protected string $ownerColumn;

    public function __construct(string $permission, string $ownerColumn = 'loan_officer_id')
    {
        $this->permission = $permission;
        $this->ownerColumn = $ownerColumn;
    }

    public function apply(Builder $builder, Model $model)
    {
        if (!auth()->check()) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        app(AuthorizationService::class)->applyOwnRecordScope($builder, $user, $this->permission, $this->ownerColumn);
    }
}

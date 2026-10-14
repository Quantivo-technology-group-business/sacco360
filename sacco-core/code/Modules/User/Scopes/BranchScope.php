<?php

namespace Modules\User\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Modules\User\Entities\User;
use Modules\User\Services\AuthorizationService;

class BranchScope implements Scope
{
    protected string $branchColumn;

    public function __construct(string $branchColumn = 'branch_id')
    {
        $this->branchColumn = $branchColumn;
    }

    public function apply(Builder $builder, Model $model)
    {
        if (!auth()->check()) {
            return;
        }

        /** @var User $user */
        $user = auth()->user();
        app(AuthorizationService::class)->applyBranchScope($builder, $user, $this->branchColumn);
    }
}

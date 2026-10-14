<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Savings\Entities\Savings;
use Modules\Share\Entities\Share;
use Modules\User\Entities\User;
use Modules\User\Policies\ClientPolicy;
use Modules\User\Policies\LoanPolicy;
use Modules\User\Policies\SavingsPolicy;
use Modules\User\Policies\SharePolicy;
use Modules\User\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Client::class => ClientPolicy::class,
        Loan::class => LoanPolicy::class,
        Savings::class => SavingsPolicy::class,
        Share::class => SharePolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot()
    {
        $this->registerPolicies();
    }
}

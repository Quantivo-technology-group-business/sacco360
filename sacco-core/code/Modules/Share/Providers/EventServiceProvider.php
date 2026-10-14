<?php

namespace Modules\Share\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        'Modules\Share\Events\TransactionUpdated' => [
            'Modules\Share\Listeners\UpdateShareTotals',
        ],
    ];

    public function boot()
    {
        parent::boot();
    }
}

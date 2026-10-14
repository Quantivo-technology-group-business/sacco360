<?php

namespace Modules\Share\Events;

use Illuminate\Queue\SerializesModels;
use Modules\Share\Entities\Share;

class TransactionUpdated
{
    use SerializesModels;

    public $share;

    public function __construct(Share $share)
    {
        $this->share = $share;
    }

    public function broadcastOn()
    {
        return [];
    }
}

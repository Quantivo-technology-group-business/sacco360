<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Services\SaccoDetailService;

class SaccoDetailSeeder extends Seeder
{
    public function run()
    {
        if (!Schema::hasTable('sacco_details')) {
            return;
        }

        (new SaccoDetailService())->get();
    }
}

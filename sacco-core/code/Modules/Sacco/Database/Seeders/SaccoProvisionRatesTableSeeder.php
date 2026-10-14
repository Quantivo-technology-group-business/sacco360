<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SaccoProvisionRatesTableSeeder extends Seeder
{
    public function run()
    {
        if (DB::table('sacco_provision_rates')->count() > 0) {
            return;
        }
        DB::table('sacco_provision_rates')->insert([
            ['classification' => 'Performing', 'days_in_arrears_min' => 0, 'days_in_arrears_max' => 0, 'provision_rate' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['classification' => 'Watch', 'days_in_arrears_min' => 1, 'days_in_arrears_max' => 30, 'provision_rate' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['classification' => 'Substandard', 'days_in_arrears_min' => 31, 'days_in_arrears_max' => 180, 'provision_rate' => 25, 'created_at' => now(), 'updated_at' => now()],
            ['classification' => 'Doubtful', 'days_in_arrears_min' => 181, 'days_in_arrears_max' => 360, 'provision_rate' => 50, 'created_at' => now(), 'updated_at' => now()],
            ['classification' => 'Loss', 'days_in_arrears_min' => 361, 'days_in_arrears_max' => null, 'provision_rate' => 100, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

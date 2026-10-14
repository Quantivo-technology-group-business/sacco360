<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;

class DemoBranchesSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('branches')) {
            return;
        }

        $this->initRuntime();

        $defaultBranchId = DB::table('branches')->where('name', 'Default')->value('id')
            ?? DB::table('branches')->value('id');

        $branchIds = $defaultBranchId ? [$defaultBranchId] : [];

        $branches = [
            'Nairobi HQ',
            'Mombasa Branch',
            'Kisumu Branch',
            'Nakuru Branch',
        ];

        $today = self::context('today');
        $now = self::context('now');

        foreach ($branches as $name) {
            $existing = DB::table('branches')->where('name', $name)->value('id');
            if ($existing) {
                $branchIds[] = $existing;
                continue;
            }

            $branchIds[] = DB::table('branches')->insertGetId([
                'name' => $name,
                'is_system' => 0,
                'open_date' => $today,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        self::setContextValue('branchIds', array_values(array_unique($branchIds)));
    }
}

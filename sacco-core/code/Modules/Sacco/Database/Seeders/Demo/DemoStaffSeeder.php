<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoStaffSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('staff')) {
            return;
        }

        $this->initRuntime();

        $branchIds = self::context('branchIds', []);
        if (empty($branchIds)) {
            $branchIds = [DB::table('branches')->value('id') ?? 1];
        }

        $officers = [
            ['Brian', 'Otieno'],
            ['Lucy', 'Mwangi'],
            ['Samuel', 'Kariuki'],
            ['Anne', 'Wambui'],
            ['Joseph', 'Njoroge'],
        ];

        $tellers = [
            ['Faith', 'Chebet'],
            ['Kevin', 'Omondi'],
        ];

        $loanOfficerIds = [];
        foreach ($officers as $i => $officer) {
            $branchId = $branchIds[$i % count($branchIds)];
            $userId = $this->createStaffUser($officer[0], $officer[1], 'admin', $branchId);
            $loanOfficerIds[] = $userId;

            if (Schema::hasTable('branch_users')) {
                $exists = DB::table('branch_users')
                    ->where('user_id', $userId)
                    ->where('branch_id', $branchId)
                    ->exists();
                if (!$exists) {
                    DB::table('branch_users')->insert([
                        'user_id' => $userId,
                        'branch_id' => $branchId,
                        'created_at' => self::context('now'),
                        'updated_at' => self::context('now'),
                    ]);
                }
            }
        }

        $tellerIds = [];
        foreach ($tellers as $i => $teller) {
            $branchId = $branchIds[$i % count($branchIds)];
            $userId = $this->createStaffUser($teller[0], $teller[1], 'admin', $branchId);
            $tellerIds[] = $userId;

            if (Schema::hasTable('sacco_tellers')) {
                $exists = DB::table('sacco_tellers')->where('user_id', $userId)->exists();
                if (!$exists) {
                    DB::table('sacco_tellers')->insert([
                        'user_id' => $userId,
                        'branch_id' => $branchId,
                        'till_number' => 'TILL-' . ($i + 1),
                        'opening_balance' => 50000,
                        'current_balance' => 50000,
                        'status' => 'closed',
                        'created_at' => self::context('now'),
                        'updated_at' => self::context('now'),
                    ]);
                }
            }
        }

        self::setContextValue('loanOfficerIds', $loanOfficerIds);
        self::setContextValue('tellerIds', $tellerIds);
        self::setContextValue('staffUserIds', array_merge($loanOfficerIds, $tellerIds));
    }
}

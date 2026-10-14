<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoMembersSeeder extends DemoSeederBase
{
    protected array $firstNames = [
        'James', 'Mary', 'Peter', 'Grace', 'David', 'Faith', 'John', 'Sarah', 'Daniel', 'Ruth',
        'Michael', 'Esther', 'Paul', 'Joyce', 'Simon', 'Catherine', 'Mark', 'Lucy', 'Thomas', 'Alice',
        'George', 'Helen', 'Charles', 'Nancy', 'Robert', 'Jane', 'William', 'Ann', 'Joseph', 'Rose',
    ];

    protected array $lastNames = [
        'Kamau', 'Wanjiku', 'Ochieng', 'Achieng', 'Mutua', 'Njeri', 'Kipchoge', 'Wambui', 'Otieno', 'Mwangi',
        'Kariuki', 'Chebet', 'Njoroge', 'Adhiambo', 'Omondi', 'Wanjala', 'Muthoni', 'Kibet', 'Akinyi', 'Barasa',
    ];

    public function run()
    {
        if (!self::shouldRun('members')) {
            return;
        }

        $this->initRuntime();

        $count = (int)self::context('memberCount', 250);
        $branchIds = self::context('branchIds', [DB::table('branches')->value('id') ?? 1]);
        $loanOfficerIds = self::context('loanOfficerIds', [self::context('adminUserId')]);
        $userId = self::context('adminUserId');
        $today = self::context('today');
        $now = self::context('now');
        $faker = $this->faker();

        $idTypeId = DB::table('client_identification_types')->value('id');

        $memberIds = [];
        $memberMeta = [];
        $clientRows = [];

        for ($i = 1; $i <= $count; $i++) {
            $firstName = $this->firstNames[($i - 1) % count($this->firstNames)];
            $lastName = $this->lastNames[intdiv($i - 1, count($this->firstNames)) % count($this->lastNames)];
            $branchId = $branchIds[($i - 1) % count($branchIds)];
            $officerId = $loanOfficerIds[($i - 1) % count($loanOfficerIds)];

            $statusRoll = $i % 20;
            if ($statusRoll === 0) {
                $status = 'pending';
                $memberStatusDetail = 'pending';
            } elseif ($statusRoll <= 2) {
                $status = 'active';
                $memberStatusDetail = 'dormant';
            } else {
                $status = 'active';
                $memberStatusDetail = 'active';
            }

            $kycVerified = ($i % 5 !== 0) ? 1 : 0;

            $row = [
                'created_by_id' => $userId,
                'branch_id' => $branchId,
                'loan_officer_id' => $officerId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $i % 2 === 0 ? 'female' : 'male',
                'status' => $status,
                'mobile' => $this->kenyanMobile(2000 + $i),
                'email' => strtolower($firstName . '.' . $lastName . $i) . '@sacco-demo.local',
                'account_number' => 'MEM-DEMO-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT),
                'external_id' => $i === 1 ? self::SEED_MARKER : self::SEED_PREFIX . '-MEMBER-' . $i,
                'dob' => $faker->date('Y-m-d', '-25 years'),
                'address' => $faker->randomElement(['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret']) . ', Kenya',
                'city' => $faker->city,
                'created_date' => $today,
                'joined_date' => date('Y-m-d', strtotime('-' . rand(30, 1825) . ' days')),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (Schema::hasColumn('clients', 'kyc_verified')) {
                $row['kyc_verified'] = $kycVerified;
            }
            if (Schema::hasColumn('clients', 'member_status_detail')) {
                $row['member_status_detail'] = $memberStatusDetail;
            }
            if (Schema::hasColumn('clients', 'kyc_status')) {
                $row['kyc_status'] = $kycVerified ? 'verified' : 'incomplete';
            }
            if (Schema::hasColumn('clients', 'member_number')) {
                $row['member_number'] = 'MN-DEMO-' . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            }

            $clientRows[] = $row;
        }

        foreach (array_chunk($clientRows, 50) as $chunk) {
            DB::table('clients')->insert($chunk);
        }

        $inserted = DB::table('clients')
            ->where('external_id', 'like', self::SEED_PREFIX . '-%')
            ->orWhere('external_id', self::SEED_MARKER)
            ->orderBy('id')
            ->get(['id', 'external_id', 'branch_id', 'first_name', 'last_name', 'status']);

        $identRows = [];
        $kinRows = [];

        foreach ($inserted as $idx => $client) {
            $memberIds[] = $client->id;
            $memberMeta[$client->id] = [
                'index' => $idx + 1,
                'branch_id' => $client->branch_id,
                'status' => $client->status,
            ];

            if ($idTypeId) {
                $identRows[] = [
                    'created_by_id' => $userId,
                    'client_id' => $client->id,
                    'client_identification_type_id' => $idTypeId,
                    'identification_value' => (string)(30000000 + $idx + 1),
                    'description' => 'National ID',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($idx % 3 === 0) {
                $kinRows[] = [
                    'created_by_id' => $userId,
                    'client_id' => $client->id,
                    'first_name' => $faker->firstName,
                    'last_name' => $client->last_name,
                    'gender' => 'unspecified',
                    'mobile' => $this->kenyanMobile(5000 + $idx),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($identRows)) {
            foreach (array_chunk($identRows, 50) as $chunk) {
                DB::table('client_identification')->insert($chunk);
            }
        }

        if (!empty($kinRows) && Schema::hasTable('client_next_of_kin')) {
            foreach (array_chunk($kinRows, 50) as $chunk) {
                DB::table('client_next_of_kin')->insert($chunk);
            }
        }

        self::setContextValue('memberIds', $memberIds);
        self::setContextValue('memberMeta', $memberMeta);
    }
}

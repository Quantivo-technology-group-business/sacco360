<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoAssetSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('assets')) {
            return;
        }

        $this->initRuntime();

        if (!Schema::hasTable('asset_types') || !Schema::hasTable('assets')) {
            return;
        }

        $userId = self::context('adminUserId');
        $branchIds = self::context('branchIds', [1]);
        $today = self::context('today');
        $now = self::context('now');
        $branchId = $branchIds[0];

        $typeIds = [];
        foreach (['Furniture', 'Computer Equipment', 'Motor Vehicles'] as $name) {
            $typeIds[] = DB::table('asset_types')->insertGetId([
                'name' => self::SEED_PREFIX . ' ' . $name,
                'type' => $name === 'Motor Vehicles' ? 'fixed' : 'fixed',
                'notes' => 'Demo asset type',
            ]);
        }

        $assetNames = [
            'Office Desk Set', 'HP Laptop', 'Conference Table', 'Printer',
            'Toyota Hilux', 'Server Rack', 'Air Conditioner', 'Safe Vault',
        ];

        foreach ($assetNames as $i => $name) {
            $purchasePrice = rand(50000, 2500000);
            $assetId = DB::table('assets')->insertGetId([
                'created_by_id' => $userId,
                'asset_type_id' => $typeIds[$i % count($typeIds)],
                'branch_id' => $branchId,
                'name' => self::SEED_PREFIX . ' ' . $name,
                'purchase_date' => date('Y-m-d', strtotime('-' . rand(1, 5) . ' years')),
                'purchase_price' => $purchasePrice,
                'value' => round($purchasePrice * 0.7, 2),
                'life_span' => 5,
                'salvage_value' => round($purchasePrice * 0.1, 2),
                'serial_number' => 'SN-DEMO-' . ($i + 1),
                'status' => 'active',
                'active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            if (Schema::hasTable('asset_depreciation')) {
                $depreciation = round($purchasePrice * 0.2, 2);
                DB::table('asset_depreciation')->insert([
                    'asset_id' => $assetId,
                    'year' => date('Y'),
                    'beginning_value' => $purchasePrice,
                    'depreciation_value' => $depreciation,
                    'rate' => 20,
                    'cost' => $purchasePrice,
                    'accumulated' => $depreciation,
                    'ending_value' => $purchasePrice - $depreciation,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

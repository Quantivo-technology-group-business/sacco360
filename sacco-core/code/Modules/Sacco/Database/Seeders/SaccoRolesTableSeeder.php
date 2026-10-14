<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;

class SaccoRolesTableSeeder extends Seeder
{
    public function run()
    {
        $teller = Role::firstOrCreate(['name' => 'SaccoTeller', 'guard_name' => 'web']);
        $teller->syncPermissions(Permission::whereIn('name', [
            'sacco.search.index', 'sacco.teller.index', 'sacco.transfers.index',
            'sacco.earmarks.index', 'sacco.cheques.index',
        ])->pluck('name')->toArray());

        $creditOfficer = Role::firstOrCreate(['name' => 'SaccoCreditOfficer', 'guard_name' => 'web'], [
            'parent_role_id' => $teller->id,
        ]);
        $creditOfficer->update(['parent_role_id' => $teller->id]);
        $creditOfficer->syncPermissions(Permission::whereIn('name', [
            'loan.loans.index', 'loan.loans.create', 'loan.loans.edit',
        ])->pluck('name')->toArray());

        $financeManager = Role::firstOrCreate(['name' => 'SaccoFinanceManager', 'guard_name' => 'web'], [
            'parent_role_id' => $teller->id,
        ]);
        $financeManager->update(['parent_role_id' => $teller->id]);
        $financeManager->syncPermissions(Permission::whereIn('name', [
            'sacco.compliance.dashboard', 'sacco.regulatory.index', 'sacco.regulatory.export',
            'sacco.regulatory.caels', 'accounting.reports.balance_sheet',
            'accounting.reports.income_statement', 'sacco.period_end.index',
            'sacco.checker.approve',
        ])->pluck('name')->toArray());

        $complianceOfficer = Role::firstOrCreate(['name' => 'SaccoComplianceOfficer', 'guard_name' => 'web'], [
            'parent_role_id' => $creditOfficer->id,
        ]);
        $complianceOfficer->update(['parent_role_id' => $creditOfficer->id]);
        $complianceOfficer->syncPermissions(Permission::whereIn('name', [
            'sacco.compliance.dashboard', 'sacco.compliance.ratios',
            'sacco.regulatory.index', 'sacco.regulatory.export', 'sacco.regulatory.caels',
            'sacco.aml.index', 'sacco.governance.licenses', 'sacco.governance.resolutions',
            'sacco.governance.inspections', 'sacco.classification.override',
            'sacco.report_templates.manage',
        ])->pluck('name')->toArray());

        $boardMember = Role::firstOrCreate(['name' => 'SaccoBoardMember', 'guard_name' => 'web']);
        $boardMember->syncPermissions(Permission::whereIn('name', [
            'sacco.compliance.dashboard', 'sacco.governance.resolutions',
            'sacco.governance.licenses', 'sacco.regulatory.index',
        ])->pluck('name')->toArray());
    }
}

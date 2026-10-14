<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AgentRoleSeeder extends Seeder
{
    public function run()
    {
        $agentPermissions = [
            'sacco.agent_portal.access',
            'sacco.agent_portal.deposit',
            'sacco.agent_portal.withdrawal',
            'sacco.agent_portal.loan_payment',
            'sacco.agent_portal.utility',
            'sacco.agent_portal.register',
            'sacco.agent_portal.commissions',
        ];

        foreach ($agentPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        $agentRole = Role::firstOrCreate([
            'name' => 'agent',
            'guard_name' => 'web',
        ]);

        $agentRole->syncPermissions($agentPermissions);

        $this->command->info('Agent role and permissions created successfully.');
    }
}

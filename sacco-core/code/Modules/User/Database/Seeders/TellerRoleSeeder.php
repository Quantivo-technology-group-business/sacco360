<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\User\Entities\Permission;
use Modules\User\Entities\Role;

/**
 * Creates the "Teller" role with the minimum permissions needed to:
 *  - Log in and access the teller desk
 *  - Open / close teller sessions
 *  - Look up clients / members
 *  - View savings accounts and receive deposits
 *  - View loans and receive repayments
 *  - Print receipts and view the daily teller ledger
 *
 * The teller CANNOT: approve/disburse loans, manage users, access accounting,
 * delete or reverse transactions, or change system settings.
 *
 * Usage:
 *   php artisan db:seed --class="Modules\User\Database\Seeders\TellerRoleSeeder"
 */
class TellerRoleSeeder extends Seeder
{
    public function run(): void
    {
        // ── Create (or retrieve) the Teller role ──────────────────────────────
        $teller = Role::firstOrCreate(
            ['name' => 'Teller', 'guard_name' => 'web'],
            ['description' => 'Front-desk teller — receives deposits and loan repayments']
        );

        // ── Permissions this role gets ─────────────────────────────────────────
        $permissionNames = [

            // Teller session management
            'sacco.teller.index',           // access teller desk
            'sacco.teller.manage',          // open / close sessions

            // Client lookup
            'client.clients.index',         // search / view clients
            'sacco.search.index',           // global member search

            // Savings — view and transact (deposit / withdrawal)
            'savings.savings.index',
            'savings.savings.transactions.index',
            'savings.savings.transactions.create',

            // Loans — view and receive repayments only
            'loan.loans.index',
            'loan.loans.transactions.index',
            'loan.loans.transactions.create',

            // Dashboard — basic KPIs
            'dashboard.dashboard.index',
        ];

        // Only sync permissions that actually exist in the database
        $existing = Permission::whereIn('name', $permissionNames)->pluck('name')->toArray();
        $teller->syncPermissions($existing);

        $this->command->info('Teller role created/updated with ' . count($existing) . ' permissions.');

        // ── Convenience: create a demo teller user (only in non-production) ──
        if (app()->environment('local', 'staging')) {
            $user = \Modules\User\Entities\User::firstOrCreate(
                ['email' => 'teller@savoirmicro.com'],
                [
                    'first_name' => 'Demo',
                    'last_name'  => 'Teller',
                    'password'   => bcrypt('Teller@1234'),
                    'active'     => 1,
                ]
            );
            $user->syncRoles([$teller->name]);
            $this->command->info('Demo teller user: teller@savoirmicro.com / Teller@1234');
        }
    }
}

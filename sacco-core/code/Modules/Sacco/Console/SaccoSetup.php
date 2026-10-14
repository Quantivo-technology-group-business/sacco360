<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Database\Seeders\SaccoDatabaseSeeder;

class SaccoSetup extends Command
{
    protected $signature = 'sacco:setup {--backfill : Also backfill member numbers}';
    protected $description = 'Run SACCO migrations, seed permissions/menus, and optionally backfill member numbers';

    public function handle()
    {
        $this->call('migrate', ['--force' => true]);
        $this->info('Migrations complete.');

        $this->call('db:seed', ['--class' => SaccoDatabaseSeeder::class, '--force' => true]);
        $this->info('SACCO permissions and menus seeded.');

        if ($this->option('backfill')) {
            $this->call('sacco:backfill-member-numbers');
        }

        $this->info('SACCO setup finished. New menu items: Earmarks, Bank Reconciliation, ATM Transactions.');
        return 0;
    }
}

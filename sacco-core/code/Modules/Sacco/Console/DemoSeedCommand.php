<?php

namespace Modules\Sacco\Console;

use Illuminate\Console\Command;
use Modules\Sacco\Database\Seeders\Demo\ComprehensiveDemoDataSeeder;
use Modules\Sacco\Database\Seeders\Demo\DemoSeederBase;

class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed
                            {--members=250 : Number of demo members to create}
                            {--force : Remove existing demo data and re-seed}
                            {--only= : Comma-separated seeder keys (branches,staff,members,accounts,loans,loan_applications,transactions,sacco,accounting,payroll,assets,wallets,communication)}';

    protected $description = 'Seed comprehensive demo data (250 members by default) across all business modules';

    public function handle()
    {
        $memberCount = max(1, (int)$this->option('members'));
        $force = (bool)$this->option('force');
        $only = array_filter(array_map('trim', explode(',', (string)$this->option('only'))));

        DemoSeederBase::resetContext();
        DemoSeederBase::setContext([
            'memberCount' => $memberCount,
            'force' => $force,
            'only' => $only,
        ]);

        if ($force) {
            $this->warn('Removing existing demo data...');
            DemoSeederBase::wipeDemoData();
            $this->info('Demo data cleanup complete.');
        } elseif (DemoSeederBase::alreadySeeded()) {
            $this->error('Demo data already exists. Use --force to re-seed.');
            return 1;
        }

        $this->info("Seeding demo data with {$memberCount} members...");

        $start = microtime(true);
        $this->call('db:seed', [
            '--class' => ComprehensiveDemoDataSeeder::class,
            '--force' => true,
        ]);
        $elapsed = round(microtime(true) - $start, 2);

        $demoCount = \DB::table('clients')
            ->where('external_id', 'like', DemoSeederBase::SEED_PREFIX . '-%')
            ->orWhere('external_id', DemoSeederBase::SEED_MARKER)
            ->count();

        $loanCount = \DB::table('loans')
            ->where('external_id', 'like', DemoSeederBase::SEED_PREFIX . '-LOAN-%')
            ->count();

        $applicationCount = \Schema::hasTable('loan_applications')
            ? \DB::table('loan_applications')->where('notes', 'like', DemoSeederBase::SEED_PREFIX . '%')->count()
            : 0;

        $this->info("Demo seed complete in {$elapsed}s.");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Demo members', $demoCount],
                ['Demo loans', $loanCount],
                ['Demo loan applications', $applicationCount],
                ['Demo branches', count(DemoSeederBase::context('branchIds', []))],
            ]
        );

        return 0;
    }
}

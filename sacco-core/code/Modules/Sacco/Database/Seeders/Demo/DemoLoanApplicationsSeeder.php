<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoLoanApplicationsSeeder extends DemoSeederBase
{
    protected array $pendingNotes = [
        'Business expansion - retail shop inventory',
        'School fees for children - term 2',
        'Medical emergency - hospital bill',
        'Home improvement - roof repair',
        'Agricultural inputs - planting season',
        'Motorcycle purchase for boda boda business',
        'Working capital for small enterprise',
        'Debt consolidation from informal lenders',
    ];

    protected array $rejectedNotes = [
        'Insufficient savings balance for guarantor requirement',
        'Incomplete KYC documentation',
        'Existing loan arrears on another account',
        'Requested amount exceeds loan multiplier limit',
        'Member account dormant for over 12 months',
    ];

    public function run()
    {
        if (!self::shouldRun('loan_applications')) {
            return;
        }

        if (!Schema::hasTable('loan_applications')) {
            return;
        }

        $this->initRuntime();

        $memberIds = self::context('memberIds', []);
        $memberMeta = self::context('memberMeta', []);
        if (empty($memberIds)) {
            $memberIds = DB::table('clients')
                ->where('external_id', 'like', self::SEED_PREFIX . '-%')
                ->orWhere('external_id', self::SEED_MARKER)
                ->pluck('id')
                ->all();
            foreach ($memberIds as $clientId) {
                $client = DB::table('clients')->where('id', $clientId)->first(['branch_id', 'status']);
                $memberMeta[$clientId] = [
                    'branch_id' => $client->branch_id ?? null,
                    'status' => $client->status ?? 'active',
                ];
            }
        }
        $products = self::context('productIds', []) ?: $this->resolveProducts();
        $loanProductId = $products['loan'] ?? DB::table('loan_products')->value('id');

        if (empty($memberIds) || !$loanProductId) {
            return;
        }

        $userId = self::context('adminUserId');
        $now = self::context('now');
        $rows = [];

        $demoLoans = DB::table('loans')
            ->where('external_id', 'like', self::SEED_PREFIX . '-LOAN-%')
            ->get(['id', 'client_id', 'applied_amount', 'branch_id']);

        $existingAppLoanIds = [];
        if ($demoLoans->isNotEmpty()) {
            $existingAppLoanIds = DB::table('loan_applications')
                ->whereNotNull('loan_id')
                ->whereIn('loan_id', $demoLoans->pluck('id'))
                ->pluck('loan_id')
                ->all();
        }

        foreach ($demoLoans as $loan) {
            if (in_array($loan->id, $existingAppLoanIds, true)) {
                continue;
            }
            $rows[] = $this->applicationRow(
                $loan->client_id,
                (int)($loan->branch_id ?? 1),
                $loanProductId,
                (float)$loan->applied_amount,
                'approved',
                self::SEED_PREFIX . ': Approved and converted to loan #' . $loan->id,
                $userId,
                $now,
                $loan->id
            );
        }

        $clientsWithLoans = $demoLoans->pluck('client_id')->all();
        $existingPendingRejected = DB::table('loan_applications')
            ->whereIn('client_id', $memberIds)
            ->where('notes', 'like', self::SEED_PREFIX . '%')
            ->pluck('client_id')
            ->all();

        $eligible = array_values(array_filter($memberIds, function ($clientId) use ($memberMeta, $clientsWithLoans, $existingPendingRejected) {
            if (in_array($clientId, $clientsWithLoans, true) || in_array($clientId, $existingPendingRejected, true)) {
                return false;
            }
            return ($memberMeta[$clientId]['status'] ?? 'active') === 'active';
        }));

        $pendingCount = max(1, (int)floor(count($eligible) * 0.12));
        $rejectedCount = max(1, (int)floor(count($eligible) * 0.05));

        foreach (array_slice($eligible, 0, $pendingCount) as $i => $clientId) {
            $branchId = (int)($memberMeta[$clientId]['branch_id'] ?? self::context('branchIds')[0] ?? 1);
            $amount = rand(30000, 400000);
            $rows[] = $this->applicationRow(
                $clientId,
                $branchId,
                $loanProductId,
                $amount,
                'pending',
                self::SEED_PREFIX . ': ' . $this->pendingNotes[$i % count($this->pendingNotes)],
                $userId,
                $now
            );
        }

        $rejectedPool = array_values(array_filter(
            $eligible,
            fn($id) => !in_array($id, array_slice($eligible, 0, $pendingCount), true)
        ));
        foreach (array_slice($rejectedPool, 0, $rejectedCount) as $i => $clientId) {
            $branchId = (int)($memberMeta[$clientId]['branch_id'] ?? self::context('branchIds')[0] ?? 1);
            $amount = rand(50000, 600000);
            $rows[] = $this->applicationRow(
                $clientId,
                $branchId,
                $loanProductId,
                $amount,
                'rejected',
                self::SEED_PREFIX . ': ' . $this->rejectedNotes[$i % count($this->rejectedNotes)],
                $userId,
                $now
            );
        }

        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, 50) as $chunk) {
            DB::table('loan_applications')->insert($chunk);
        }

        self::setContextValue('loanApplicationCount', count($rows));
    }

    protected function applicationRow(
        int $clientId,
        int $branchId,
        int $loanProductId,
        float $amount,
        string $status,
        string $notes,
        int $userId,
        $now,
        ?int $loanId = null
    ): array {
        $createdAt = date('Y-m-d H:i:s', strtotime('-' . rand(1, 45) . ' days', strtotime((string)$now)));

        return [
            'branch_id' => $branchId,
            'created_by_id' => $userId,
            'client_id' => $clientId,
            'loan_id' => $loanId,
            'loan_product_id' => $loanProductId,
            'amount' => $amount,
            'status' => $status,
            'notes' => $notes,
            'created_at' => $createdAt,
            'updated_at' => $now,
        ];
    }
}

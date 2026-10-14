<?php

namespace Modules\Sacco\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Client\Entities\ClientType;
use Modules\Sacco\Entities\SaccoGroup;
use Maatwebsite\Excel\Facades\Excel;

class DataImportService
{
    public function importMembers(string $path, string $extension, string $filename = ''): array
    {
        if (in_array(strtolower($extension), ['xlsx', 'xls'])) {
            $rows = $this->parseExcel($path, $extension);
        } else {
            $rows = $this->parseCsv($path);
        }

        $total   = count($rows);
        $errors  = [];
        $imported = 0;
        $updatedCount = 0;

        if ($total === 0) {
            return ['imported' => 0, 'total' => 0, 'errors' => ['No data rows found.'],
                    'reconciliation' => ['success_rate' => 0]];
        }

        // Generate a batch ID for this import (allows unseed later)
        $batchId = \Illuminate\Support\Str::uuid()->toString();

        // ── Pre-load lookups (avoid N+1) ─────────────────────────────────────
        // Client types keyed by lowercase name
        $clientTypes = ClientType::all()->keyBy(fn($ct) => strtolower($ct->name));
        $defaultClientType = $clientTypes->first();

        // Pre-load existing clients by external_id and mobile in one pass
        $extIds  = array_filter(array_column($rows, 'external_id'));
        $mobiles = array_filter(array_column($rows, 'mobile'));

        $existingByExtId = DB::table('clients')
            ->whereIn('external_id', $extIds)
            ->pluck('id', 'external_id')
            ->toArray();

        $existingByMobile = DB::table('clients')
            ->whereIn('mobile', $mobiles)
            ->pluck('id', 'mobile')
            ->toArray();

        // Pre-load sacco groups by name
        $groupsByName = SaccoGroup::all()->keyBy(fn($g) => strtolower(trim($g->name)));

        $now       = now()->toDateTimeString();
        $today     = date('Y-m-d');
        $userId    = Auth::id();
        $chunkSize = 200;
        $toInsert  = [];
        $toUpdate  = [];
        $groupEnrollments = []; // [client_id => sacco_group_id]

        foreach ($rows as $i => $row) {
            try {
                $firstName = trim($row['first_name'] ?? '');
                if (!$firstName) {
                    $errors[] = "Row " . ($i + 2) . ": missing first_name";
                    continue;
                }

                $ctKey     = strtolower(trim($row['client_type'] ?? 'individual'));
                $ct        = $clientTypes->get($ctKey) ?? $defaultClientType;
                $ctId      = $ct?->id;
                $isOrg     = $ct && !in_array(strtolower($ct->name), ['individual', 'customer']);

                $extId     = trim($row['external_id'] ?? '');
                $mobile    = trim($row['mobile'] ?? '');
                $lastName  = $isOrg ? '' : trim($row['last_name'] ?? 'Member');

                $record = [
                    'client_type_id'  => $ctId,
                    'first_name'      => $firstName,
                    'last_name'       => $lastName,
                    'mobile'          => $mobile ?: null,
                    'email'           => trim($row['email'] ?? '') ?: null,
                    'external_id'     => $extId ?: null,
                    'gender'          => $row['gender'] ?? 'unspecified',
                    'branch_id'       => $row['branch_id'] ?? 1,
                    'dob'             => $row['dob'] ?: null,
                    'address'         => trim($row['address'] ?? '') ?: null,
                    'zip'             => trim($row['postal_code'] ?? '') ?: null,
                    'landing_site'    => trim($row['landing_site'] ?? '') ?: null,
                    'years_in_business' => $row['years_in_business'] ?? null,
                    'updated_at'      => $now,
                ];

                if (!$isOrg) {
                    $record['id_number']          = trim($row['id_number'] ?? '') ?: null;
                    $record['passport_number']    = trim($row['passport_number'] ?? '') ?: null;
                    $record['alternative_mobile'] = trim($row['alternative_mobile'] ?? '') ?: null;
                    $record['employment_status']  = trim($row['employment_status'] ?? '') ?: null;
                    $record['employer']           = trim($row['employer_name'] ?? '') ?: null;
                    $record['workplace_address']  = trim($row['workplace_address'] ?? '') ?: null;
                    $record['employer_phone']     = trim($row['employer_phone'] ?? '') ?: null;
                    $record['occupation']         = trim($row['occupation'] ?? '') ?: null;
                } else {
                    $record['registration_number'] = trim($row['registration_number'] ?? '') ?: null;
                    $record['registration_date']   = trim($row['registration_date'] ?? '') ?: null;
                    $record['kra_pin']             = trim($row['kra_pin'] ?? '') ?: null;
                    $record['alternative_mobile']  = trim($row['alternative_mobile'] ?? '') ?: null;
                    $record['nature_of_business']  = trim($row['nature_of_business'] ?? '') ?: null;
                    $record['business_category']   = trim($row['business_category'] ?? '') ?: null;
                    $record['membership_number']   = trim($row['membership_number'] ?? '') ?: null;
                    $record['date_of_admission']   = trim($row['date_of_admission'] ?? '') ?: null;
                }

                // Check if existing
                $existingId = null;
                if ($extId && isset($existingByExtId[$extId])) {
                    $existingId = $existingByExtId[$extId];
                } elseif ($mobile && isset($existingByMobile[$mobile])) {
                    $existingId = $existingByMobile[$mobile];
                }

                if ($existingId) {
                    $toUpdate[] = ['id' => $existingId, 'data' => $record];
                } else {
                    $record['created_by_id']  = $userId;
                    $record['status']         = 'pending';
                    $record['created_date']   = $today;
                    $record['created_at']     = $now;
                    $record['import_batch_id']= $batchId;
                    $toInsert[]               = $record;
                }

                // Group enrollment
                $groupName = strtolower(trim($row['bmu_name'] ?? $row['sacco_group_id'] ?? ''));
                if ($groupName && isset($groupsByName[$groupName])) {
                    $groupEnrollments[] = [
                        'group_id'   => $groupsByName[$groupName]->id,
                        'ext_id'     => $extId,
                        'mobile'     => $mobile,
                        'existing_id'=> $existingId,
                    ];
                }

                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row " . ($i + 2) . ": " . $e->getMessage();
            }
        }

        // ── Bulk insert new clients in chunks ─────────────────────────────────
        foreach (array_chunk($toInsert, $chunkSize) as $chunk) {
            DB::table('clients')->insert($chunk);
        }

        // ── Bulk update existing clients ──────────────────────────────────────
        $updatedCount = count($toUpdate);
        foreach (array_chunk($toUpdate, $chunkSize) as $chunk) {
            foreach ($chunk as $item) {
                DB::table('clients')->where('id', $item['id'])->update($item['data']);
            }
        }

        // ── Group enrollments ─────────────────────────────────────────────────
        if (!empty($groupEnrollments)) {
            // Re-fetch inserted clients by external_id to get their IDs
            $insertedExtIds = array_filter(array_column($toInsert, 'external_id'));
            $newClients = [];
            if ($insertedExtIds) {
                $newClients = DB::table('clients')
                    ->whereIn('external_id', $insertedExtIds)
                    ->pluck('id', 'external_id')
                    ->toArray();
            }

            $enrollInsert = [];
            foreach ($groupEnrollments as $en) {
                $cid = $en['existing_id'];
                if (!$cid && $en['ext_id'] && isset($newClients[$en['ext_id']])) {
                    $cid = $newClients[$en['ext_id']];
                }
                if (!$cid) continue;

                $enrollInsert[] = [
                    'sacco_group_id' => $en['group_id'],
                    'client_id'      => $cid,
                    'role'           => 'member',
                    // joined_date is NOT NULL with no default and is the sort key
                    // GroupService::orderMembers() uses for 'fixed' rotation.
                    // Omitting it made MySQL write '0000-00-00', tying every
                    // imported member on the same date and making the
                    // merry-go-round payout order arbitrary.
                    'joined_date'    => $today,
                    'shares_held'    => 0,
                    'status'         => 'active',
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            // Insert only those not already members, honouring group capacity.
            // Bypassing GroupService::addMember() for bulk speed, so the
            // max_members check it performs has to be replicated here.
            $capacity = [];
            $touchedGroups = [];
            $skippedOverCapacity = 0;

            foreach (array_chunk($enrollInsert, $chunkSize) as $chunk) {
                foreach ($chunk as $en) {
                    $gid = $en['sacco_group_id'];

                    if (!isset($capacity[$gid])) {
                        $group = $groupsByName->first(fn($g) => (int) $g->id === (int) $gid);
                        $capacity[$gid] = [
                            'max'  => $group ? (int) $group->max_members : 0,
                            'type' => $group->type ?? null,
                            'used' => DB::table('sacco_group_members')
                                ->where('sacco_group_id', $gid)
                                ->whereIn('status', ['active', 'suspended'])
                                ->count(),
                        ];
                    }

                    $exists = DB::table('sacco_group_members')
                        ->where('sacco_group_id', $gid)
                        ->where('client_id', $en['client_id'])
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    // max_members of 0/null means unlimited.
                    if ($capacity[$gid]['max'] > 0 && $capacity[$gid]['used'] >= $capacity[$gid]['max']) {
                        $skippedOverCapacity++;
                        $errors[] = "Client id {$en['client_id']}: group is full (max {$capacity[$gid]['max']} members).";
                        continue;
                    }

                    DB::table('sacco_group_members')->insert($en);
                    $capacity[$gid]['used']++;
                    $touchedGroups[$gid] = $capacity[$gid]['type'];
                }
            }

            // A merry-go-round group that gained members has a stale rotation:
            // the cycle sequence was built from the old membership. Rebuild the
            // upcoming schedule so the new members actually get a turn.
            foreach (array_keys($touchedGroups) as $gid) {
                if (($touchedGroups[$gid] ?? null) !== 'merry_go_round') {
                    continue;
                }
                try {
                    $group = SaccoGroup::find($gid);
                    if ($group) {
                        app(GroupService::class)->generateMerryGoRoundSchedule($group, Carbon::today(), $userId);
                    }
                } catch (\Throwable $e) {
                    // A group with no active members throws by design; that is not
                    // an import failure, so record and carry on.
                    $errors[] = "Group {$gid}: rotation schedule not rebuilt ({$e->getMessage()}).";
                }
            }
        }

        $newCount = count($toInsert);
        $errCount = count($errors);

        // ── Log the import batch ──────────────────────────────────────────────
        DB::table('client_import_batches')->insert([
            'batch_id'    => $batchId,
            'filename'    => $filename,
            'import_type' => 'members',
            'total_rows'  => $total,
            'imported'    => $newCount,
            'updated'     => $updatedCount,
            'errors'      => $errCount,
            'created_by_id' => $userId,
            'created_at'  => $now,
            'updated_at'  => $now,
        ]);

        return [
            'imported'   => $imported,
            'new'        => $newCount,
            'updated'    => $updatedCount,
            'total'      => $total,
            'errors'     => $errors,
            'batch_id'   => $batchId,
            'reconciliation' => [
                'success_rate' => $total > 0 ? round(($imported / $total) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Delete all clients that were created in a specific import batch.
     * Only clients with no loans/savings are deleted; others are skipped.
     */
    public function unseedBatch(string $batchId): array
    {
        $batch = DB::table('client_import_batches')->where('batch_id', $batchId)->first();
        if (!$batch) {
            return ['error' => 'Batch not found.', 'deleted' => 0, 'skipped' => 0];
        }

        $clientIds = DB::table('clients')
            ->where('import_batch_id', $batchId)
            ->pluck('id')
            ->toArray();

        if (empty($clientIds)) {
            return ['error' => null, 'deleted' => 0, 'skipped' => 0,
                    'message' => 'No clients found for this batch (may have already been removed).'];
        }

        $deleted = 0;
        $skipped = 0;
        $skippedReasons = [];

        foreach (array_chunk($clientIds, 200) as $chunk) {
            foreach ($chunk as $cid) {
                // Check for active loans
                $hasLoans = DB::table('loan_accounts')
                    ->where('client_id', $cid)
                    ->whereNotIn('status', ['declined', 'closed', 'rejected'])
                    ->exists();
                if ($hasLoans) {
                    $skipped++;
                    continue;
                }

                // Remove group memberships first
                DB::table('sacco_group_members')->where('client_id', $cid)->delete();
                // Delete client
                DB::table('clients')->where('id', $cid)->delete();
                $deleted++;
            }
        }

        // Remove batch log if fully deleted
        if ($skipped === 0) {
            DB::table('client_import_batches')->where('batch_id', $batchId)->delete();
        } else {
            DB::table('client_import_batches')
                ->where('batch_id', $batchId)
                ->update(['imported' => DB::raw('imported - ' . $deleted), 'updated_at' => now()]);
        }

        return [
            'error'   => null,
            'deleted' => $deleted,
            'skipped' => $skipped,
            'batch'   => $batch,
            'message' => "Deleted {$deleted} clients." . ($skipped ? " Skipped {$skipped} (have active loans)." : ''),
        ];
    }

    protected function parseExcel(string $path, string $extension = 'xlsx'): array
    {
        $readerType = strtolower($extension) === 'xls'
            ? \Maatwebsite\Excel\Excel::XLS
            : \Maatwebsite\Excel\Excel::XLSX;

        $data = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array) {}
        }, $path, null, $readerType);

        $rows = [];
        if (!empty($data) && count($data[0]) > 0) {
            $sheet = $data[0];
            $headers = array_map('strtolower', array_map('trim', array_shift($sheet) ?: []));
            foreach ($sheet as $row) {
                // Filter out empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                // Pad or slice row to match headers count exactly
                $row = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                $rows[] = array_combine($headers, $row);
            }
        }
        return $rows;
    }

    protected function parseCsv(string $path): array
    {
        $rows = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = array_map('strtolower', array_map('trim', fgetcsv($handle) ?: []));
            while (($data = fgetcsv($handle)) !== false) {
                // Filter out empty lines
                if (empty(array_filter($data))) {
                    continue;
                }
                if (count($data) === count($headers)) {
                    $rows[] = array_combine($headers, $data);
                } else {
                    // Try to fix mismatches by padding/slicing
                    $data = array_slice(array_pad($data, count($headers), null), 0, count($headers));
                    $rows[] = array_combine($headers, $data);
                }
            }
            fclose($handle);
        }
        return $rows;
    }
}

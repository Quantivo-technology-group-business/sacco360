<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GlobalSearchService
{
    public function search(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $like = '%' . $query . '%';
        $results = [];

        $hasMemberNumber = Schema::hasTable('clients') && Schema::hasColumn('clients', 'member_number');

        $clients = DB::table('clients')
            ->leftJoin('client_identification', 'client_identification.client_id', '=', 'clients.id')
            ->where(function ($q) use ($like, $query, $hasMemberNumber) {
                $q->where('clients.first_name', 'like', $like)
                    ->orWhere('clients.last_name', 'like', $like)
                    ->orWhere('clients.mobile', 'like', $like)
                    ->orWhere('clients.email', 'like', $like)
                    ->orWhere('clients.external_id', 'like', $like)
                    ->orWhere('clients.account_number', 'like', $like);
                if ($hasMemberNumber) {
                    $q->orWhere('clients.member_number', 'like', $like);
                }
                $q->orWhere('client_identification.identification_value', 'like', $like)
                    ->orWhere('clients.id', $query);
            })
            ->selectRaw("clients.id, CONCAT(clients.first_name,' ',clients.last_name) as name, clients.mobile, clients.status, 'client' as match_type")
            ->groupBy('clients.id', 'clients.first_name', 'clients.last_name', 'clients.mobile', 'clients.status')
            ->limit($limit)
            ->get();

        $earmarkService = class_exists(\Modules\Sacco\Services\EarmarkService::class)
            ? new \Modules\Sacco\Services\EarmarkService()
            : null;

        foreach ($clients as $c) {
            $earmarks = $earmarkService ? $earmarkService->getActiveForClient((int)$c->id, true) : [];
            $results[] = [
                'type' => 'client',
                'id' => $c->id,
                'label' => $c->name,
                'detail' => $c->mobile,
                'url' => url('client/' . $c->id . '/show'),
                'earmarks' => $earmarks,
                'has_earmarks' => !empty($earmarks),
            ];
        }

        $savings = DB::table('savings')
            ->join('clients', 'clients.id', '=', 'savings.client_id')
            ->where('savings.account_number', 'like', $like)
            ->orWhere('savings.id', $query)
            ->selectRaw("savings.id, savings.account_number, CONCAT(clients.first_name,' ',clients.last_name) as client_name, clients.id as client_id, 'savings' as match_type")
            ->limit($limit)
            ->get();

        foreach ($savings as $s) {
            $results[] = [
                'type' => 'savings',
                'id' => $s->id,
                'label' => 'Savings #' . ($s->account_number ?: $s->id),
                'detail' => $s->client_name,
                'url' => url('savings/' . $s->id . '/show'),
            ];
        }

        $loans = DB::table('loans')
            ->join('clients', 'clients.id', '=', 'loans.client_id')
            ->where('loans.id', $query)
            ->selectRaw("loans.id, CONCAT(clients.first_name,' ',clients.last_name) as client_name, clients.id as client_id, 'loan' as match_type")
            ->limit($limit)
            ->get();

        foreach ($loans as $l) {
            $results[] = [
                'type' => 'loan',
                'id' => $l->id,
                'label' => 'Loan #' . $l->id,
                'detail' => $l->client_name,
                'url' => url('loan/' . $l->id . '/show'),
            ];
        }

        $shares = DB::table('shares')
            ->join('clients', 'clients.id', '=', 'shares.client_id')
            ->where('shares.id', $query)
            ->selectRaw("shares.id, CONCAT(clients.first_name,' ',clients.last_name) as client_name, clients.id as client_id, 'share' as match_type")
            ->limit($limit)
            ->get();

        foreach ($shares as $sh) {
            $results[] = [
                'type' => 'share',
                'id' => $sh->id,
                'label' => 'Share #' . $sh->id,
                'detail' => $sh->client_name,
                'url' => url('share/' . $sh->id . '/show'),
            ];
        }

        return array_slice($results, 0, $limit);
    }
}

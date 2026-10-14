<?php

namespace Modules\Sacco\Services;

use Modules\Client\Entities\Client;

class MemberNumberService
{
    public function assign(Client $client): string
    {
        if (!empty($client->member_number)) {
            return $client->member_number;
        }

        if (!empty($client->external_id)) {
            $client->member_number = $client->external_id;
            $client->save();
            return $client->member_number;
        }

        $prefix = config('sacco.member_number_prefix', 'M');
        $number = $prefix . str_pad((string)$client->id, 6, '0', STR_PAD_LEFT);

        while (Client::where('member_number', $number)->where('id', '!=', $client->id)->exists()) {
            $number = $prefix . str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        }

        $client->member_number = $number;
        $client->save();
        return $number;
    }

    public function resolve(string $identifier): ?Client
    {
        return Client::where('member_number', $identifier)
            ->orWhere('external_id', $identifier)
            ->orWhere('account_number', $identifier)
            ->orWhere('id', is_numeric($identifier) ? (int)$identifier : 0)
            ->first();
    }
}

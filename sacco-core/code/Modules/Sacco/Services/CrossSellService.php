<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoCrossSellCampaign;

class CrossSellService
{
    public function getEligibleMembers(?int $campaignId = null): array
    {
        $campaigns = $campaignId
            ? SaccoCrossSellCampaign::where('id', $campaignId)->where('active', 1)->get()
            : SaccoCrossSellCampaign::where('active', 1)->get();

        $results = [];
        foreach ($campaigns as $campaign) {
            $members = $this->findEligibleForCampaign($campaign);
            $results[] = [
                'campaign' => $campaign,
                'eligible_count' => count($members),
                'members' => $members,
            ];
        }
        return $results;
    }

    public function findEligibleForCampaign(SaccoCrossSellCampaign $campaign): array
    {
        $today = date('Y-m-d');
        if ($campaign->start_date && $campaign->start_date > $today) {
            return [];
        }
        if ($campaign->end_date && $campaign->end_date < $today) {
            return [];
        }

        $wingService = new WingService();
        $clients = Client::whereIn('status', ['active', 'approved'])->get();
        $eligible = [];

        foreach ($clients as $client) {
            $sourceBalance = $this->getWingBalance($client->id, $campaign->source_wing);
            $targetBalance = $this->getWingBalance($client->id, $campaign->target_wing);

            if ($sourceBalance >= $campaign->min_balance && $targetBalance < $campaign->min_balance) {
                $eligible[] = [
                    'client_id' => $client->id,
                    'member_number' => $client->member_number,
                    'name' => trim($client->first_name . ' ' . $client->last_name),
                    'source_balance' => $sourceBalance,
                    'target_balance' => $targetBalance,
                    'message' => $campaign->message,
                ];
            }
        }

        return $eligible;
    }

    protected function getWingBalance(int $clientId, string $wing): float
    {
        if ($wing === 'share') {
            return (new GuarantorLockService())->getFreeShareValue($clientId);
        }
        if ($wing === 'any') {
            return (new WingService())->getSavingsBalanceByWing($clientId, WingService::WING_BOSA)
                + (new WingService())->getSavingsBalanceByWing($clientId, WingService::WING_FOSA);
        }
        return (new WingService())->getSavingsBalanceByWing($clientId, $wing);
    }

    public function suggestForMember(int $clientId): array
    {
        $campaigns = SaccoCrossSellCampaign::where('active', 1)->get();
        $suggestions = [];
        foreach ($campaigns as $campaign) {
            $eligible = $this->findEligibleForCampaign($campaign);
            foreach ($eligible as $member) {
                if ($member['client_id'] === $clientId) {
                    $suggestions[] = [
                        'campaign' => $campaign->name,
                        'target' => $campaign->target_wing,
                        'message' => $campaign->message,
                    ];
                }
            }
        }
        return $suggestions;
    }
}

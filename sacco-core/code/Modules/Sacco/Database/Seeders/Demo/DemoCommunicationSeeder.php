<?php

namespace Modules\Sacco\Database\Seeders\Demo;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoCommunicationSeeder extends DemoSeederBase
{
    public function run()
    {
        if (!self::shouldRun('communication')) {
            return;
        }

        $this->initRuntime();

        if (!Schema::hasTable('communication_campaigns')) {
            return;
        }

        $userId = self::context('adminUserId');
        $branchIds = self::context('branchIds', [1]);
        $memberIds = self::context('memberIds', []);
        $now = self::context('now');
        $today = self::context('today');
        $branchId = $branchIds[0];

        $businessRuleId = DB::table('communication_campaign_business_rules')->value('id');
        $attachmentTypeId = DB::table('communication_campaign_attachment_types')->value('id');

        $sentCampaignId = DB::table('communication_campaigns')->insertGetId([
            'created_by_id' => $userId,
            'communication_campaign_business_rule_id' => $businessRuleId,
            'communication_campaign_attachment_type_id' => $attachmentTypeId,
            'branch_id' => $branchId,
            'name' => self::SEED_PREFIX . ' Monthly Savings Reminder',
            'subject' => 'Reminder: Make your monthly savings contribution',
            'campaign_type' => 'sms',
            'trigger_type' => 'direct',
            'scheduled_date' => $today,
            'active' => 1,
            'status' => 'done',
            'description' => 'SMS reminder sent to active members',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('communication_campaigns')->insert([
            'created_by_id' => $userId,
            'communication_campaign_business_rule_id' => $businessRuleId,
            'communication_campaign_attachment_type_id' => $attachmentTypeId,
            'branch_id' => $branchId,
            'name' => self::SEED_PREFIX . ' AGM Notice Draft',
            'subject' => 'Annual General Meeting Notice',
            'campaign_type' => 'email',
            'trigger_type' => 'schedule',
            'scheduled_date' => date('Y-m-d', strtotime('+14 days')),
            'active' => 0,
            'status' => 'pending',
            'description' => 'Draft email campaign for upcoming AGM',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if (Schema::hasTable('communication_campaign_logs')) {
            foreach (array_slice($memberIds, 0, 25) as $i => $clientId) {
                $mobile = DB::table('clients')->where('id', $clientId)->value('mobile');
                DB::table('communication_campaign_logs')->insert([
                    'created_by_id' => $userId,
                    'client_id' => $clientId,
                    'communication_campaign_id' => $sentCampaignId,
                    'campaign_type' => 'sms',
                    'description' => 'Monthly savings reminder SMS',
                    'send_to' => $mobile,
                    'campaign_name' => self::SEED_PREFIX . ' Monthly Savings Reminder',
                    'status' => $i % 8 === 0 ? 'failed' : 'sent',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}

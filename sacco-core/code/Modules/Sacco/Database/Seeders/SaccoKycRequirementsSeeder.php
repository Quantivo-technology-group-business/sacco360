<?php

namespace Modules\Sacco\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Sacco\Entities\SaccoKycRequirement;

class SaccoKycRequirementsSeeder extends Seeder
{
    public function run()
    {
        $requirements = [
            // Individual/Customer requirements
            ['code' => 'national_id', 'name' => 'National ID', 'category' => 'identity', 'mandatory' => 1, 'requires_iprs' => 1, 'sort_order' => 1, 'description' => 'Kenyan national ID — verified via IPRS/NIDA', 'applies_to' => 'individual'],
            ['code' => 'kra_pin', 'name' => 'KRA PIN', 'category' => 'tax', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 2, 'description' => 'Kenya Revenue Authority PIN certificate', 'applies_to' => 'individual'],
            ['code' => 'photo', 'name' => 'Passport Photo', 'category' => 'biometric', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 3, 'description' => 'Recent passport-size photograph', 'applies_to' => 'individual'],
            ['code' => 'proof_of_address', 'name' => 'Proof of Address', 'category' => 'address', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 4, 'description' => 'Utility bill, lease agreement, or chief\'s letter (≤3 months)', 'applies_to' => 'individual'],
            ['code' => 'signature', 'name' => 'Member Signature', 'category' => 'biometric', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 5, 'description' => 'Captured signature on membership form', 'applies_to' => 'individual'],
            ['code' => 'employment_letter', 'name' => 'Employment / Income Proof', 'category' => 'employment', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 6, 'description' => 'Payslip, employment letter, or business registration', 'applies_to' => 'individual'],
            ['code' => 'passport', 'name' => 'Passport', 'category' => 'identity', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 7, 'description' => 'For non-citizen members', 'applies_to' => 'individual'],
            ['code' => 'next_of_kin_id', 'name' => 'Next of Kin ID', 'category' => 'identity', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 8, 'description' => 'National ID copy of nominated next of kin', 'applies_to' => 'individual'],

            // Organisation requirements
            ['code' => 'cert_of_reg', 'name' => 'Certificate of Registration/Incorporation', 'category' => 'identity', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 9, 'description' => 'Official government registration certificate', 'applies_to' => 'organisation'],
            ['code' => 'org_kra_pin', 'name' => 'Organisation KRA PIN', 'category' => 'tax', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 10, 'description' => 'KRA PIN certificate for the organisation', 'applies_to' => 'organisation'],
            ['code' => 'board_resolution', 'name' => 'Board Resolution', 'category' => 'identity', 'mandatory' => 1, 'requires_iprs' => 0, 'sort_order' => 1, 'description' => 'Signed board resolution authorising the account opening/loans', 'applies_to' => 'organisation'],
            ['code' => 'cr12_form', 'name' => 'CR12 Form', 'category' => 'identity', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 12, 'description' => 'Latest CR12 form listing company directors and shareholders', 'applies_to' => 'organisation'],
            ['code' => 'trading_license', 'name' => 'Trading License', 'category' => 'identity', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 13, 'description' => 'Valid county trading license', 'applies_to' => 'organisation'],
            ['code' => 'constitution_docs', 'name' => 'Constitution / Bylaws', 'category' => 'identity', 'mandatory' => 0, 'requires_iprs' => 0, 'sort_order' => 14, 'description' => 'For Chamas, groups, or Saccos - constitution/bylaws document', 'applies_to' => 'organisation'],
        ];

        foreach ($requirements as $req) {
            SaccoKycRequirement::updateOrCreate(
                ['code' => $req['code']],
                array_merge($req, ['active' => 1])
            );
        }
    }
}

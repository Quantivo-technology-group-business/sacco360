<?php

return [
    'sacco.members' => [
        'filename' => 'members_import_template.csv',
        'headers' => [
            'client_type', 'first_name', 'last_name', 'gender', 'dob', 'id_number', 'passport_number',
            'mobile', 'alternative_mobile', 'email', 'address', 'postal_code', 'landing_site',
            'years_in_business', 'bmu_name', 'branch_id', 'external_id', 'employment_status',
            'employer_name', 'workplace_address', 'employer_phone', 'occupation', 'registration_number',
            'registration_date', 'nature_of_business', 'business_category', 'membership_number',
            'date_of_admission'
        ],
        'sample' => [
            'individual', 'John Doe', 'Customer', 'male', '1990-05-15', '12345678', 'A000123',
            '254712345678', '254787654321', 'john@example.com', 'P.O. Box 123-00100', '00100', 'Kisumu Landing',
            '5', 'Kisumu Sacco Group', '1', 'EXT001', 'employed', 'Kisumu Fisheries', 'Kisumu port 4',
            '0711111111', 'Fisherman', '', '', '', '', '', ''
        ],
        'columns_hint' => 'client_type, first_name, last_name, gender, dob, id_number, passport_number, mobile, alternative_mobile, email, address, postal_code, landing_site, years_in_business, bmu_name, branch_id, external_id, employment_status, employer_name, workplace_address, employer_phone, occupation, registration_number, registration_date, nature_of_business, business_category, membership_number, date_of_admission',
        'notes' => 'client_type: individual (or customer) vs organisation. For organisations, first_name acts as the Organisation Name (leave last_name blank). bmu_name must match a Sacco Group name in the system.',
    ],

    // Individual members only template
    'sacco_members_individual' => [
        'filename' => 'individual_members_template.xlsx',
        'headers' => [
            'client_type', 'first_name', 'last_name', 'gender', 'dob',
            'id_number', 'passport_number', 'mobile', 'alternative_mobile', 'email',
            'address', 'postal_code', 'branch_id', 'external_id', 'bmu_name',
            'employment_status', 'employer_name', 'workplace_address', 'employer_phone', 'occupation',
        ],
        'sample' => [
            'individual', 'Jane', 'Wanjiku', 'female', '1992-03-10',
            '23456789', '', '254712000001', '254787000001', 'jane@example.com',
            'P.O. Box 200-00200', '00200', '1', 'EXT002', 'Kisumu Sacco Group',
            'employed', 'ABC Company', 'Industrial Area', '0722000001', 'Accountant',
        ],
        'notes' => 'Use this template for individual/personal members. client_type must be "individual". bmu_name should match an existing Chama Group name.',
    ],

    // Organisation members only template
    'sacco_members_organisation' => [
        'filename' => 'organisation_members_template.xlsx',
        'headers' => [
            'client_type', 'first_name', 'mobile', 'alternative_mobile', 'email',
            'address', 'postal_code', 'branch_id', 'external_id', 'bmu_name',
            'registration_number', 'registration_date', 'kra_pin',
            'nature_of_business', 'business_category', 'membership_number', 'date_of_admission',
        ],
        'sample' => [
            'organisation', 'Wanjiku Traders Ltd', '254712000002', '254787000002', 'info@wanjiku.co.ke',
            'P.O. Box 300-00300', '00300', '1', 'EXT003', 'Kisumu Sacco Group',
            'CPR/2020/12345', '2020-01-15', 'A001234567B',
            'Retail Trade', 'SME', 'MBR0001', '2020-02-01',
        ],
        'notes' => 'Use this template for organisation/company members. client_type must be "organisation". first_name holds the Organisation Name.',
    ],
    'sacco.checkoff' => [
        'filename' => 'checkoff_template.csv',
        'headers' => ['employee_id', 'employee_name', 'deduction_type', 'loan_id', 'amount'],
        'sample' => ['EMP001', 'John Doe', 'loan', 'LN001', '5000.00'],
        'columns_hint' => 'employee_id, employee_name, deduction_type, loan_id, amount',
        'notes' => 'deduction_type: loan | bosa_savings | share',
    ],
    'kyc.document' => [
        'accept' => '.jpg,.jpeg,.png,.pdf',
        'notes' => 'Upload a clear scan or photo of the document.',
    ],
    'photo.profile' => [
        'accept' => 'image/*',
        'notes' => 'Recommended: square image, at least 200x200px.',
    ],
    'document.general' => [
        'accept' => '.jpg,.jpeg,.png,.pdf,.doc,.docx',
        'notes' => 'Upload supporting documents in a readable format.',
    ],
    'cheque.image' => [
        'accept' => 'image/*',
        'notes' => 'Upload a clear photo of the front of the cheque.',
    ],
    'module.zip' => [
        'accept' => '.zip',
        'notes' => 'Upload a ZIP archive containing the package files.',
    ],
];

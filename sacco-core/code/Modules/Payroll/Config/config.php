<?php

return [
    'name' => 'Payroll',

    'statutory' => [
        'country' => 'KE',
        'personal_relief' => 2400,
        'paye_bands' => [
            ['limit' => 24000, 'rate' => 0.10],
            ['limit' => 8333, 'rate' => 0.25],
            ['limit' => 467667, 'rate' => 0.30],
            ['limit' => 300000, 'rate' => 0.325],
            ['limit' => PHP_INT_MAX, 'rate' => 0.35],
        ],
        'nssf' => [
            'lower_limit' => 7000,
            'upper_limit' => 36000,
            'rate' => 0.06,
        ],
        'shif_rate' => 0.0275,
        'housing_levy_rate' => 0.015,
    ],

    'item_categories' => [
        'basic' => 'Basic Pay',
        'allowance' => 'Allowance',
        'statutory' => 'Statutory Deduction',
        'voluntary' => 'Voluntary Deduction',
        'sacco' => 'SACCO Deduction',
        'loan' => 'Loan Deduction',
        'other' => 'Other',
    ],
];

<?php
return [
    ['name'=>'Investments',          'is_parent'=>1,'module'=>'Investment','slug'=>'investment',            'parent_slug'=>'',            'url'=>'investment',                  'icon'=>'fas fa-chart-line','order'=>13,'permissions'=>''],
    ['name'=>'Investment Plans',     'is_parent'=>0,'module'=>'Investment','slug'=>'investment_plans',      'parent_slug'=>'investment',  'url'=>'investment/plan',             'icon'=>'far fa-circle',    'order'=>1, 'permissions'=>'investment.plans.index'],
    ['name'=>'All Investments',      'is_parent'=>0,'module'=>'Investment','slug'=>'member_investments',    'parent_slug'=>'investment',  'url'=>'investment',                  'icon'=>'far fa-circle',    'order'=>2, 'permissions'=>'investment.investments.index'],
    ['name'=>'New Investment',       'is_parent'=>0,'module'=>'Investment','slug'=>'investment_create',     'parent_slug'=>'investment',  'url'=>'investment/create',           'icon'=>'far fa-circle',    'order'=>3, 'permissions'=>'investment.investments.create'],
    ['name'=>'Portfolio Report',     'is_parent'=>0,'module'=>'Investment','slug'=>'investment_portfolio',  'parent_slug'=>'investment',  'url'=>'investment/report/portfolio', 'icon'=>'far fa-circle',    'order'=>4, 'permissions'=>'investment.reports.index'],
    ['name'=>'Maturing Investments', 'is_parent'=>0,'module'=>'Investment','slug'=>'investment_maturities', 'parent_slug'=>'investment', 'url'=>'investment/report/maturities','icon'=>'far fa-circle',    'order'=>5, 'permissions'=>'investment.reports.index'],
    ['name'=>'Profit Due',           'is_parent'=>0,'module'=>'Investment','slug'=>'investment_profit_due', 'parent_slug'=>'investment', 'url'=>'investment/report/profit-due','icon'=>'far fa-circle',    'order'=>6, 'permissions'=>'investment.reports.index'],
];

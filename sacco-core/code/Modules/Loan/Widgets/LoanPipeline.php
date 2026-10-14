<?php

namespace Modules\Loan\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanApplication;

class LoanPipeline extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $pipeline = [
            'pending' => Loan::whereIn('status', ['pending', 'submitted'])->count(),
            'approved' => Loan::where('status', 'approved')->count(),
            'active' => Loan::where('status', 'active')->count(),
            'closed' => Loan::where('status', 'closed')->count(),
            'written_off' => Loan::where('status', 'written_off')->count(),
            'applications' => LoanApplication::count(),
            'pending_amount' => Loan::whereIn('status', ['pending', 'submitted', 'approved'])->sum('applied_amount'),
        ];

        return theme_view('loan::widgets.loan_pipeline', [
            'config' => $this->config,
            'pipeline' => $pipeline,
        ]);
    }
}

<?php

namespace Modules\Sacco\Widgets;

use Arrilot\Widgets\AbstractWidget;
use Modules\Sacco\Services\PrudentialRatioService;

class CompliancePrudentialWidget extends AbstractWidget
{
    protected $config = [];

    public function run()
    {
        $ratios = (new PrudentialRatioService())->calculateAll(date('Y-m-d'));

        return theme_view('sacco::widgets.compliance_prudential', [
            'config' => $this->config,
            'ratios' => $ratios,
        ]);
    }
}

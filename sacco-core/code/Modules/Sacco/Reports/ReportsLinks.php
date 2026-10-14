<?php

namespace Modules\Sacco\Reports;

class ReportsLinks
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function get_links()
    {
        return [
            'links' => [
                ['name' => 'SASRA Regulatory Reports', 'url' => 'sacco/regulatory', 'permission' => 'sacco.regulatory.index'],
                ['name' => 'Compliance Dashboard', 'url' => 'sacco/compliance', 'permission' => 'sacco.compliance.dashboard'],
                ['name' => 'CAELS Report', 'url' => 'sacco/regulatory/caels', 'permission' => 'sacco.regulatory.caels'],
            ],
        ];
    }
}

<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CaelsReportService
{
    public function generateComparativeReport(string $asOfDate, int $periods = 4): array
    {
        $trends = [];
        $current = strtotime($asOfDate);

        for ($i = 0; $i < $periods; $i++) {
            $date = date('Y-m-t', strtotime("-{$i} months", $current));
            $caels = new CaelsClassificationService();
            $data = $caels->classifyPortfolio($date);
            $trends[] = [
                'period' => $date,
                'npl_ratio' => $data['npl_ratio'],
                'total_provision' => $data['total_provision'],
                'provision_coverage' => $data['provision_coverage'],
                'by_classification' => $data['by_classification'],
            ];
        }

        return [
            'trends' => array_reverse($trends),
            'sectoral' => $this->sectoralBreakdown($asOfDate),
            'risk_profile' => $this->riskProfile($asOfDate),
            'as_of' => $asOfDate,
        ];
    }

    protected function sectoralBreakdown(string $asOfDate): array
    {
        $caels = new CaelsClassificationService();
        $portfolio = $caels->classifyPortfolio($asOfDate);
        $sectors = [];

        foreach ($portfolio['classifications'] as $item) {
            $sector = $item['sector_code'] ?? 'unclassified';
            if (!isset($sectors[$sector])) {
                $sectors[$sector] = ['outstanding' => 0, 'provision' => 0, 'count' => 0, 'npl' => 0];
            }
            $sectors[$sector]['outstanding'] += $item['outstanding'];
            $sectors[$sector]['provision'] += $item['provision_amount'];
            $sectors[$sector]['count']++;
            if ($item['days_overdue'] > 30) {
                $sectors[$sector]['npl'] += $item['outstanding'];
            }
        }

        return $sectors;
    }

    protected function riskProfile(string $asOfDate): string
    {
        $caels = new CaelsClassificationService();
        $data = $caels->classifyPortfolio($asOfDate);
        $npl = $data['npl_ratio'];

        if ($npl < 5) {
            return 'Low';
        }
        if ($npl < 10) {
            return 'Moderate';
        }
        if ($npl < 20) {
            return 'High';
        }

        return 'Critical';
    }
}

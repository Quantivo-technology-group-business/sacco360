<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Sacco\Entities\SaccoAuditorAppointment;
use Modules\Sacco\Entities\SaccoBoardResolution;
use Modules\Sacco\Entities\SaccoComplianceCalendar;
use Modules\Sacco\Entities\SaccoFitProperRecord;
use Modules\Sacco\Entities\SaccoInspectionAction;
use Modules\Sacco\Entities\SaccoLicenseRenewal;

class GovernanceService
{
    protected array $formSchedule = [
        ['code' => '1/001', 'name' => 'Capital Adequacy Return', 'frequency' => 'monthly'],
        ['code' => '1/002', 'name' => 'Liquidity Statement', 'frequency' => 'monthly'],
        ['code' => '1/003', 'name' => 'Statement of Deposit Return', 'frequency' => 'monthly'],
        ['code' => '1/004', 'name' => 'Risk Classification & Provisioning', 'frequency' => 'quarterly'],
        ['code' => '1/005', 'name' => 'Investment Return', 'frequency' => 'quarterly'],
        ['code' => '1/006', 'name' => 'Statement of Financial Position', 'frequency' => 'quarterly'],
        ['code' => '1/007', 'name' => 'Statement of Comprehensive Income', 'frequency' => 'quarterly'],
        ['code' => '1/008', 'name' => 'Other Disclosures', 'frequency' => 'adhoc'],
        ['code' => '4B', 'name' => 'Sectorial Lending Report', 'frequency' => 'adhoc'],
    ];

    public function generateComplianceCalendar(int $year, int $month): int
    {
        $count = 0;
        foreach ($this->formSchedule as $form) {
            if ($form['frequency'] === 'monthly') {
                $dueDate = date('Y-m-15', strtotime("{$year}-{$month}-01 +1 month"));
                $this->upsertCalendarEntry($form, $dueDate, $year, $month);
                $count++;
            }
            if ($form['frequency'] === 'quarterly' && in_array($month, [3, 6, 9, 12], true)) {
                $dueDate = date('Y-m-t', strtotime("{$year}-{$month}-01"));
                $this->upsertCalendarEntry($form, $dueDate, $year, $month);
                $count++;
            }
        }

        return $count;
    }

    public function processReminders(): int
    {
        $count = 0;
        $days = array_map('intval', explode(',', config('sacco.compliance_reminder_days', '7,14,30')));

        foreach ($days as $dayOffset) {
            $targetDate = date('Y-m-d', strtotime("+{$dayOffset} days"));
            $entries = SaccoComplianceCalendar::where('due_date', $targetDate)
                ->whereIn('status', ['upcoming', 'due'])
                ->get();
            $count += $entries->count();
        }

        $licenseReminders = SaccoLicenseRenewal::where('status', 'active')
            ->where('expiry_date', '<=', date('Y-m-d', strtotime('+90 days')))
            ->where('expiry_date', '>', date('Y-m-d'))
            ->get();

        foreach ($licenseReminders as $license) {
            SaccoLicenseRenewal::where('id', $license->id)->update(['status' => 'pending_renewal']);
            $count++;
        }

        $overdueActions = SaccoInspectionAction::where('deadline', '<', date('Y-m-d'))
            ->whereIn('status', ['open', 'in_progress'])
            ->update(['status' => 'overdue']);

        $count += $overdueActions;

        $overdueAuditors = SaccoAuditorAppointment::where('sasra_submission_deadline', '<', date('Y-m-d'))
            ->where('status', 'appointed')
            ->update(['status' => 'overdue']);

        $count += $overdueAuditors;

        return $count;
    }

    public function markSubmitted(string $formCode, string $dueDate, int $userId): void
    {
        SaccoComplianceCalendar::where('form_code', $formCode)
            ->where('due_date', $dueDate)
            ->update([
                'status' => 'submitted',
                'submitted_date' => date('Y-m-d'),
                'submitted_by_id' => $userId,
            ]);
    }

    protected function upsertCalendarEntry(array $form, string $dueDate, int $year, int $month): void
    {
        $status = $dueDate < date('Y-m-d') ? 'overdue' : ($dueDate <= date('Y-m-d', strtotime('+7 days')) ? 'due' : 'upcoming');

        SaccoComplianceCalendar::updateOrCreate(
            ['form_code' => $form['code'], 'due_date' => $dueDate],
            [
                'form_name' => $form['name'],
                'frequency' => $form['frequency'],
                'period_start' => date('Y-m-01', strtotime("{$year}-{$month}-01")),
                'period_end' => date('Y-m-t', strtotime("{$year}-{$month}-01")),
                'status' => $status,
            ]
        );
    }
}

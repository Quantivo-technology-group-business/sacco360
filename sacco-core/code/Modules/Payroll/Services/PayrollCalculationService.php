<?php

namespace Modules\Payroll\Services;

use Illuminate\Support\Facades\DB;
use Modules\Payroll\Entities\Payroll;
use Modules\Payroll\Entities\PayrollEmployee;
use Modules\Payroll\Entities\PayrollItemMeta;
use Modules\Payroll\Entities\PayrollRun;
use Modules\User\Entities\User;

class PayrollCalculationService
{
    public function calculatePaye(float $taxableIncome): float
    {
        $config = config('payroll.statutory');
        $remaining = max(0, $taxableIncome);
        $tax = 0;

        foreach ($config['paye_bands'] as $band) {
            if ($remaining <= 0) {
                break;
            }
            $taxable = min($remaining, $band['limit']);
            $tax += $taxable * $band['rate'];
            $remaining -= $taxable;
        }

        return max(0, round($tax - ($config['personal_relief'] ?? 0), 2));
    }

    public function calculateNssf(float $grossPay): array
    {
        $config = config('payroll.statutory.nssf');
        $pensionable = min($grossPay, $config['upper_limit']);
        $tier1Base = min($pensionable, $config['lower_limit']);
        $tier2Base = max(0, min($pensionable, $config['upper_limit']) - $config['lower_limit']);
        $employee = round(($tier1Base + $tier2Base) * $config['rate'], 2);

        return [
            'employee' => $employee,
            'employer' => $employee,
        ];
    }

    public function calculateShif(float $grossPay): float
    {
        return round($grossPay * config('payroll.statutory.shif_rate', 0.0275), 2);
    }

    public function calculateHousingLevy(float $grossPay): array
    {
        $rate = config('payroll.statutory.housing_levy_rate', 0.015);
        $amount = round($grossPay * $rate, 2);

        return ['employee' => $amount, 'employer' => $amount];
    }

    public function computeLineAmount(array $item, float $baseAmount): float
    {
        if (($item['amount_type'] ?? 'fixed') === 'percentage') {
            return round($baseAmount * (float)($item['amount'] ?? 0) / 100, 2);
        }

        return round((float)($item['amount'] ?? 0), 2);
    }

    public function calculateFromItems(array $items, float $basicSalary): array
    {
        $allowances = [];
        $deductions = [];
        $totalAllowances = 0;
        $taxableAllowances = 0;
        $nonStatutoryDeductions = 0;

        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'allowance') {
                $computed = $this->computeLineAmount($item, $basicSalary);
                $item['computed_amount'] = $computed;
                $allowances[] = $item;
                $totalAllowances += $computed;
                if (!empty($item['is_taxable'])) {
                    $taxableAllowances += $computed;
                }
            }
        }

        $grossPay = $basicSalary + $totalAllowances;
        $taxablePay = $basicSalary + $taxableAllowances;

        $nssf = $this->calculateNssf($grossPay);
        $shif = $this->calculateShif($grossPay);
        $housing = $this->calculateHousingLevy($grossPay);

        $statutoryItems = [
            ['name' => 'PAYE', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => 0, 'category' => 'statutory', 'is_taxable' => 0, 'is_statutory' => 1],
            ['name' => 'NSSF (Employee)', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => $nssf['employee'], 'category' => 'statutory', 'is_taxable' => 0, 'is_statutory' => 1],
            ['name' => 'SHIF', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => $shif, 'category' => 'statutory', 'is_taxable' => 0, 'is_statutory' => 1],
            ['name' => 'Housing Levy', 'type' => 'deduction', 'amount_type' => 'fixed', 'amount' => $housing['employee'], 'category' => 'statutory', 'is_taxable' => 0, 'is_statutory' => 1],
        ];

        $paye = $this->calculatePaye($taxablePay - $nssf['employee'] - $shif - $housing['employee']);
        $statutoryItems[0]['amount'] = $paye;
        $statutoryItems[0]['computed_amount'] = $paye;

        foreach ($statutoryItems as $statutory) {
            if ($statutory['name'] !== 'PAYE') {
                $statutory['computed_amount'] = $statutory['amount'];
            }
            $deductions[] = $statutory;
        }

        $statutoryTotal = $paye + $nssf['employee'] + $shif + $housing['employee'];

        foreach ($items as $item) {
            if (($item['type'] ?? '') === 'deduction' && empty($item['is_statutory'])) {
                $computed = $this->computeLineAmount($item, $grossPay);
                $item['computed_amount'] = $computed;
                $deductions[] = $item;
                $nonStatutoryDeductions += $computed;
            }
        }

        $totalDeductions = $statutoryTotal + $nonStatutoryDeductions;
        $netPay = $grossPay - $totalDeductions;

        return [
            'basic_salary' => round($basicSalary, 2),
            'gross_pay' => round($grossPay, 2),
            'taxable_pay' => round($taxablePay, 2),
            'net_pay' => round($netPay, 2),
            'paye' => $paye,
            'nssf_employee' => $nssf['employee'],
            'nssf_employer' => $nssf['employer'],
            'shif' => $shif,
            'housing_levy_employee' => $housing['employee'],
            'housing_levy_employer' => $housing['employer'],
            'total_allowances' => round($totalAllowances, 2),
            'total_deductions' => round($totalDeductions, 2),
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross_amount' => round($netPay, 2),
        ];
    }

    public function savePayrollItems(int $payrollId, array $allowances, array $deductions): void
    {
        PayrollItemMeta::where('payroll_id', $payrollId)->delete();

        foreach (array_merge($allowances, $deductions) as $item) {
            $meta = new PayrollItemMeta();
            $meta->payroll_id = $payrollId;
            $meta->payroll_item_id = $item['id'] ?? $item['payroll_item_id'] ?? null;
            $meta->name = $item['name'] ?? '';
            $meta->type = $item['type'] ?? 'deduction';
            $meta->amount_type = $item['amount_type'] ?? 'fixed';
            $meta->amount = $item['amount'] ?? 0;
            $meta->computed_amount = $item['computed_amount'] ?? $item['amount'] ?? 0;
            $meta->category = $item['category'] ?? 'other';
            $meta->is_taxable = $item['is_taxable'] ?? 1;
            $meta->save();
        }
    }

    public function applyCalculationToPayroll(Payroll $payroll, array $calculation): Payroll
    {
        $payroll->basic_salary = $calculation['basic_salary'];
        $payroll->gross_pay = $calculation['gross_pay'];
        $payroll->taxable_pay = $calculation['taxable_pay'];
        $payroll->net_pay = $calculation['net_pay'];
        $payroll->gross_amount = $calculation['gross_amount'];
        $payroll->paye = $calculation['paye'];
        $payroll->nssf_employee = $calculation['nssf_employee'];
        $payroll->nssf_employer = $calculation['nssf_employer'];
        $payroll->shif = $calculation['shif'];
        $payroll->housing_levy_employee = $calculation['housing_levy_employee'];
        $payroll->housing_levy_employer = $calculation['housing_levy_employer'];
        $payroll->total_allowances = $calculation['total_allowances'];
        $payroll->total_deductions = $calculation['total_deductions'];
        $payroll->total_duration_amount = $calculation['basic_salary'];

        return $payroll;
    }

    public function calculateYtd(int $userId, int $year, ?int $excludePayrollId = null): array
    {
        $query = Payroll::where('user_id', $userId)->where('year', (string)$year);
        if ($excludePayrollId) {
            $query->where('id', '!=', $excludePayrollId);
        }

        return [
            'ytd_gross' => (float)$query->sum(DB::raw('COALESCE(gross_pay, gross_amount, 0)')),
            'ytd_paye' => (float)$query->sum('paye'),
            'ytd_nssf' => (float)$query->sum('nssf_employee'),
            'ytd_net' => (float)$query->sum(DB::raw('COALESCE(net_pay, gross_amount, 0)')),
        ];
    }

    public function buildItemsFromRequest(array $requestItems): array
    {
        $items = [];
        $count = count($requestItems['id'] ?? []);
        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'id' => $requestItems['id'][$i] ?? null,
                'payroll_item_id' => $requestItems['id'][$i] ?? null,
                'name' => $requestItems['name'][$i] ?? '',
                'type' => $requestItems['type'][$i] ?? 'allowance',
                'amount_type' => $requestItems['amount_type'][$i] ?? 'fixed',
                'amount' => $requestItems['amount'][$i] ?? 0,
                'category' => $requestItems['category'][$i] ?? 'other',
                'is_taxable' => $requestItems['is_taxable'][$i] ?? 1,
                'is_statutory' => $requestItems['is_statutory'][$i] ?? 0,
            ];
        }

        return $items;
    }

    public function generatePayslipForEmployee(PayrollRun $run, PayrollEmployee $employee, User $user): Payroll
    {
        $basicSalary = $this->resolveBasicSalary($employee, $run);
        $templateItems = $this->getTemplateItems($employee);

        $calculation = $this->calculateFromItems($templateItems, $basicSalary);
        $ytd = $this->calculateYtd($user->id, (int)date('Y', strtotime($run->period_end ?? 'now')));

        $payroll = new Payroll();
        $payroll->payroll_run_id = $run->id;
        $payroll->payroll_template_id = $employee->payroll_template_id;
        $payroll->user_id = $user->id;
        $payroll->branch_id = $employee->branch_id ?? $run->branch_id;
        $payroll->currency_id = $run->currency_id;
        $payroll->created_by_id = $run->created_by_id;
        $payroll->employee_name = $user->first_name . ' ' . $user->last_name;
        $payroll->employee_number = $employee->employee_number;
        $payroll->department = $employee->department;
        $payroll->job_title = $employee->job_title;
        $payroll->kra_pin = $employee->kra_pin;
        $payroll->bank_name = $employee->bank_name;
        $payroll->account_number = $employee->account_number;
        $payroll->work_duration = 1;
        $payroll->duration_unit = 'Month';
        $payroll->amount_per_duration = $basicSalary;
        $payroll->date = $run->period_end ?? date('Y-m-d');
        $payroll->month = date('m', strtotime($payroll->date));
        $payroll->year = date('Y', strtotime($payroll->date));
        $payroll->status = 'draft';
        $payroll->ytd_gross = $ytd['ytd_gross'] + $calculation['gross_pay'];
        $payroll->ytd_paye = $ytd['ytd_paye'] + $calculation['paye'];
        $payroll->ytd_nssf = $ytd['ytd_nssf'] + $calculation['nssf_employee'];
        $payroll->ytd_net = $ytd['ytd_net'] + $calculation['net_pay'];

        $this->applyCalculationToPayroll($payroll, $calculation);
        $payroll->save();
        $this->savePayrollItems($payroll->id, $calculation['allowances'], $calculation['deductions']);

        return $payroll;
    }

    public function recalculateRunTotals(PayrollRun $run): void
    {
        $payrolls = Payroll::where('payroll_run_id', $run->id)->get();
        $run->employee_count = $payrolls->count();
        $run->total_gross = $payrolls->sum('gross_pay');
        $run->total_net = $payrolls->sum('net_pay');
        $run->total_paye = $payrolls->sum('paye');
        $run->total_nssf = $payrolls->sum('nssf_employee');
        $run->total_shif = $payrolls->sum('shif');
        $run->total_housing_levy = $payrolls->sum('housing_levy_employee');
        $run->save();
    }

    protected function resolveBasicSalary(PayrollEmployee $employee, PayrollRun $run): float
    {
        if ($employee->salary_type === 'hourly') {
            $days = max(1, (strtotime($run->period_end) - strtotime($run->period_start)) / 86400 + 1);
            $hours = $days * 8;

            return (float)$employee->hourly_rate * $hours;
        }
        if ($employee->salary_type === 'daily') {
            $days = max(1, (strtotime($run->period_end) - strtotime($run->period_start)) / 86400 + 1);

            return (float)$employee->daily_rate * $days;
        }

        return (float)$employee->basic_salary;
    }

    protected function getTemplateItems(PayrollEmployee $employee): array
    {
        if (!$employee->payroll_template_id) {
            return [];
        }

        $template = $employee->payroll_template;
        if (!$template) {
            return [];
        }

        $items = [];
        foreach ($template->payroll_items as $templateItem) {
            $item = $templateItem->payroll_item ?? null;
            if (!$item || $item->is_statutory) {
                continue;
            }
            $items[] = [
                'id' => $item->id,
                'payroll_item_id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'amount_type' => $item->amount_type,
                'amount' => $item->amount,
                'category' => $item->category ?? 'other',
                'is_taxable' => $item->is_taxable ?? 1,
                'is_statutory' => 0,
            ];
        }

        return $items;
    }
}

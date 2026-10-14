<?php

namespace Modules\Payroll\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Branch\Entities\Branch;
use Modules\Core\Entities\Currency;
use Modules\Payroll\Entities\PayrollEmployee;
use Modules\Payroll\Entities\PayrollTemplate;
use Modules\User\Entities\User;

class PayrollEmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', '2fa']);
        $this->middleware(['permission:payroll.payroll.employees.index'])->only(['index', 'show']);
        $this->middleware(['permission:payroll.payroll.employees.create'])->only(['create', 'store']);
        $this->middleware(['permission:payroll.payroll.employees.edit'])->only(['edit', 'update']);
        $this->middleware(['permission:payroll.payroll.employees.destroy'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $data = PayrollEmployee::with('user')
            ->when($request->s, fn($q) => $q->where('employee_number', 'like', '%' . $request->s . '%')
                ->orWhere('department', 'like', '%' . $request->s . '%'))
            ->orderByDesc('id')
            ->paginate($request->per_page ?: 20)
            ->appends($request->input());

        return theme_view('payroll::employee.index', compact('data'));
    }

    public function create()
    {
        $users = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'client'))->get();
        $templates = PayrollTemplate::get();
        $branches = Branch::get();

        return theme_view('payroll::employee.create', compact('users', 'templates', 'branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|unique:payroll_employees,user_id',
            'basic_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:monthly,hourly,daily',
        ]);

        $employee = new PayrollEmployee();
        $employee->fill($request->only([
            'user_id', 'branch_id', 'payroll_template_id', 'employee_number', 'department',
            'job_title', 'kra_pin', 'nssf_number', 'shif_number', 'salary_type',
            'basic_salary', 'hourly_rate', 'daily_rate', 'bank_name', 'account_number',
            'date_hired', 'notes',
        ]));
        $employee->is_active = $request->is_active ?? 1;
        $employee->save();

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();

        return redirect('payroll/employee');
    }

    public function show($id)
    {
        $employee = PayrollEmployee::with('user', 'payroll_template')->findOrFail($id);

        return theme_view('payroll::employee.show', compact('employee'));
    }

    public function edit($id)
    {
        $employee = PayrollEmployee::findOrFail($id);
        $users = User::whereHas('roles', fn($q) => $q->where('name', '!=', 'client'))->get();
        $templates = PayrollTemplate::get();
        $branches = Branch::get();

        return theme_view('payroll::employee.edit', compact('employee', 'users', 'templates', 'branches'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|unique:payroll_employees,user_id,' . $id,
            'basic_salary' => 'required|numeric|min:0',
            'salary_type' => 'required|in:monthly,hourly,daily',
        ]);

        $employee = PayrollEmployee::findOrFail($id);
        $employee->fill($request->only([
            'user_id', 'branch_id', 'payroll_template_id', 'employee_number', 'department',
            'job_title', 'kra_pin', 'nssf_number', 'shif_number', 'salary_type',
            'basic_salary', 'hourly_rate', 'daily_rate', 'bank_name', 'account_number',
            'date_hired', 'notes',
        ]));
        $employee->is_active = $request->is_active ?? 1;
        $employee->save();

        \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();

        return redirect('payroll/employee');
    }

    public function destroy($id)
    {
        PayrollEmployee::findOrFail($id)->delete();
        \flash(trans_choice('core::general.successfully_deleted', 1))->success()->important();

        return redirect()->back();
    }
}

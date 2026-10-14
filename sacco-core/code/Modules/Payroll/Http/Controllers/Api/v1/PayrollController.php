<?php

namespace Modules\Payroll\Http\Controllers\Api\v1;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Payroll\Entities\Payroll;
use Modules\Payroll\Entities\PayrollEmployee;
use Modules\Payroll\Entities\PayrollRun;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware(['permission:payroll.payroll.index'])->only(['index', 'show']);
        $this->middleware(['permission:payroll.payroll.create'])->only(['store']);
        $this->middleware(['permission:payroll.payroll.edit'])->only(['update']);
        $this->middleware(['permission:payroll.payroll.destroy'])->only(['destroy']);
    }

    public function index(Request $request)
    {
        $limit = $request->limit ?: 20;
        $data = Payroll::leftJoin('branches', 'branches.id', 'payroll.branch_id')
            ->leftJoin('payroll_payments', 'payroll_payments.payroll_id', 'payroll.id')
            ->leftJoin('users', 'users.id', 'payroll.user_id')
            ->leftJoin('payroll_templates', 'payroll_templates.id', 'payroll.payroll_template_id')
            ->when($request->order_by, fn (Builder $q) => $q->orderBy($request->order_by, $request->order_by_dir ?: 'asc'))
            ->when($request->s, fn (Builder $q) => $q->where('payroll.id', 'like', '%' . $request->s . '%'))
            ->selectRaw("sum(payroll_payments.amount) payments, branches.name branch, concat(users.first_name,' ',users.last_name) user, payroll_templates.name payroll_template, payroll.*")
            ->groupBy('payroll.id')
            ->paginate($limit);

        return response()->json([$data]);
    }

    public function show($id)
    {
        $payroll = Payroll::with(['user', 'payroll_template', 'payroll_items', 'payroll_run'])->findOrFail($id);
        return response()->json(['data' => $payroll]);
    }

    public function employees(Request $request)
    {
        $limit = $request->limit ?: 20;
        $employees = PayrollEmployee::with(['user', 'payroll_template'])
            ->when($request->branch_id, fn ($q) => $q->where('branch_id', $request->branch_id))
            ->when($request->is_active !== null, fn ($q) => $q->where('is_active', $request->is_active))
            ->paginate($limit);

        return response()->json([$employees]);
    }

    public function employeeShow($id)
    {
        $employee = PayrollEmployee::with(['user', 'payroll_template'])->findOrFail($id);
        return response()->json(['data' => $employee]);
    }

    public function runs(Request $request)
    {
        $limit = $request->limit ?: 20;
        $runs = PayrollRun::orderByDesc('id')->paginate($limit);

        return response()->json([$runs]);
    }

    public function runShow($id)
    {
        $run = PayrollRun::with(['payrolls'])->findOrFail($id);
        return response()->json(['data' => $run]);
    }
}

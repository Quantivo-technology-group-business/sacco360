<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laracasts\Flash\Flash;
use Modules\Core\Entities\PaymentDetail;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsTransaction;
use Modules\Savings\Events\TransactionUpdated;
use Yajra\DataTables\Facades\DataTables;

class SavingsController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        if (!empty($request->status)) {
            $status = $request->status;
        } else {
            $status = "";
        }
        return theme_view('portal::savings.index', compact('status'));
    }

    public function get_savings(Request $request)
    {

        $status = $request->status;
        $client_id = session('client_id');
        $savings_officer_id = $request->savings_officer_id;

        $query = DB::table("savings")
            ->leftJoin("clients", "clients.id", "savings.client_id")
            ->leftJoin("savings_transactions", "savings_transactions.savings_id", "savings.id")
            ->leftJoin("savings_products", "savings_products.id", "savings.savings_product_id")
            ->leftJoin("branches", "branches.id", "savings.branch_id")->leftJoin("users", "users.id", "savings.savings_officer_id")
            ->selectRaw("concat(clients.first_name,' ',clients.last_name) client,concat(users.first_name,' ',users.last_name) savings_officer,savings.id,savings.client_id,savings.interest_rate,savings.activated_on_date,savings_products.name savings_product,savings_products.savings_category,savings.status,savings.decimals,branches.name branch, COALESCE(SUM(savings_transactions.credit)-SUM(savings_transactions.debit),0) balance")->when($status, function ($query) use ($status) {
                $query->where("savings.status", $status);
            })->when($client_id, function ($query) use ($client_id) {
                $query->where("savings.client_id", $client_id);
            })->when($savings_officer_id, function ($query) use ($savings_officer_id) {
                $query->where("savings.savings_officer_id", $savings_officer_id);
            })->groupBy("savings.id");
        $datatable = DataTables::of($query)->editColumn('client', function ($data) {
            return '<a href="' . url('portal/client/' . $data->client_id . '/show') . '">' . $data->client . '</a>';
        })->editColumn('balance', function ($data) {
            return number_format($data->balance, $data->decimals);
        })->editColumn('interest_rate', function ($data) {
            return number_format($data->interest_rate, 2);
        });
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            $datatable = $datatable->addColumn('wing', function ($data) {
                $wing = ($data->savings_category ?? '') === 'compulsory' ? 'BOSA' : 'FOSA';
                $class = $wing === 'BOSA' ? 'primary' : 'info';
                return '<span class="label label-' . $class . '">' . $wing . '</span>';
            });
        }
        $rawColumns = ['id', 'client', 'action', 'status'];
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            $rawColumns[] = 'wing';
        }
        return $datatable->editColumn('status', function ($data) {
            if ($data->status == 'pending') {
                return '<span class="label label-warning">' . trans_choice('savings::general.pending', 1) . ' ' . trans_choice('savings::general.approval', 1) . '</span>';
            }
            if ($data->status == 'submitted') {
                return '<span class="label label-warning">' . trans_choice('savings::general.pending_approval', 1) . '</span>';
            }
            if ($data->status == 'overpaid') {
                return '<span class="label label-warning">' . trans_choice('savings::general.overpaid', 1) . '</span>';
            }
            if ($data->status == 'approved') {
                return '<span class="label label-warning">' . trans_choice('savings::general.awaiting_activation', 1) . '</span>';
            }
            if ($data->status == 'active') {
                return '<span class="label label-info">' . trans_choice('savings::general.active', 1) . '</span>';
            }
            if ($data->status == 'rejected') {
                return '<span class="label label-danger">' . trans_choice('savings::general.rejected', 1) . '</span>';
            }
            if ($data->status == 'withdrawn') {
                return '<span class="label label-danger">' . trans_choice('savings::general.withdrawn', 1) . '</span>';
            }
            if ($data->status == 'dormant') {
                return '<span class="label label-warning">' . trans_choice('savings::general.dormant', 1) . '</span>';
            }
            if ($data->status == 'closed') {
                return '<span class="label label-success">' . trans_choice('savings::general.closed', 1) . '</span>';
            }
            if ($data->status == 'inactive') {
                return '<span class="label label-warning">' . trans_choice('savings::general.inactive', 1) . '</span>';
            }

        })->editColumn('action', function ($data) {

            $action = '<a href="' . url('portal/savings/' . $data->id . '/show') . '" class="btn btn-info">' . trans_choice('general.detail', 2) . '</a>';

            return $action;
        })->editColumn('id', function ($data) {
            return '<a href="' . url('portal/savings/' . $data->id . '/show') . '" class="">' . $data->id . '</a>';

        })->rawColumns($rawColumns)->make(true);
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return theme_view('portal::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {

        $savings = Savings::with('transactions')->with('charges')->with('client')->with('savings_product')->find($id);
        if ($savings->client_id != session('client_id')) {
            Flash::warning(trans('core::general.permission_denied'));
            return redirect()->back();
        }
        $wing = null;
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            $wing = (new \Modules\Sacco\Services\WingService())->getAccountWing($savings);
        }
        return theme_view('portal::savings.show', compact('savings', 'wing'));
    }
    //transactions
    public function show_transaction($id)
    {
        $savings_transaction = SavingsTransaction::with('payment_detail')->with('savings')->find($id);
        return theme_view('portal::savings.savings_transaction.show', compact('savings_transaction'));
    }

    public function pdf_transaction($id)
    {
        $savings_transaction = SavingsTransaction::with('payment_detail')->with('savings')->find($id);
        $pdf = PDF::loadView('portal::savings.savings_transaction.pdf', compact('savings_transaction'));
        return $pdf->download(trans_choice('savings::general.transaction', 1) . ' ' . trans_choice('core::general.detail', 2) . ".pdf");
    }

    public function print_transaction($id)
    {
        $savings_transaction = SavingsTransaction::with('payment_detail')->with('savings')->find($id);
        return theme_view('portal::savings.savings_transaction.print', compact('savings_transaction'));
    }

    public function create_deposit($id)
    {
        $savings = $this->resolveOwnedSavings($id);
        if ($savings instanceof \Illuminate\Http\RedirectResponse) {
            return $savings;
        }
        $balance = $this->getSavingsBalance($savings);
        return theme_view('portal::savings.deposit.create', compact('savings', 'balance'));
    }

    public function store_deposit(Request $request, $id)
    {
        $savings = $this->resolveOwnedSavings($id);
        if ($savings instanceof \Illuminate\Http\RedirectResponse) {
            return $savings;
        }

        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/savings/' . $id . '/show')) {
            return $redirect;
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float)$request->amount;
        if ($redirect = $this->verifyTransactionAuth($request, $client->id, $amount, 'savings_deposit')) {
            return $redirect;
        }
        if (!$this->validatePortalTransaction($savings, 'deposit', $amount)) {
            return redirect()->back()->withInput();
        }

        try {
            $this->postPortalDeposit($savings, $amount, $request->notes);
            $this->notifyPortalTransaction($savings, 'deposit', $amount);
            \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        } catch (\Exception $e) {
            Flash::warning($e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect('portal/savings/' . $savings->id . '/show');
    }

    public function create_withdrawal($id)
    {
        $savings = $this->resolveOwnedSavings($id);
        if ($savings instanceof \Illuminate\Http\RedirectResponse) {
            return $savings;
        }

        $wing = null;
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            $wing = (new \Modules\Sacco\Services\WingService())->getAccountWing($savings);
            if ($wing === 'bosa') {
                Flash::warning(trans('sacco::general.bosa_non_withdrawable'));
                return redirect('portal/savings/' . $savings->id . '/show');
            }
        }

        $balance = $this->getSavingsBalance($savings);
        return theme_view('portal::savings.withdrawal.create', compact('savings', 'balance', 'wing'));
    }

    public function store_withdrawal(Request $request, $id)
    {
        $savings = $this->resolveOwnedSavings($id);
        if ($savings instanceof \Illuminate\Http\RedirectResponse) {
            return $savings;
        }

        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/savings/' . $id . '/show')) {
            return $redirect;
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ]);

        $amount = (float)$request->amount;
        if ($redirect = $this->verifyTransactionAuth($request, $client->id, $amount, 'savings_withdrawal')) {
            return $redirect;
        }
        if (!$this->validatePortalTransaction($savings, 'withdrawal', $amount)) {
            return redirect()->back()->withInput();
        }

        try {
            $this->postPortalWithdrawal($savings, $amount, $request->notes);
            $this->notifyPortalTransaction($savings, 'withdrawal', $amount);
            \flash(trans_choice('core::general.successfully_saved', 1))->success()->important();
        } catch (\Exception $e) {
            Flash::warning($e->getMessage());
            return redirect()->back()->withInput();
        }

        return redirect('portal/savings/' . $savings->id . '/show');
    }

    protected function resolveOwnedSavings($id)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $savings = Savings::with('savings_product')->find($id);
        if (!$savings || $savings->client_id != $client->id) {
            Flash::warning(trans('core::general.permission_denied'));
            return redirect('portal/savings');
        }
        if ($savings->status !== 'active') {
            Flash::warning(trans('core::general.permission_denied'));
            return redirect('portal/savings/' . $savings->id . '/show');
        }

        return $savings;
    }

    protected function getSavingsBalance(Savings $savings): float
    {
        if (class_exists(\Modules\Sacco\Services\WingService::class)) {
            return (new \Modules\Sacco\Services\WingService())->getAccountBalance($savings->id);
        }

        return (float)SavingsTransaction::where('savings_id', $savings->id)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(credit),0) - COALESCE(SUM(debit),0) as balance')
            ->value('balance');
    }

    protected function validatePortalTransaction(Savings $savings, string $type, float $amount): bool
    {
        if (function_exists('sacco_requires_maker_checker') && sacco_requires_maker_checker($amount)) {
            Flash::warning('This amount requires staff approval. Please visit a branch.');
            return false;
        }

        if (function_exists('sacco_validate_member_transaction')) {
            $memberCheck = sacco_validate_member_transaction($savings->client_id);
            if (!$memberCheck['allowed']) {
                Flash::warning($memberCheck['reason']);
                return false;
            }
        }

        if (function_exists('sacco_check_earmarks')) {
            $earmarkCheck = sacco_check_earmarks($savings->client_id, $type, $amount);
            if (!$earmarkCheck['allowed']) {
                Flash::warning(implode(' ', $earmarkCheck['violations']));
                return false;
            }
        }

        if ($type === 'withdrawal' && function_exists('sacco_validate_withdrawal')) {
            $wingCheck = sacco_validate_withdrawal($savings, $amount);
            if (!$wingCheck['allowed']) {
                Flash::warning(implode(' ', $wingCheck['violations']));
                return false;
            }
        }

        return true;
    }

    protected function postPortalDeposit(Savings $savings, float $amount, ?string $notes = null): void
    {
        if (class_exists(\Modules\Sacco\Services\SavingsChannelService::class)) {
            (new \Modules\Sacco\Services\SavingsChannelService())->postDeposit(
                $savings,
                $amount,
                'portal',
                'PORTAL-' . uniqid()
            );
            return;
        }

        $this->postPortalTransaction($savings, $amount, 'deposit', $notes);
    }

    protected function postPortalWithdrawal(Savings $savings, float $amount, ?string $notes = null): void
    {
        if (class_exists(\Modules\Sacco\Services\SavingsChannelService::class)) {
            (new \Modules\Sacco\Services\SavingsChannelService())->postWithdrawal(
                $savings,
                $amount,
                'portal',
                'PORTAL-' . uniqid()
            );
            return;
        }

        $balance = $this->getSavingsBalance($savings);
        $savings->loadMissing('savings_product');
        if ($amount > $balance) {
            $product = $savings->savings_product;
            if (!$product || !$product->allow_overdraft || $amount > ($product->overdraft_limit ?? 0)) {
                throw new \RuntimeException(trans_choice('savings::general.insufficient_balance', 1));
            }
        }

        $this->postPortalTransaction($savings, $amount, 'withdrawal', $notes);
    }

    protected function postPortalTransaction(Savings $savings, float $amount, string $type, ?string $notes = null): void
    {
        DB::transaction(function () use ($savings, $amount, $type, $notes) {
            $paymentDetail = new PaymentDetail();
            $paymentDetail->created_by_id = Auth::id();
            $paymentDetail->payment_type_id = 1;
            $paymentDetail->transaction_type = 'portal';
            $paymentDetail->description = $notes;
            $paymentDetail->save();

            $txn = new SavingsTransaction();
            $txn->created_by_id = Auth::id();
            $txn->savings_id = $savings->id;
            $txn->branch_id = $savings->branch_id;
            $txn->payment_detail_id = $paymentDetail->id;
            $txn->name = $type === 'deposit'
                ? trans_choice('savings::general.deposit', 1)
                : trans_choice('savings::general.withdrawal', 1);
            $txn->savings_transaction_type_id = $type === 'deposit' ? 1 : 2;
            $txn->submitted_on = date('Y-m-d');
            $txn->created_on = date('Y-m-d');
            $txn->amount = $amount;
            $txn->reversible = 1;
            if ($type === 'deposit') {
                $txn->credit = $amount;
            } else {
                $txn->debit = $amount;
            }
            $txn->save();

            event(new TransactionUpdated($savings));
        });
    }

    protected function notifyPortalTransaction(Savings $savings, string $type, float $amount): void
    {
        if (!class_exists(\Modules\Sacco\Services\TransactionNotificationService::class)) {
            return;
        }
        $member = \Modules\Client\Entities\Client::find($savings->client_id);
        if ($member) {
            (new \Modules\Sacco\Services\TransactionNotificationService())->notify($member, $type, $amount, 'PORTAL');
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return theme_view('portal::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}

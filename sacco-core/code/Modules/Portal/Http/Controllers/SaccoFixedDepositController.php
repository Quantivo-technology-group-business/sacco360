<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Sacco\Entities\SaccoFixedDeposit;
use Modules\Sacco\Services\FixedDepositService;
use Modules\Sacco\Services\SavingsChannelService;
use Modules\Sacco\Services\WingService;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsProduct;

class SaccoFixedDepositController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    public function index()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $deposits = SaccoFixedDeposit::where('client_id', $client->id)->orderByDesc('id')->paginate(20);

        return theme_view('portal::sacco.fixed_deposit.index', compact('deposits'));
    }

    public function create()
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        $balance = $fosa ? $wing->getAccountBalance($fosa->id) : 0;
        $product = SavingsProduct::where('account_product_type', 'fixed_deposit')->where('active', 1)->first();

        return theme_view('portal::sacco.fixed_deposit.create', compact('fosa', 'balance', 'product'));
    }

    public function store(Request $request, FixedDepositService $fdService)
    {
        $this->requireSaccoModule();
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        if ($redirect = $this->requireKyc($client->id, 'portal/sacco/fixed_deposit/create')) {
            return $redirect;
        }

        $request->validate([
            'principal' => 'required|numeric|min:1',
            'tenure_months' => 'required|integer|min:1|max:60',
            'renewal_option' => 'nullable|in:payout,renew_principal,renew_principal_interest',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->principal, 'fixed_deposit')) {
            return $redirect;
        }

        $product = SavingsProduct::where('account_product_type', 'fixed_deposit')->where('active', 1)->first();
        if (!$product) {
            return redirect('portal/sacco/fixed_deposit/create')->with('error', 'Fixed deposit product is not configured.');
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        if (!$fosa) {
            return redirect('portal/sacco/fixed_deposit/create')->with('error', trans('sacco::general.no_fosa_account'));
        }

        try {
            DB::transaction(function () use ($request, $client, $product, $fosa, $fdService, $wing) {
                $savings = new Savings();
                $savings->currency_id = $product->currency_id;
                $savings->created_by_id = Auth::id();
                $savings->client_id = $client->id;
                $savings->savings_product_id = $product->id;
                $savings->branch_id = $client->branch_id;
                $savings->interest_rate = $product->default_interest_rate ?? 0;
                $savings->status = 'active';
                $savings->submitted_on_date = date('Y-m-d');
                $savings->activated_on_date = date('Y-m-d');
                $savings->save();
                $savings->account_number = 'FD-' . str_pad((string) $savings->id, 4, '0', STR_PAD_LEFT);
                $savings->save();

                (new SavingsChannelService())->postWithdrawal($fosa, (float) $request->principal, 'fixed_deposit', 'FD-OPEN-' . $savings->id);
                (new SavingsChannelService())->postDeposit($savings, (float) $request->principal, 'fixed_deposit', 'FD-OPEN-' . $savings->id);

                $fdService->create($savings, [
                    'principal' => (float) $request->principal,
                    'tenure_months' => (int) $request->tenure_months,
                    'renewal_option' => $request->renewal_option ?? 'payout',
                    'interest_rate' => $product->default_interest_rate ?? 0,
                ]);
            });

            return redirect('portal/sacco/fixed_deposit')->with('success', trans('core::general.successfully_saved'));
        } catch (\Throwable $e) {
            return redirect('portal/sacco/fixed_deposit/create')->with('error', $e->getMessage());
        }
    }
}

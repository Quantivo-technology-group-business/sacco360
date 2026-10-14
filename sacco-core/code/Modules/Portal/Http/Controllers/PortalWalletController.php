<?php

namespace Modules\Portal\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Entities\PaymentDetail;
use Modules\Core\Entities\PaymentType;
use Modules\Portal\Http\Controllers\Concerns\RequiresTransactionAuth;
use Modules\Portal\Http\Controllers\Concerns\ResolvesPortalClient;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletTransaction;
use Modules\Wallet\Events\TransactionUpdated;

class PortalWalletController extends Controller
{
    use ResolvesPortalClient;
    use RequiresTransactionAuth;

    protected function memberWallet(int $clientId): Wallet
    {
        return Wallet::where('client_id', $clientId)->where('status', 'active')->firstOrFail();
    }

    public function index()
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wallet = Wallet::where('client_id', $client->id)->where('status', 'active')->first();
        $transactions = $wallet
            ? WalletTransaction::where('wallet_id', $wallet->id)->orderByDesc('id')->limit(20)->get()
            : collect();

        return theme_view('portal::wallet.index', compact('wallet', 'transactions'));
    }

    public function createDeposit()
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wallet = $this->memberWallet($client->id);
        $paymentTypes = PaymentType::where('active', 1)->get();

        return theme_view('portal::wallet.deposit', compact('wallet', 'paymentTypes'));
    }

    public function storeDeposit(Request $request)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_type_id' => 'required',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'wallet_deposit')) {
            return $redirect;
        }

        $wallet = $this->memberWallet($client->id);

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $request->payment_type_id;
        $paymentDetail->transaction_type = 'wallet_transaction';
        $paymentDetail->description = $request->description;
        $paymentDetail->save();

        $txn = new WalletTransaction();
        $txn->created_by_id = Auth::id();
        $txn->wallet_id = $wallet->id;
        $txn->branch_id = $wallet->branch_id;
        $txn->payment_detail_id = $paymentDetail->id;
        $txn->name = 'Portal deposit';
        $txn->transaction_type = 'deposit';
        $txn->submitted_on = $request->date;
        $txn->created_on = date('Y-m-d');
        $txn->amount = $request->amount;
        $txn->credit = $request->amount;
        $txn->save();

        $wallet->balance = $wallet->balance + $request->amount;
        $wallet->save();
        event(new TransactionUpdated($wallet));

        return redirect('portal/wallet')->with('success', trans('core::general.successfully_saved'));
    }

    public function createWithdrawal()
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $wallet = $this->memberWallet($client->id);
        $paymentTypes = PaymentType::where('active', 1)->get();

        return theme_view('portal::wallet.withdraw', compact('wallet', 'paymentTypes'));
    }

    public function storeWithdrawal(Request $request)
    {
        $client = $this->requireClient();
        if ($client instanceof \Illuminate\Http\RedirectResponse) {
            return $client;
        }

        $request->validate([
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date',
            'payment_type_id' => 'required',
        ]);

        if ($redirect = $this->verifyTransactionAuth($request, $client->id, (float) $request->amount, 'wallet_withdraw')) {
            return $redirect;
        }

        $wallet = $this->memberWallet($client->id);
        if ($wallet->balance < $request->amount) {
            return redirect('portal/wallet/withdraw/create')->with('error', 'Insufficient wallet balance.');
        }

        $paymentDetail = new PaymentDetail();
        $paymentDetail->created_by_id = Auth::id();
        $paymentDetail->payment_type_id = $request->payment_type_id;
        $paymentDetail->transaction_type = 'wallet_transaction';
        $paymentDetail->description = $request->description;
        $paymentDetail->save();

        $txn = new WalletTransaction();
        $txn->created_by_id = Auth::id();
        $txn->wallet_id = $wallet->id;
        $txn->branch_id = $wallet->branch_id;
        $txn->payment_detail_id = $paymentDetail->id;
        $txn->name = 'Portal withdrawal';
        $txn->transaction_type = 'withdrawal';
        $txn->submitted_on = $request->date;
        $txn->created_on = date('Y-m-d');
        $txn->amount = $request->amount;
        $txn->debit = $request->amount;
        $txn->save();

        $wallet->balance = $wallet->balance - $request->amount;
        $wallet->save();
        event(new TransactionUpdated($wallet));

        return redirect('portal/wallet')->with('success', trans('core::general.successfully_saved'));
    }
}

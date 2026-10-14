<?php

namespace Modules\Portal\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Loan\Entities\Loan;
use Modules\Portal\Http\Controllers\Concerns\ResolvesApiPortalClient;
use Modules\Portal\Http\Controllers\Concerns\ResolvesMpesaSourceAccounts;
use Modules\Sacco\Entities\SaccoDebitCard;
use Modules\Sacco\Entities\SaccoMpesaB2cTransaction;
use Modules\Sacco\Entities\SaccoMpesaC2bTransaction;
use Modules\Sacco\Entities\SaccoPesalinkTransaction;
use Modules\Sacco\Entities\SaccoStandingOrder;
use Modules\Sacco\Entities\SaccoTransfer;
use Modules\Sacco\Services\BeneficiaryService;
use Modules\Sacco\Services\CrossSellService;
use Modules\Sacco\Services\KycService;
use Modules\Sacco\Services\MemberAccountService;
use Modules\Sacco\Services\MpesaB2cService;
use Modules\Sacco\Services\PesalinkService;
use Modules\Sacco\Services\TransferService;
use Modules\Sacco\Services\WingService;
use Modules\Savings\Entities\Savings;
use Modules\Setting\Entities\Setting;
use Modules\Share\Entities\Share;

class SaccoPortalApiController extends Controller
{
    use ResolvesApiPortalClient;
    use ResolvesMpesaSourceAccounts;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function transferForm(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $wing = new WingService();
        $bosa = $wing->getBosaAccount($client->id);
        $fosa = $wing->getFosaAccount($client->id);
        $loans = Loan::where('client_id', $client->id)->whereIn('status', ['active', 'disbursed'])->get();
        $shares = Share::where('client_id', $client->id)->where('status', 'active')->get();
        $suggestions = class_exists(CrossSellService::class)
            ? (new CrossSellService())->suggestForMember($client->id)
            : [];

        return response()->json([
            'data' => [
                'bosa' => $bosa ? ['id' => $bosa->id, 'label' => 'BOSA #' . ($bosa->account_number ?? $bosa->id)] : null,
                'fosa' => $fosa ? ['id' => $fosa->id, 'label' => 'FOSA #' . ($fosa->account_number ?? $fosa->id)] : null,
                'loans' => $loans->map(fn ($l) => ['id' => $l->id, 'label' => 'Loan #' . $l->id . ' - ' . number_format($l->principal, 2)])->values(),
                'shares' => $shares->map(fn ($s) => ['id' => $s->id, 'label' => 'Share #' . $s->id])->values(),
                'suggestions' => $suggestions,
            ],
        ]);
    }

    public function storeTransfer(Request $request, TransferService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        if ($error = $this->requireKyc($client->id)) {
            return $error;
        }

        $request->validate([
            'transfer_type' => 'required|in:savings_to_savings,savings_to_loan,share_to_savings,savings_to_share',
            'source_id' => 'required',
            'destination_id' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        $type = $request->transfer_type;

        if (in_array($type, ['savings_to_savings', 'savings_to_loan', 'savings_to_share'], true)) {
            Savings::where('id', $request->source_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'share_to_savings') {
            Share::where('id', $request->source_id)->where('client_id', $client->id)->firstOrFail();
        }
        if (in_array($type, ['savings_to_savings', 'share_to_savings'], true)) {
            Savings::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'savings_to_loan') {
            Loan::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($type === 'savings_to_share') {
            Share::where('id', $request->destination_id)->where('client_id', $client->id)->firstOrFail();
        }

        $source = in_array($type, ['savings_to_savings', 'savings_to_loan', 'savings_to_share'], true)
            ? Savings::findOrFail($request->source_id)
            : Share::findOrFail($request->source_id);

        $transfer = $service->execute([
            'client_id' => $client->id,
            'branch_id' => $source->branch_id ?? $client->branch_id,
            'transfer_type' => $type,
            'source_id' => $request->source_id,
            'destination_id' => $request->destination_id,
            'amount' => $request->amount,
        ]);

        return response()->json(['message' => 'Transfer completed successfully', 'data' => $transfer]);
    }

    public function transferHistory(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $limit = $request->limit ?: 20;
        $transfers = SaccoTransfer::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([$transfers]);
    }

    public function openBosa(Request $request, MemberAccountService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        try {
            $account = $service->openBosaAccount($client->id);
            return response()->json(['message' => 'BOSA account opened successfully', 'data' => $account]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function beneficiaries(Request $request, BeneficiaryService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        return response()->json(['data' => $service->listForClient($client->id)]);
    }

    public function storeBeneficiary(Request $request, BeneficiaryService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'nickname' => 'required|string|max:100',
            'name' => 'required|string|max:255',
            'phone' => 'required|string',
        ]);

        try {
            $beneficiary = $service->create($client->id, $request->only(['nickname', 'name', 'phone']));
            return response()->json(['message' => 'Beneficiary saved', 'data' => $beneficiary], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function destroyBeneficiary(Request $request, $id, BeneficiaryService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        try {
            $service->deactivate($client->id, (int)$id);
            return response()->json(['message' => 'Beneficiary removed']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function mpesaSources(Request $request, MpesaB2cService $mpesaService)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $sources = $this->resolveMpesaSources($client->id);

        return response()->json([
            'data' => [
                'fosa' => $sources['fosa'] ? ['id' => $sources['fosa']->id, 'account_number' => $sources['fosa']->account_number, 'balance' => $sources['fosaBalance']] : null,
                'wallet' => $sources['wallet'] ? ['id' => $sources['wallet']->id, 'balance' => $sources['walletBalance']] : null,
                'member_phone' => $client->mobile ?: $client->phone,
                'simulation' => $mpesaService->isSimulationMode(),
            ],
        ]);
    }

    public function mpesaSend(Request $request, MpesaB2cService $service, BeneficiaryService $beneficiaryService)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        if ($error = $this->requireKyc($client->id)) {
            return $error;
        }

        $request->validate([
            'source_type' => 'required|in:fosa,wallet',
            'amount' => 'required|numeric|min:1',
            'beneficiary_id' => 'nullable|integer',
            'recipient_phone' => 'required_without:beneficiary_id',
            'recipient_name' => 'required_without:beneficiary_id',
        ]);

        $sources = $this->resolveMpesaSources($client->id);
        if ($request->source_type === 'fosa' && !$sources['fosa']) {
            return response()->json(['error' => trans('sacco::general.no_fosa_account')], 422);
        }
        if ($request->source_type === 'wallet' && !$sources['wallet']) {
            return response()->json(['error' => trans('sacco::general.no_wallet_account')], 422);
        }

        try {
            $payload = array_merge($request->all(), [
                'client_id' => $client->id,
                'savings_id' => $sources['fosa'] ? $sources['fosa']->id : null,
                'wallet_id' => $sources['wallet'] ? $sources['wallet']->id : null,
            ]);
            $txn = $service->sendToBeneficiary($payload);
            $success = in_array($txn->status, ['completed', 'submitted'], true);

            return response()->json([
                'message' => $success ? trans('sacco::general.mpesa_send_success') : trans('sacco::general.mpesa_send_failed'),
                'data' => $txn,
            ], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function mpesaWithdraw(Request $request, MpesaB2cService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        if ($error = $this->requireKyc($client->id)) {
            return $error;
        }

        $memberPhone = $client->mobile ?: $client->phone;
        if (!$memberPhone) {
            return response()->json(['error' => trans('sacco::general.no_member_phone')], 422);
        }

        $request->validate([
            'source_type' => 'required|in:fosa,wallet',
            'amount' => 'required|numeric|min:1',
        ]);

        $sources = $this->resolveMpesaSources($client->id);
        if ($request->source_type === 'fosa' && !$sources['fosa']) {
            return response()->json(['error' => trans('sacco::general.no_fosa_account')], 422);
        }
        if ($request->source_type === 'wallet' && !$sources['wallet']) {
            return response()->json(['error' => trans('sacco::general.no_wallet_account')], 422);
        }

        try {
            $payload = array_merge($request->all(), [
                'client_id' => $client->id,
                'recipient_phone' => $memberPhone,
                'recipient_name' => trim($client->first_name . ' ' . $client->last_name),
                'savings_id' => $sources['fosa'] ? $sources['fosa']->id : null,
                'wallet_id' => $sources['wallet'] ? $sources['wallet']->id : null,
            ]);
            $txn = $service->withdrawToSelf($payload);
            $success = in_array($txn->status, ['completed', 'submitted'], true);

            return response()->json([
                'message' => $success ? trans('sacco::general.mpesa_withdraw_success') : trans('sacco::general.mpesa_withdraw_failed'),
                'data' => $txn,
            ], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function mpesaTransactions(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $limit = $request->limit ?: 20;
        $transactions = SaccoMpesaB2cTransaction::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([$transactions]);
    }

    public function kycProfile(Request $request, KycService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        return response()->json(['data' => $service->getMemberProfile($client->id)]);
    }

    public function storeKycDocument(Request $request, KycService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'document_type' => 'required|string|max:50',
            'document_number' => 'nullable|string|max:100',
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $document = $service->uploadDocument($client->id, $request->only([
            'document_type', 'document_number',
        ]), $request->file('file'), auth('api')->id());

        return response()->json(['message' => 'Document submitted for review', 'data' => $document], 201);
    }

    public function standingOrders(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $limit = $request->limit ?: 20;
        $orders = SaccoStandingOrder::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([$orders]);
    }

    public function standingOrderForm(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        return response()->json([
            'data' => [
                'savings' => Savings::where('client_id', $client->id)->where('status', 'active')->get(['id', 'account_number']),
                'loans' => Loan::where('client_id', $client->id)->whereIn('status', ['active', 'disbursed'])->get(['id', 'principal']),
            ],
        ]);
    }

    public function storeStandingOrder(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $request->validate([
            'source_savings_id' => 'required',
            'amount' => 'required|numeric|min:0.01',
            'frequency' => 'required|in:daily,weekly,monthly',
            'start_date' => 'required|date',
            'destination_loan_id' => 'nullable',
            'destination_savings_id' => 'nullable',
        ]);

        if (!$request->destination_loan_id && !$request->destination_savings_id) {
            return response()->json(['error' => 'Select a destination loan or savings account'], 422);
        }

        Savings::where('id', $request->source_savings_id)->where('client_id', $client->id)->firstOrFail();
        if ($request->destination_loan_id) {
            Loan::where('id', $request->destination_loan_id)->where('client_id', $client->id)->firstOrFail();
        }
        if ($request->destination_savings_id) {
            Savings::where('id', $request->destination_savings_id)->where('client_id', $client->id)->firstOrFail();
        }

        $order = new SaccoStandingOrder();
        $order->created_by_id = Auth::id();
        $order->client_id = $client->id;
        $order->fill($request->only(['source_savings_id', 'destination_loan_id', 'destination_savings_id', 'amount', 'frequency', 'start_date', 'end_date', 'description']));
        $order->next_run_date = $request->start_date;
        $order->status = 'active';
        $order->save();

        return response()->json(['message' => 'Standing order created successfully', 'data' => $order], 201);
    }

    public function cancelStandingOrder(Request $request, $id)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $order = SaccoStandingOrder::where('id', $id)->where('client_id', $client->id)->firstOrFail();
        $order->status = 'cancelled';
        $order->save();

        return response()->json(['message' => 'Standing order cancelled', 'data' => $order]);
    }

    public function pesalinkTransactions(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $limit = $request->limit ?: 20;
        $transactions = SaccoPesalinkTransaction::where('client_id', $client->id)
            ->orderByDesc('id')
            ->paginate($limit);

        return response()->json([
            'simulation' => empty(config('sacco.pesalink_api_url')),
            'data' => $transactions,
        ]);
    }

    public function pesalinkForm(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);

        return response()->json([
            'data' => [
                'fosa' => $fosa ? ['id' => $fosa->id, 'account_number' => $fosa->account_number] : null,
                'balance' => $fosa ? $wing->getAccountBalance($fosa->id) : 0,
                'simulation' => empty(config('sacco.pesalink_api_url')),
            ],
        ]);
    }

    public function storePesalink(Request $request, PesalinkService $service)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }
        if ($error = $this->requireKyc($client->id)) {
            return $error;
        }

        $wing = new WingService();
        $fosa = $wing->getFosaAccount($client->id);
        if (!$fosa) {
            return response()->json(['error' => 'No active FOSA account found'], 422);
        }

        $request->validate([
            'beneficiary_account' => 'required',
            'beneficiary_bank' => 'required',
            'beneficiary_name' => 'required',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $txn = $service->sendFromFosa(array_merge($request->all(), ['savings_id' => $fosa->id]));
            $success = $txn->status === 'completed';

            return response()->json([
                'message' => $success ? 'Pesalink transfer completed' : 'Transfer failed: ' . $txn->response_message,
                'data' => $txn,
            ], $success ? 200 : 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function cards(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $cards = SaccoDebitCard::where('client_id', $client->id)->orderByDesc('id')->get();

        return response()->json(['data' => $cards]);
    }

    public function depositInfo(Request $request)
    {
        if ($error = $this->requireSaccoModule()) {
            return $error;
        }
        $client = $this->requireClient($request);
        if ($client instanceof \Illuminate\Http\JsonResponse) {
            return $client;
        }

        $memberNumber = $client->member_number ?? $client->external_id ?? (string)$client->id;
        $paybill = Setting::where('setting_key', 'mpesa.business_shortcode')->value('setting_value') ?? '';
        $fosa = (new WingService())->getFosaAccount($client->id);

        $recentDeposits = SaccoMpesaC2bTransaction::where(function ($q) use ($memberNumber, $client) {
            $q->where('bill_ref_number', $memberNumber)->orWhere('client_id', $client->id);
        })->orderByDesc('id')->limit(10)->get();

        return response()->json([
            'data' => [
                'member_number' => $memberNumber,
                'paybill' => $paybill,
                'fosa' => $fosa,
                'recent_deposits' => $recentDeposits,
            ],
        ]);
    }
}

<?php

namespace Modules\Sacco\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Modules\Client\Entities\Client;
use Modules\Loan\Entities\Loan;
use Modules\Loan\Entities\LoanApplication;
use Modules\Loan\Entities\LoanProduct;
use Modules\Sacco\Services\TransferService;
use Modules\Sacco\Services\UtilityPaymentService;
use Modules\Sacco\Services\WingService;
use Modules\Setting\Entities\Setting;

class UssdActionController extends Controller
{
    public function transfer(Request $request, TransferService $transferService, WingService $wingService)
    {
        $request->validate([
            'clientId' => 'required|integer',
            'transferType' => 'required|in:savings_to_loan,savings_to_savings',
            'amount' => 'required|numeric|min:0.01',
            'loanId' => 'nullable|integer',
        ]);

        $client = Client::findOrFail($request->clientId);

        try {
            if ($request->transferType === 'savings_to_loan') {
                $loan = Loan::where('id', $request->loanId)->where('client_id', $client->id)->firstOrFail();
                $fosa = $wingService->getFosaAccount($client->id);
                if (!$fosa) {
                    return response()->json(['success' => false, 'message' => 'No FOSA account found'], 422);
                }

                $transfer = $transferService->execute([
                    'client_id' => $client->id,
                    'branch_id' => $fosa->branch_id,
                    'transfer_type' => 'savings_to_loan',
                    'source_id' => $fosa->id,
                    'destination_id' => $loan->id,
                    'amount' => (float)$request->amount,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transfer completed',
                    'data' => ['reference' => $transfer->reference],
                ]);
            }

            $bosa = $wingService->getBosaAccount($client->id);
            $fosa = $wingService->getFosaAccount($client->id);
            if (!$bosa || !$fosa) {
                return response()->json(['success' => false, 'message' => 'BOSA or FOSA account not found'], 422);
            }

            $transfer = $transferService->execute([
                'client_id' => $client->id,
                'branch_id' => $bosa->branch_id,
                'transfer_type' => 'savings_to_savings',
                'source_id' => $bosa->id,
                'destination_id' => $fosa->id,
                'amount' => (float)$request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transfer completed',
                'data' => ['reference' => $transfer->reference],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function loanApplication(Request $request)
    {
        $request->validate([
            'clientId' => 'required|integer',
            'amount' => 'required|numeric|min:1',
        ]);

        $client = Client::findOrFail($request->clientId);
        $product = LoanProduct::where('active', 1)->orderBy('id')->first();
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'No active loan product configured'], 422);
        }

        $application = new LoanApplication();
        $application->branch_id = $client->branch_id;
        $application->client_id = $client->id;
        $application->loan_product_id = $product->id;
        $application->amount = $request->amount;
        $application->status = 'pending';
        $application->notes = $request->notes ?? 'USSD loan application';
        $application->save();

        return response()->json([
            'success' => true,
            'message' => 'Loan application received',
            'data' => ['applicationId' => $application->id],
        ]);
    }

    public function utilityPayment(Request $request, UtilityPaymentService $service)
    {
        $request->validate([
            'clientId' => 'required|integer',
            'providerId' => 'required|integer',
            'accountNumber' => 'required|string|max:50',
            'amount' => 'required|numeric|min:1',
        ]);

        try {
            $result = $service->payFromFosa(
                (int)$request->clientId,
                (int)$request->providerId,
                $request->accountNumber,
                (float)$request->amount
            );

            return response()->json(['success' => true, 'message' => 'Payment completed', 'data' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function mpesaStk(Request $request)
    {
        $request->validate([
            'clientId' => 'required|integer',
            'loanId' => 'required|integer',
            'amount' => 'required|numeric|min:1',
            'phoneNumber' => 'required|string',
        ]);

        $client = Client::findOrFail($request->clientId);
        $loan = Loan::where('id', $request->loanId)->where('client_id', $client->id)->firstOrFail();

        try {
            $testMode = $this->setting('mpesa.test_mode') === 'yes';
            $baseUrl = $testMode ? $this->setting('mpesa.sandbox_url') : $this->setting('mpesa.live_url');
            $consumerKey = $this->setting('mpesa.consumer_key');
            $consumerSecret = $this->setting('mpesa.consumer_secret');
            $shortcode = $this->setting('mpesa.business_shortcode');
            $passkey = $this->setting('mpesa.passkey');

            if (!$baseUrl || !$consumerKey || !$consumerSecret || !$shortcode || !$passkey) {
                return response()->json(['success' => false, 'message' => 'M-Pesa not configured'], 422);
            }

            $tokenResponse = Http::withBasicAuth($consumerKey, $consumerSecret)
                ->get(rtrim($baseUrl, '/') . '/oauth/v1/generate?grant_type=client_credentials');

            if (!$tokenResponse->successful()) {
                return response()->json(['success' => false, 'message' => 'M-Pesa auth failed'], 422);
            }

            $accessToken = $tokenResponse->json('access_token');
            $timestamp = Carbon::now()->format('YmdHis');
            $phone = preg_replace('/\D/', '', $request->phoneNumber);
            if (str_starts_with($phone, '0')) {
                $phone = '254' . substr($phone, 1);
            } elseif (!str_starts_with($phone, '254')) {
                $phone = '254' . $phone;
            }

            $stkResponse = Http::withToken($accessToken)
                ->post(rtrim($baseUrl, '/') . '/mpesa/stkpush/v1/processrequest', [
                    'BusinessShortCode' => $shortcode,
                    'Password' => base64_encode($shortcode . $passkey . $timestamp),
                    'Timestamp' => $timestamp,
                    'TransactionType' => 'CustomerPayBillOnline',
                    'Amount' => (int)$request->amount,
                    'PartyA' => $phone,
                    'PartyB' => $shortcode,
                    'PhoneNumber' => $phone,
                    'CallBackURL' => url('webhooks/mpesa'),
                    'AccountReference' => (string)$loan->id,
                    'TransactionDesc' => 'Loan Repayment',
                ]);

            $body = $stkResponse->json();
            if (!$stkResponse->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => $body['errorMessage'] ?? 'STK push failed',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'STK push initiated',
                'data' => [
                    'checkoutRequestId' => $body['CheckoutRequestID'] ?? null,
                    'loanId' => $loan->id,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function providers()
    {
        $providers = DB::table('sacco_utility_providers')->where('active', 1)->orderBy('id')->get();
        return response()->json(['success' => true, 'data' => $providers]);
    }

    protected function setting(string $key): ?string
    {
        return Setting::where('setting_key', $key)->value('setting_value');
    }
}

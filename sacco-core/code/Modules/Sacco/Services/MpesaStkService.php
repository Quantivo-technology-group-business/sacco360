<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\Loan\Entities\Loan;
use Modules\Setting\Entities\Setting;

class MpesaStkService
{
    public function pushLoanRepayment(int $clientId, int $loanId, float $amount, string $phoneNumber): array
    {
        $loan = Loan::where('id', $loanId)->where('client_id', $clientId)->firstOrFail();

        $testMode = $this->setting('mpesa.test_mode') === 'yes';
        $baseUrl = $testMode ? $this->setting('mpesa.sandbox_url') : $this->setting('mpesa.live_url');
        $consumerKey = $this->setting('mpesa.consumer_key');
        $consumerSecret = $this->setting('mpesa.consumer_secret');
        $shortcode = $this->setting('mpesa.business_shortcode');
        $passkey = $this->setting('mpesa.passkey');

        if (!$baseUrl || !$consumerKey || !$consumerSecret || !$shortcode || !$passkey) {
            throw new \RuntimeException('M-Pesa not configured');
        }

        $tokenResponse = Http::withBasicAuth($consumerKey, $consumerSecret)
            ->get(rtrim($baseUrl, '/') . '/oauth/v1/generate?grant_type=client_credentials');

        if (!$tokenResponse->successful()) {
            throw new \RuntimeException('M-Pesa auth failed');
        }

        $accessToken = $tokenResponse->json('access_token');
        $timestamp = Carbon::now()->format('YmdHis');
        $phone = preg_replace('/\D/', '', $phoneNumber);
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
                'Amount' => (int) $amount,
                'PartyA' => $phone,
                'PartyB' => $shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' => url('webhooks/mpesa'),
                'AccountReference' => (string) $loan->id,
                'TransactionDesc' => 'Loan Repayment',
            ]);

        $body = $stkResponse->json();
        if (!$stkResponse->successful()) {
            throw new \RuntimeException($body['errorMessage'] ?? 'STK push failed');
        }

        return [
            'checkoutRequestId' => $body['CheckoutRequestID'] ?? null,
            'loanId' => $loan->id,
        ];
    }

    protected function setting(string $key): ?string
    {
        return Setting::where('setting_key', $key)->value('setting_value');
    }
}

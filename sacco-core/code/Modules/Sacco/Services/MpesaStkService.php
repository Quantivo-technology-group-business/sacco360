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

    /**
     * Send an STK push for a chama group contribution.
     *
     * The contribution row is created as 'pending' by the caller before this
     * runs; the CheckoutRequestID returned by Daraja is persisted on it so the
     * asynchronous callback can be matched back and flip it to 'posted'.
     *
     * @throws \RuntimeException when M-Pesa is not configured or Daraja rejects the push
     */
    public function pushGroupContribution(int $clientId, int $groupId, int $contributionId, float $amount, string $phoneNumber): array
    {
        $contribution = \Modules\Sacco\Entities\SaccoGroupContribution::where('id', $contributionId)
            ->where('client_id', $clientId)
            ->where('sacco_group_id', $groupId)
            ->firstOrFail();

        $credentials = $this->credentials();

        $accessToken = $this->accessToken($credentials);
        $timestamp   = Carbon::now()->format('YmdHis');
        $phone       = $this->normalisePhone($phoneNumber);

        // AccountReference is what comes back as the paybill account number and
        // is the human-readable correlation key for reconciliation. The
        // authoritative match is CheckoutRequestID, handled in the callback.
        $stkResponse = Http::withToken($accessToken)
            ->post(rtrim($credentials['base_url'], '/') . '/mpesa/stkpush/v1/processrequest', [
                'BusinessShortCode' => $credentials['shortcode'],
                'Password'          => base64_encode($credentials['shortcode'] . $credentials['passkey'] . $timestamp),
                'Timestamp'         => $timestamp,
                'TransactionType'   => 'CustomerPayBillOnline',
                'Amount'            => (int) round($amount),
                'PartyA'            => $phone,
                'PartyB'            => $credentials['shortcode'],
                'PhoneNumber'       => $phone,
                'CallBackURL'       => url('api/v1/sacco/mpesa/stk/group-contribution'),
                'AccountReference'  => 'CHAMA' . $groupId,
                'TransactionDesc'   => 'Chama Contribution',
            ]);

        $body = $stkResponse->json();

        if (!$stkResponse->successful()) {
            throw new \RuntimeException($body['errorMessage'] ?? 'STK push failed');
        }

        $checkoutRequestId = $body['CheckoutRequestID'] ?? null;

        $contribution->checkout_request_id = $checkoutRequestId;
        $contribution->merchant_request_id = $body['MerchantRequestID'] ?? null;
        $contribution->save();

        return [
            'checkoutRequestId'   => $checkoutRequestId,
            'merchantRequestId'   => $body['MerchantRequestID'] ?? null,
            'contributionId'      => $contribution->id,
            'groupId'             => $groupId,
            'responseDescription' => $body['ResponseDescription'] ?? null,
        ];
    }

    /**
     * Ask Daraja whether an STK checkout actually completed.
     *
     * The STK callback URL is unauthenticated — Safaricom cannot sign it — so the
     * payload alone is spoofable. Anyone who guessed a CheckoutRequestID could
     * otherwise post a contribution that was never paid. Re-querying Daraja makes
     * the callback advisory and the API the source of truth.
     *
     * Fails safe: if the query cannot be completed, this returns a non-zero
     * ResultCode so the caller leaves the contribution pending for manual
     * approval rather than posting money that cannot be confirmed.
     *
     * @return array{result_code:int, result_desc:string, verified:bool}
     */
    public function queryStkStatus(string $checkoutRequestId): array
    {
        try {
            $credentials = $this->credentials();
            $accessToken = $this->accessToken($credentials);
            $timestamp   = Carbon::now()->format('YmdHis');

            $response = Http::withToken($accessToken)
                ->post(rtrim($credentials['base_url'], '/') . '/mpesa/stkpushquery/v1/query', [
                    'BusinessShortCode' => $credentials['shortcode'],
                    'Password'          => base64_encode($credentials['shortcode'] . $credentials['passkey'] . $timestamp),
                    'Timestamp'         => $timestamp,
                    'CheckoutRequestID' => $checkoutRequestId,
                ]);

            $body = $response->json();

            if (!$response->successful() || !is_array($body)) {
                return [
                    'result_code' => -1,
                    'result_desc' => 'STK status query failed',
                    'verified'    => false,
                ];
            }

            $resultCode = (int) ($body['ResultCode'] ?? -1);

            return [
                'result_code' => $resultCode,
                'result_desc' => $body['ResultDesc'] ?? 'Unknown',
                'verified'    => $resultCode === 0,
            ];
        } catch (\Throwable $e) {
            // Never post on an unconfirmed checkout: M-Pesa may be unreachable,
            // or credentials may be missing. An officer can still approve by hand.
            return [
                'result_code' => -1,
                'result_desc' => 'STK status query error: ' . $e->getMessage(),
                'verified'    => false,
            ];
        }
    }

    /**
     * Load and validate the Daraja credentials shared by both push methods.
     *
     * @throws \RuntimeException when any required setting is missing
     */
    protected function credentials(): array
    {
        $testMode = $this->setting('mpesa.test_mode') === 'yes';
        $baseUrl  = $testMode ? $this->setting('mpesa.sandbox_url') : $this->setting('mpesa.live_url');

        $credentials = [
            'base_url'       => $baseUrl,
            'consumer_key'   => $this->setting('mpesa.consumer_key'),
            'consumer_secret'=> $this->setting('mpesa.consumer_secret'),
            'shortcode'      => $this->setting('mpesa.business_shortcode'),
            'passkey'        => $this->setting('mpesa.passkey'),
        ];

        foreach ($credentials as $key => $value) {
            if (empty($value)) {
                throw new \RuntimeException('M-Pesa not configured: missing ' . $key);
            }
        }

        return $credentials;
    }

    /**
     * Fetch a Daraja OAuth token.
     *
     * @throws \RuntimeException when authentication fails
     */
    protected function accessToken(array $credentials): string
    {
        $response = Http::withBasicAuth($credentials['consumer_key'], $credentials['consumer_secret'])
            ->get(rtrim($credentials['base_url'], '/') . '/oauth/v1/generate?grant_type=client_credentials');

        if (!$response->successful()) {
            throw new \RuntimeException('M-Pesa auth failed');
        }

        $token = $response->json('access_token');
        if (empty($token)) {
            throw new \RuntimeException('M-Pesa auth returned no access token');
        }

        return $token;
    }

    /**
     * Normalise a Kenyan mobile number to the 254XXXXXXXXX form Daraja expects.
     */
    protected function normalisePhone(string $phoneNumber): string
    {
        $phone = preg_replace('/\D/', '', $phoneNumber);

        if (str_starts_with($phone, '254')) {
            return $phone;
        }
        if (str_starts_with($phone, '0')) {
            return '254' . substr($phone, 1);
        }
        if (str_starts_with($phone, '7') || str_starts_with($phone, '1')) {
            return '254' . $phone;
        }

        return '254' . $phone;
    }

    protected function setting(string $key): ?string
    {
        return Setting::where('setting_key', $key)->value('setting_value');
    }
}

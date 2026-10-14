<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Http;
use Modules\Setting\Entities\Setting;

class MpesaDarajaClient
{
    public function getSettingValue(string $key, string $default = ''): string
    {
        $value = Setting::where('setting_key', $key)->value('setting_value');
        return $value !== null && $value !== '' ? (string)$value : $default;
    }

    public function getBaseUrl(): string
    {
        return $this->getSettingValue('mpesa.test_mode', 'yes') === 'yes'
            ? $this->getSettingValue('mpesa.sandbox_url', 'https://sandbox.safaricom.co.ke')
            : $this->getSettingValue('mpesa.live_url', 'https://api.safaricom.co.ke');
    }

    public function getAccessToken(): string
    {
        $consumerKey = $this->getSettingValue('mpesa.consumer_key');
        $consumerSecret = $this->getSettingValue('mpesa.consumer_secret');
        if ($consumerKey === '' || $consumerSecret === '') {
            throw new \RuntimeException('M-Pesa consumer key and secret are not configured.');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($consumerKey . ':' . $consumerSecret),
        ])->get($this->getBaseUrl() . '/oauth/v1/generate', [
            'grant_type' => 'client_credentials',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Failed to obtain M-Pesa access token.');
        }

        $token = $response->json('access_token');
        if (!$token) {
            throw new \RuntimeException('M-Pesa access token missing from response.');
        }

        return $token;
    }

    public function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\s+/', '', $phone);
        $phone = ltrim($phone, '+');
        if (substr($phone, 0, 1) === '0' && strlen($phone) === 10) {
            $phone = '254' . substr($phone, 1);
        }
        if (!preg_match('/^254\d{9}$/', $phone)) {
            throw new \RuntimeException('Invalid Kenyan M-Pesa phone number.');
        }
        return $phone;
    }

    public function isB2cConfigured(): bool
    {
        return $this->getSettingValue('mpesa.b2c_initiator_name') !== ''
            && $this->getSettingValue('mpesa.b2c_security_credential') !== ''
            && $this->getSettingValue('mpesa.b2c_shortcode') !== '';
    }

    public function initiateB2c(array $payload): array
    {
        $token = $this->getAccessToken();
        $shortcode = $this->getSettingValue('mpesa.b2c_shortcode');
        $resultUrl = url('api/v1/sacco/mpesa/b2c/result');
        $timeoutUrl = url('api/v1/sacco/mpesa/b2c/timeout');

        $body = [
            'InitiatorName' => $this->getSettingValue('mpesa.b2c_initiator_name'),
            'SecurityCredential' => $this->getSettingValue('mpesa.b2c_security_credential'),
            'CommandID' => $this->getSettingValue('mpesa.b2c_command_id', 'BusinessPayment'),
            'Amount' => (string)round($payload['amount']),
            'PartyA' => $shortcode,
            'PartyB' => $payload['phone'],
            'Remarks' => $payload['remarks'] ?? 'SACCO M-Pesa transfer',
            'QueueTimeOutURL' => $timeoutUrl,
            'ResultURL' => $resultUrl,
            'Occasion' => $payload['reference'] ?? '',
        ];

        $response = Http::withToken($token)
            ->post($this->getBaseUrl() . '/mpesa/b2c/v1/paymentrequest', $body);

        return [
            'success' => $response->successful(),
            'body' => $response->json() ?? [],
            'request' => $body,
        ];
    }
}

<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Hash;
use Modules\Client\Entities\Client;
use Modules\Sacco\Entities\SaccoTransactionOtp;

class TransactionOtpService
{
    public function requiresAuth(float $amount): bool
    {
        return $amount >= (float) config('sacco.transaction_otp_threshold', 1000);
    }

    public function requestOtp(int $clientId, string $purpose, ?string $reference = null): array
    {
        $client = Client::findOrFail($clientId);
        $code = (string) random_int(100000, 999999);
        $expiryMinutes = (int) config('sacco.transaction_otp_expiry_minutes', 5);

        SaccoTransactionOtp::where('client_id', $clientId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        $otp = new SaccoTransactionOtp();
        $otp->client_id = $clientId;
        $otp->purpose = $purpose;
        $otp->reference = $reference;
        $otp->code_hash = Hash::make($code);
        $otp->expires_at = now()->addMinutes($expiryMinutes);
        $otp->save();

        $message = sprintf('Your SACCO transaction OTP is %s. Valid for %d minutes.', $code, $expiryMinutes);
        $sent = false;

        if (!empty($client->mobile) && function_exists('send_sms')) {
            try {
                send_sms($client->mobile, $message);
                $sent = true;
            } catch (\Throwable $e) {
                $sent = false;
            }
        }

        $result = ['sent' => $sent, 'expires_at' => $otp->expires_at->toDateTimeString()];

        if (!$sent || config('app.debug')) {
            session()->flash('dev_otp_code', $code);
            $result['dev_code'] = $code;
        }

        return $result;
    }

    public function verifyOtp(int $clientId, string $code, string $purpose): bool
    {
        $otp = SaccoTransactionOtp::where('client_id', $clientId)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$otp || !Hash::check($code, $otp->code_hash)) {
            return false;
        }

        $otp->verified_at = now();
        $otp->save();

        return true;
    }

    public function setPin(int $clientId, string $pin): void
    {
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            throw new \InvalidArgumentException('Transaction PIN must be 4 to 6 digits.');
        }

        $client = Client::findOrFail($clientId);
        $client->transaction_pin = Hash::make($pin);
        $client->save();
    }

    public function hasPin(int $clientId): bool
    {
        $client = Client::find($clientId);

        return $client && !empty($client->transaction_pin);
    }

    public function verifyPin(int $clientId, string $pin): bool
    {
        $client = Client::find($clientId);
        if (!$client || empty($client->transaction_pin)) {
            return false;
        }

        return Hash::check($pin, $client->transaction_pin);
    }

    public function verifyPinOrOtp(int $clientId, ?string $pin, ?string $otp, string $purpose): bool
    {
        if ($pin && $this->verifyPin($clientId, $pin)) {
            return true;
        }

        if ($otp && $this->verifyOtp($clientId, $otp, $purpose)) {
            return true;
        }

        return false;
    }
}

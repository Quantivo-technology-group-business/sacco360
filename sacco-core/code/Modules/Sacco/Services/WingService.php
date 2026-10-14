<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsProduct;

class WingService
{
    public const WING_BOSA = 'bosa';
    public const WING_FOSA = 'fosa';

    public function getProductWing(?SavingsProduct $product): string
    {
        if (!$product) {
            return self::WING_FOSA;
        }
        if ($product->savings_category === 'compulsory') {
            return self::WING_BOSA;
        }
        return self::WING_FOSA;
    }

    public function getAccountWing(Savings $savings): string
    {
        $savings->loadMissing('savings_product');
        return $this->getProductWing($savings->savings_product);
    }

    public function isWithdrawable(Savings $savings, ?Client $client = null, bool $exitSettlement = false): array
    {
        $savings->loadMissing('savings_product');
        $product = $savings->savings_product;
        $violations = [];

        if ($product && $product->account_product_type === 'fixed_deposit') {
            $fd = DB::table('sacco_fixed_deposits')->where('savings_id', $savings->id)->first();
            if ($fd && $fd->status === 'active' && $fd->maturity_date > date('Y-m-d') && !$exitSettlement) {
                $violations[] = 'Fixed deposit cannot be withdrawn before maturity date ' . $fd->maturity_date;
            }
        }

        if ($this->getProductWing($product) === self::WING_BOSA) {
            $client = $client ?? Client::find($savings->client_id);
            $allowedExit = $exitSettlement || in_array($client->member_status_detail ?? '', ['exit', 'deceased'], true);
            if (!$allowedExit) {
                $violations[] = 'BOSA deposits are non-withdrawable except on member exit, retirement, or death';
            }
        }

        if ($product && (int)$product->lockin_period > 0 && !$exitSettlement) {
            $openedOn = $savings->submitted_on ?? $savings->created_at ?? date('Y-m-d');
            $lockEnd = $this->addLockPeriod($openedOn, (int)$product->lockin_period, $product->lockin_type ?? 'days');
            if ($lockEnd > date('Y-m-d')) {
                $violations[] = 'Account is in lock-in period until ' . $lockEnd;
            }
        }

        return [
            'allowed' => empty($violations),
            'violations' => $violations,
            'wing' => $this->getProductWing($product),
        ];
    }

    public function validateWithdrawalAmount(Savings $savings, float $amount): array
    {
        $savings->loadMissing('savings_product');
        $product = $savings->savings_product;
        $balance = $this->getAccountBalance($savings->id);
        $violations = [];

        if ($product && $product->minimum_balance > 0) {
            $remaining = $balance - $amount;
            if ($remaining > 0 && $remaining < $product->minimum_balance) {
                $violations[] = sprintf('Withdrawal would leave balance below minimum required %.2f', $product->minimum_balance);
            }
        }

        return [
            'allowed' => empty($violations),
            'violations' => $violations,
            'balance' => $balance,
        ];
    }

    public function getAccountByWing(int $clientId, string $wing): ?Savings
    {
        $accounts = Savings::with('savings_product')
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->get();

        foreach ($accounts as $account) {
            if ($this->getAccountWing($account) === $wing) {
                return $account;
            }
        }

        return null;
    }

    public function getFosaAccount(int $clientId): ?Savings
    {
        $accounts = Savings::with('savings_product')
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->get();

        foreach ($accounts as $account) {
            $product = $account->savings_product;
            if ($product && in_array($product->account_product_type, ['current', 'fixed_deposit'], true)) {
                return $account;
            }
            if ($this->getAccountWing($account) === self::WING_FOSA && (!$product || $product->account_product_type !== 'savings')) {
                return $account;
            }
        }

        return $this->getAccountByWing($clientId, self::WING_FOSA);
    }

    public function getBosaAccount(int $clientId): ?Savings
    {
        return $this->getAccountByWing($clientId, self::WING_BOSA);
    }

    public function getSavingsBalanceByWing(int $clientId, string $wing): float
    {
        $total = 0.0;
        $accounts = Savings::with('savings_product')
            ->where('client_id', $clientId)
            ->where('status', 'active')
            ->get();

        foreach ($accounts as $account) {
            if ($this->getAccountWing($account) === $wing) {
                $total += $this->getAccountBalance($account->id);
            }
        }

        return $total;
    }

    public function validateTransfer(Savings $source, Savings $destination, string $transferType): array
    {
        $source->loadMissing('savings_product');
        $destination->loadMissing('savings_product');
        $violations = [];

        if ($source->client_id !== $destination->client_id) {
            $violations[] = 'Transfers must be between accounts of the same member';
        }

        switch ($transferType) {
            case 'savings_to_savings':
                $sourceWing = $this->getAccountWing($source);
                $destWing = $this->getAccountWing($destination);
                if ($sourceWing === $destWing) {
                    $violations[] = 'BOSA/FOSA transfer requires moving funds between different wings';
                }
                break;
            case 'savings_to_loan':
                if ($this->getAccountWing($source) === self::WING_BOSA) {
                    break;
                }
                break;
            case 'share_to_savings':
                if ($this->getAccountWing($destination) !== self::WING_BOSA) {
                    $violations[] = 'Share redemption proceeds must be credited to a BOSA account';
                }
                break;
            case 'savings_to_share':
                if ($this->getAccountWing($source) !== self::WING_BOSA) {
                    $violations[] = 'Share purchases must be funded from a BOSA account';
                }
                $withdrawCheck = $this->isWithdrawable($source);
                if (!$withdrawCheck['allowed']) {
                    $violations = array_merge($violations, $withdrawCheck['violations']);
                }
                break;
        }

        return ['allowed' => empty($violations), 'violations' => $violations];
    }

    public function getAccountBalance(int $savingsId): float
    {
        return (float)(DB::table('savings_transactions')
            ->where('savings_id', $savingsId)
            ->where('reversed', 0)
            ->selectRaw('COALESCE(SUM(credit) - SUM(debit), 0) as balance')
            ->value('balance') ?? 0);
    }

    protected function addLockPeriod(string $startDate, int $period, string $type): string
    {
        $ts = strtotime($startDate);
        switch ($type) {
            case 'weeks':
                return date('Y-m-d', strtotime('+' . $period . ' weeks', $ts));
            case 'months':
                return date('Y-m-d', strtotime('+' . $period . ' months', $ts));
            case 'years':
                return date('Y-m-d', strtotime('+' . $period . ' years', $ts));
            default:
                return date('Y-m-d', strtotime('+' . $period . ' days', $ts));
        }
    }
}

<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Entities\Client;
use Modules\Savings\Entities\Savings;
use Modules\Savings\Entities\SavingsProduct;

class MemberAccountService
{
    public function openBosaAccount(int $clientId): Savings
    {
        $wingService = new WingService();
        $existing = $wingService->getBosaAccount($clientId);
        if ($existing) {
            return $existing;
        }

        $suggestions = (new CrossSellService())->suggestForMember($clientId);
        $eligible = collect($suggestions)->contains(function ($s) {
            return ($s['target'] ?? '') === 'bosa';
        });
        if (!$eligible) {
            throw new \RuntimeException('You are not eligible to open a BOSA account at this time.');
        }

        $product = SavingsProduct::where('savings_category', 'compulsory')
            ->where('active', 1)
            ->first();
        if (!$product) {
            $product = SavingsProduct::where('short_name', 'BOSA')->where('active', 1)->first();
        }
        if (!$product) {
            throw new \RuntimeException('BOSA savings product is not configured.');
        }

        $client = Client::findOrFail($clientId);

        return DB::transaction(function () use ($client, $product) {
            $savings = new Savings();
            $savings->currency_id = $product->currency_id;
            $savings->created_by_id = Auth::id();
            $savings->client_id = $client->id;
            $savings->savings_product_id = $product->id;
            $savings->savings_officer_id = Auth::id();
            $savings->branch_id = $client->branch_id;
            $savings->interest_rate = $product->default_interest_rate ?? 0;
            $savings->interest_rate_type = $product->interest_rate_type;
            $savings->compounding_period = $product->compounding_period;
            $savings->interest_posting_period_type = $product->interest_posting_period_type;
            $savings->decimals = $product->decimals;
            $savings->interest_calculation_type = $product->interest_calculation_type;
            $savings->automatic_opening_balance = 0;
            $savings->lockin_period = $product->lockin_period ?? 0;
            $savings->lockin_type = $product->lockin_type ?? 'days';
            $savings->allow_overdraft = $product->allow_overdraft;
            $savings->overdraft_limit = $product->overdraft_limit;
            $savings->overdraft_interest_rate = $product->overdraft_interest_rate;
            $savings->minimum_overdraft_for_interest = $product->minimum_overdraft_for_interest;
            $savings->submitted_on_date = date('Y-m-d');
            $savings->submitted_by_user_id = Auth::id();
            $savings->activated_on_date = date('Y-m-d');
            $savings->status = 'active';
            $savings->save();

            $count = Savings::where('client_id', $client->id)->count();
            $savings->account_number = 'BOSA-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            $savings->save();

            return $savings;
        });
    }

    public function openFosaAccount(int $clientId): Savings
    {
        $wingService = new WingService();
        $existing = $wingService->getFosaAccount($clientId);
        if ($existing) {
            return $existing;
        }

        $suggestions = (new CrossSellService())->suggestForMember($clientId);
        $eligible = collect($suggestions)->contains(function ($s) {
            return ($s['target'] ?? '') === 'fosa';
        });

        $product = SavingsProduct::where('savings_category', 'voluntary')
            ->where('active', 1)
            ->first();
        if (!$product) {
            $product = SavingsProduct::where('short_name', 'FOSA')->where('active', 1)->first();
        }
        if (!$product) {
            throw new \RuntimeException('FOSA savings product is not configured.');
        }

        $client = Client::findOrFail($clientId);

        return DB::transaction(function () use ($client, $product) {
            $savings = new Savings();
            $savings->currency_id = $product->currency_id;
            $savings->created_by_id = Auth::id();
            $savings->client_id = $client->id;
            $savings->savings_product_id = $product->id;
            $savings->savings_officer_id = Auth::id();
            $savings->branch_id = $client->branch_id;
            $savings->interest_rate = $product->default_interest_rate ?? 0;
            $savings->interest_rate_type = $product->interest_rate_type;
            $savings->compounding_period = $product->compounding_period;
            $savings->interest_posting_period_type = $product->interest_posting_period_type;
            $savings->decimals = $product->decimals;
            $savings->interest_calculation_type = $product->interest_calculation_type;
            $savings->automatic_opening_balance = 0;
            $savings->lockin_period = $product->lockin_period ?? 0;
            $savings->lockin_type = $product->lockin_type ?? 'days';
            $savings->allow_overdraft = $product->allow_overdraft;
            $savings->overdraft_limit = $product->overdraft_limit;
            $savings->overdraft_interest_rate = $product->overdraft_interest_rate;
            $savings->minimum_overdraft_for_interest = $product->minimum_overdraft_for_interest;
            $savings->submitted_on_date = date('Y-m-d');
            $savings->submitted_by_user_id = Auth::id();
            $savings->activated_on_date = date('Y-m-d');
            $savings->status = 'active';
            $savings->save();

            $count = Savings::where('client_id', $client->id)->count();
            $savings->account_number = 'FOSA-' . str_pad((string)$count, 4, '0', STR_PAD_LEFT);
            $savings->save();

            return $savings;
        });
    }
}

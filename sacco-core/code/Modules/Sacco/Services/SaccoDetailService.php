<?php

namespace Modules\Sacco\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Sacco\Entities\SaccoDetail;

class SaccoDetailService
{
    public function get(): SaccoDetail
    {
        if (!Schema::hasTable('sacco_details')) {
            return new SaccoDetail($this->defaultAttributes());
        }

        return SaccoDetail::firstOrCreate([], $this->defaultAttributes());
    }

    public function getHomepageData(): array
    {
        $detail = $this->get();

        return [
            'tagline' => $detail->tagline,
            'mission' => $detail->mission,
            'vision' => $detail->vision,
            'about_text' => $detail->about_text,
            'license_number' => $detail->license_number,
            'registration_number' => $detail->registration_number,
            'founded_year' => $detail->founded_year,
            'paybill_number' => $detail->paybill_number,
            'office_hours' => $detail->office_hours,
            'featured_services' => $this->mergeFeaturedServices($detail->featured_services),
            'primary_color' => $detail->primary_color ?: '#0c2340',
            'accent_color' => $detail->accent_color ?: '#c5a572',
            'facebook_url' => $detail->facebook_url,
            'twitter_url' => $detail->twitter_url,
            'instagram_url' => $detail->instagram_url,
            'linkedin_url' => $detail->linkedin_url,
            'ussd_shortcode' => config('sacco.ussd_shortcode', '*123#'),
            'member_count' => $this->resolveMemberCount($detail),
            'branch_count' => $this->resolveBranchCount($detail),
            'years_serving' => $detail->founded_year ? (int) date('Y') - (int) $detail->founded_year : null,
        ];
    }

    public function defaultServices(): array
    {
        return [
            ['icon' => 'fas fa-piggy-bank', 'title' => 'BOSA Savings', 'description' => 'Build your share capital through compulsory back-office savings.'],
            ['icon' => 'fas fa-wallet', 'title' => 'FOSA Accounts', 'description' => 'Flexible front-office savings with easy deposits and withdrawals.'],
            ['icon' => 'fas fa-hand-holding-usd', 'title' => 'Member Loans', 'description' => 'Affordable credit backed by your savings and guarantors.'],
            ['icon' => 'fas fa-chart-pie', 'title' => 'Dividends & Shares', 'description' => 'Earn returns on your share capital and annual dividends.'],
            ['icon' => 'fas fa-mobile-alt', 'title' => 'M-Pesa Banking', 'description' => 'Deposit and transact conveniently via M-Pesa PayBill.'],
            ['icon' => 'fas fa-laptop', 'title' => 'Internet Banking', 'description' => 'Manage accounts, transfers, and payments from anywhere.'],
            ['icon' => 'fas fa-bolt', 'title' => 'Utility Payments', 'description' => 'Pay electricity, water, airtime, and other bills from your FOSA account via USSD or internet banking.'],
            ['icon' => 'fas fa-chart-line', 'title' => 'Investments', 'description' => 'Grow your wealth through fixed deposits, treasury-linked products, and SACCO investment pools with competitive returns.'],
        ];
    }

    protected function mergeFeaturedServices(?array $existing): array
    {
        $existing = $existing ?: [];
        $titles = array_column($existing, 'title');

        foreach ($this->defaultServices() as $service) {
            if (!in_array($service['title'], $titles, true)) {
                $existing[] = $service;
            }
        }

        return !empty($existing) ? $existing : $this->defaultServices();
    }

    protected function defaultAttributes(): array
    {
        return [
            'tagline' => 'Your trusted partner for savings, credit, and member-owned financial growth.',
            'mission' => 'To empower our members through accessible savings, affordable credit, and ethical financial services.',
            'vision' => 'To be the leading member-owned financial cooperative, transforming lives through inclusive banking.',
            'about_text' => 'We are a member-owned Savings and Credit Cooperative (SACCO) committed to helping individuals and communities achieve financial security. Through BOSA and FOSA accounts, competitive loans, dividends, and digital banking, we put our members first.',
            'license_number' => '',
            'registration_number' => '',
            'founded_year' => (int) date('Y') - 10,
            'paybill_number' => '',
            'office_hours' => 'Mon – Fri: 8:00 AM – 5:00 PM',
            'featured_services' => $this->defaultServices(),
            'show_live_stats' => true,
            'primary_color' => '#0c2340',
            'accent_color' => '#c5a572',
        ];
    }

    protected function resolveMemberCount(SaccoDetail $detail): int
    {
        if (!$detail->show_live_stats && $detail->member_count_override !== null) {
            return (int) $detail->member_count_override;
        }

        if (!Schema::hasTable('clients')) {
            return (int) ($detail->member_count_override ?? 0);
        }

        $query = DB::table('clients');

        if (Schema::hasColumn('clients', 'member_number')) {
            $query->whereNotNull('member_number')->where('member_number', '!=', '');
        }

        $count = (int) $query->count();

        return $count > 0 ? $count : (int) ($detail->member_count_override ?? 0);
    }

    protected function resolveBranchCount(SaccoDetail $detail): int
    {
        if (!$detail->show_live_stats && $detail->branch_count_override !== null) {
            return (int) $detail->branch_count_override;
        }

        if (!Schema::hasTable('branches')) {
            return (int) ($detail->branch_count_override ?? 1);
        }

        $count = (int) DB::table('branches')->count();

        return $count > 0 ? $count : (int) ($detail->branch_count_override ?? 1);
    }
}

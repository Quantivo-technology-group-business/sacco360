<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\Sacco\Services\SaccoDetailService;
use Modules\Setting\Entities\Setting;

class HomeController extends Controller
{
    /**
     * Display the public homepage.
     *
     * @return Response
     */
    public function index()
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole('client')) {
                return redirect('/portal/dashboard');
            }

            return redirect('/dashboard');
        }

        return theme_view('core::home', [
            'company_name' => $this->setting('core.company_name', config('app.name', 'Ultimate Loan Manager')),
            'company_logo' => $this->setting('core.company_logo'),
            'company_email' => $this->setting('core.company_email'),
            'company_address' => $this->setting('core.company_address'),
            'company_phone' => $this->setting('core.company_phone'),
            'homepage' => (new SaccoDetailService())->getHomepageData(),
        ]);
    }

    private function setting($key, $default = '')
    {
        if (!config('app.installed')) {
            return $default;
        }

        $setting = Setting::where('setting_key', $key)->first();

        return $setting && $setting->setting_value !== null
            ? $setting->setting_value
            : $default;
    }
}

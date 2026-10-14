<?php

namespace Modules\Sacco\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Sacco\Services\SaccoDetailService;
use Modules\Setting\Entities\Setting;

class SaccoServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path('Sacco', 'Database/Migrations'));

        // Register Sacco dashboard widgets
        app('arrilot.widget-namespaces')->registerNamespace('Sacco', '\Modules\Sacco\Widgets');

        if (config('app.installed')) {
            try {
                $homepage = app(SaccoDetailService::class)->getHomepageData();
                $brandPrimary = optional(Setting::where('setting_key', 'core.brand_primary_color')->first())->setting_value;
                $brandAccent = optional(Setting::where('setting_key', 'core.brand_accent_color')->first())->setting_value;
                View::share('brand_primary', $brandPrimary ?: $homepage['primary_color']);
                View::share('brand_accent', $brandAccent ?: $homepage['accent_color']);
            } catch (\Throwable $e) {
                View::share('brand_primary', optional(Setting::where('setting_key', 'core.brand_primary_color')->first())->setting_value ?: '#0d5c3d');
                View::share('brand_accent', optional(Setting::where('setting_key', 'core.brand_accent_color')->first())->setting_value ?: '#d4a012');
            }
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Sacco\Console\ProcessStandingOrders::class,
                \Modules\Sacco\Console\ProcessAmlMonitoring::class,
                \Modules\Sacco\Console\ProcessShareDeadlines::class,
                \Modules\Sacco\Console\ProcessEarmarkExpiry::class,
                \Modules\Sacco\Console\ClassifyLoans::class,
                \Modules\Sacco\Console\ProcessLoanAccruals::class,
                \Modules\Sacco\Console\PostProvisions::class,
                \Modules\Sacco\Console\LiquiditySnapshot::class,
                \Modules\Sacco\Console\ComplianceReminders::class,
                \Modules\Sacco\Console\BackfillMemberNumbers::class,
                \Modules\Sacco\Console\SaccoSetup::class,
                \Modules\Sacco\Console\ProcessFixedDepositMaturities::class,
                \Modules\Sacco\Console\DemoSeedCommand::class,
            ]);
        }

        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('sacco:process-fd-maturities')->daily();
            $schedule->command('sacco:process-standing-orders')->daily();
            $schedule->command('sacco:process-aml-monitoring')->daily();
            $schedule->command('sacco:process-share-deadlines')->weekly();
            $schedule->command('sacco:expire-earmarks')->daily();
            $schedule->command('sacco:classify-loans')->daily();
            $schedule->command('sacco:process-loan-accruals')->daily();
            $schedule->command('sacco:liquidity-snapshot')->weeklyOn(3, '08:00');
            $schedule->command('sacco:post-provisions')->monthlyOn(1, '06:00');
            $schedule->command('sacco:compliance-reminders')->dailyAt('07:00');
        });
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(module_path('Sacco', 'Config/config.php'), 'sacco');
        $this->mergeConfigFrom(module_path('Sacco', 'Config/permissions.php'), 'sacco.permissions');
        $this->mergeConfigFrom(module_path('Sacco', 'Config/menus.php'), 'sacco.menus');
        $this->mergeConfigFrom(module_path('Sacco', 'Config/widgets.php'), 'sacco.widgets');
    }

    public function registerViews()
    {
        $sourcePath = module_path('Sacco', 'Resources/views');
        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/sacco';
        }, \Config::get('view.paths')), [$sourcePath]), 'sacco');
    }

    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/sacco');
        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'sacco');
        } else {
            $this->loadTranslationsFrom(module_path('Sacco', 'Resources/lang'), 'sacco');
        }
    }
}

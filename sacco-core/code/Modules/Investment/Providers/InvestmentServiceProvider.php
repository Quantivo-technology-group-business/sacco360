<?php
namespace Modules\Investment\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Investment\Console\PostScheduledProfits;

class InvestmentServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // Register widget namespace for arrilot/widgets
        if (app()->bound('arrilot.widget-namespaces')) {
            app('arrilot.widget-namespaces')->registerNamespace('Investment', '\Modules\Investment\Widgets');
        }

        // Daily scheduled profit posting
        $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('investment:post-profits')->dailyAt('00:30');
        });
    }

    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([PostScheduledProfits::class]);
    }

    protected function registerConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/config.php', 'investment');
        $this->mergeConfigFrom(__DIR__ . '/../Config/widgets.php', 'investment.widgets');
    }

    protected function registerTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'investment');
    }

    protected function registerViews()
    {
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'investment');
    }
}

<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Nwidart\Modules\Facades\Module;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        //$this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerWidgetNamespaces();
    }

    /**
     * Register widget namespaces for the dashboard and all enabled modules that ship widgets.
     */
    protected function registerWidgetNamespaces()
    {
        $namespaces = app('arrilot.widget-namespaces');
        $namespaces->registerNamespace('Dashboard', '\Modules\Dashboard\Widgets');

        foreach (Module::allEnabled() as $module) {
            $name = $module->getName();
            if ($name === 'Dashboard') {
                continue;
            }

            $widgetsPath = module_path($name, 'Widgets');
            if (!is_dir($widgetsPath)) {
                continue;
            }

            $namespaces->registerNamespace($name, '\\Modules\\' . $name . '\\Widgets');
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('dashboard.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'dashboard'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../Config/permissions.php', 'dashboard.permissions'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../Config/menus.php', 'dashboard.menus'
        );
        $this->mergeConfigFrom(
            __DIR__.'/../Config/widgets.php', 'dashboard.widgets'
        );
        $defaultWidgets = require __DIR__ . '/../Config/default_widgets.php';
        if (!Module::isEnabled('Sacco') || !is_dir(module_path('Sacco', 'Widgets'))) {
            unset($defaultWidgets['CompliancePrudential']);
        }
        config(['dashboard.default_widgets' => $defaultWidgets]);
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/dashboard');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/dashboard';
        }, \Config::get('view.paths')), [$sourcePath]), 'dashboard');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/dashboard');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'dashboard');
        } else {
            $this->loadTranslationsFrom(__DIR__ .'/../Resources/lang', 'dashboard');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
